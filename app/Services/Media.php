<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;

/** 媒體庫：圖片上傳（自動縮圖、依手機方向轉正、移除 EXIF 定位資訊） */
final class Media
{
    private const MAX_BYTES = 10485760; // 10 MB
    private const MAX_SIDE = 2000;
    private const TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/x-icon' => 'ico',
        'image/vnd.microsoft.icon' => 'ico',
    ];

    public static function upload(mixed $file): array
    {
        if (!is_array($file) || !isset($file['error']) || is_array($file['error'])) {
            throw new \RuntimeException('沒有收到檔案');
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(match ($file['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => '檔案超過主機上傳上限（' . ini_get('upload_max_filesize') . '），可在 Plesk「PHP 設定」調高 upload_max_filesize。',
                UPLOAD_ERR_NO_FILE => '沒有選擇檔案',
                default => '上傳失敗（錯誤碼 ' . (int) $file['error'] . '）',
            });
        }
        if ((int) $file['size'] > self::MAX_BYTES) {
            throw new \RuntimeException('圖片不可超過 10 MB');
        }
        $tmp = (string) $file['tmp_name'];
        if (!is_uploaded_file($tmp) && PHP_SAPI !== 'cli') {
            throw new \RuntimeException('上傳檔案無效');
        }
        $mime = self::mime($tmp);
        if (!isset(self::TYPES[$mime])) {
            throw new \RuntimeException('只能上傳 JPG、PNG、GIF、WebP 或 ICO 圖片');
        }
        $info = @getimagesize($tmp);
        if (!str_contains($mime, 'icon') && !$info) {
            throw new \RuntimeException('圖片檔案無法讀取');
        }
        $rel = 'uploads/' . date('Y/m');
        $dir = AMF_ROOT . '/public/' . $rel;
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('無法建立上傳資料夾，請確認 public/uploads 可寫入');
        }
        $name = bin2hex(random_bytes(8)) . '.' . self::TYPES[$mime];
        $target = $dir . '/' . $name;
        [$w, $h] = self::store($tmp, $target, $mime, $info ?: []);
        $row = [
            'path' => $rel . '/' . $name,
            'original_name' => mb_substr(basename((string) ($file['name'] ?? $name)), 0, 250),
            'mime' => $mime,
            'size' => (int) (filesize($target) ?: 0),
            'width' => $w,
            'height' => $h,
            'alt' => '',
            'created_at' => DB::now(),
        ];
        $id = DB::insert('media', $row);
        return self::present(['id' => $id] + $row);
    }

    private static function mime(string $path): string
    {
        if (function_exists('finfo_open')) {
            $f = finfo_open(FILEINFO_MIME_TYPE);
            $m = $f ? finfo_file($f, $path) : false;
            if (is_string($m)) {
                return $m;
            }
        }
        $info = @getimagesize($path);
        return (string) ($info['mime'] ?? '');
    }

    /** @return array{0:int,1:int} 寬高（依照片方向轉正後） */
    private static function store(string $tmp, string $target, string $mime, array $info): array
    {
        $w = (int) ($info[0] ?? 0);
        $h = (int) ($info[1] ?? 0);
        $data = (string) file_get_contents($tmp);
        $orientation = $mime === 'image/jpeg' ? self::jpegOrientation($data) : 1;
        $scale = $w > 0 && $h > 0 ? min(1, self::MAX_SIDE / max($w, $h)) : 1;
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        // 太大或需要轉正：用 GD 重新產生（先縮小再轉正，節省記憶體），並保留原本的色彩描述檔
        if (($scale < 1 || $orientation !== 1) && in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)
            && extension_loaded('gd') && self::enoughMemory($w * $h * 5 + $nw * $nh * 8 + strlen($data) * 2)) {
            $out = self::reencode($data, $mime, $orientation, $w, $h, $nw, $nh);
            if ($out !== null && @file_put_contents($target, $out[0]) !== false) {
                @chmod($target, 0644);
                return [$out[1], $out[2]];
            }
        }

        // 其餘情況保留原圖，但一律移除 EXIF／XMP 等可能含拍攝位置的資料
        $clean = match ($mime) {
            'image/jpeg' => self::stripJpeg($data, $orientation),
            'image/png' => self::stripPng($data),
            'image/webp' => self::stripWebp($data),
            default => $data,
        };
        if ($clean === null) {
            throw new \RuntimeException('圖片格式無法處理，請用其他軟體另存成 JPG 或 PNG 後再上傳');
        }
        if (@file_put_contents($target, $clean) === false) {
            throw new \RuntimeException('檔案儲存失敗');
        }
        @chmod($target, 0644);
        return $orientation >= 5 ? [$h, $w] : [$w, $h];
    }

    /** @return array{0:string,1:int,2:int}|null 新檔內容與寬高 */
    private static function reencode(string $data, string $mime, int $orientation, int $w, int $h, int $nw, int $nh): ?array
    {
        $img = @imagecreatefromstring($data);
        if (!$img) {
            return null;
        }
        if ($nw !== $w || $nh !== $h) {
            $dst = imagecreatetruecolor($nw, $nh);
            if ($mime !== 'image/jpeg') {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
            }
            imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
            $img = $dst;
        } elseif ($mime !== 'image/jpeg') {
            imagesavealpha($img, true);
        }
        $img = self::orient($img, $orientation);
        ob_start();
        $ok = match ($mime) {
            'image/jpeg' => imagejpeg($img, null, 85),
            'image/png' => imagepng($img, null, 6),
            default => function_exists('imagewebp') && imagewebp($img, null, 85),
        };
        $bytes = (string) ob_get_clean();
        if (!$ok || $bytes === '') {
            return null;
        }
        $bytes = match ($mime) {
            'image/jpeg' => self::withJpegIcc(self::stripJpeg($bytes, 1) ?? $bytes, $data),
            'image/png' => self::withPngIcc($bytes, $data),
            default => $bytes,
        };
        return [$bytes, imagesx($img), imagesy($img)];
    }

    /** 依 EXIF 方向（1–8）把影像轉正 */
    private static function orient(\GdImage $img, int $orientation): \GdImage
    {
        if (in_array($orientation, [2, 7], true)) {
            imageflip($img, IMG_FLIP_HORIZONTAL);
        } elseif (in_array($orientation, [4, 5], true)) {
            imageflip($img, IMG_FLIP_VERTICAL);
        }
        $angle = match ($orientation) {
            3 => 180,
            5, 6, 7 => 270, // 順時針 90 度（imagerotate 以逆時針計算）
            8 => 90,
            default => 0,
        };
        return $angle ? (imagerotate($img, $angle, 0) ?: $img) : $img;
    }

    /** JPEG 檔頭區段（到影像資料為止）：[[marker, 區段位元組], ...] */
    private static function jpegHeader(string $data): array
    {
        $segs = [];
        if (strncmp($data, "\xFF\xD8", 2) !== 0) {
            return $segs;
        }
        $pos = 2;
        $len = strlen($data);
        while ($pos + 4 <= $len && $data[$pos] === "\xFF") {
            $marker = ord($data[$pos + 1]);
            if ($marker === 0xFF) {
                $pos++;
                continue;
            }
            if ($marker === 0xDA || $marker === 0xD9) {
                break;
            }
            $n = (ord($data[$pos + 2]) << 8) | ord($data[$pos + 3]);
            if ($n < 2 || $pos + 2 + $n > $len) {
                break;
            }
            $segs[] = [$marker, substr($data, $pos, 2 + $n)];
            $pos += 2 + $n;
        }
        return $segs;
    }

    /** 讀取 JPEG 的 EXIF 方向，沒有則回傳 1 */
    private static function jpegOrientation(string $data): int
    {
        foreach (self::jpegHeader($data) as [$marker, $seg]) {
            if ($marker !== 0xE1 || substr($seg, 4, 6) !== "Exif\0\0") {
                continue;
            }
            $t = substr($seg, 10);
            $le = substr($t, 0, 2) === 'II';
            if (!$le && substr($t, 0, 2) !== 'MM') {
                return 1;
            }
            $u16 = static fn (int $o): int => strlen($t) >= $o + 2 ? (int) unpack($le ? 'v' : 'n', substr($t, $o, 2))[1] : -1;
            $u32 = static fn (int $o): int => strlen($t) >= $o + 4 ? (int) unpack($le ? 'V' : 'N', substr($t, $o, 4))[1] : -1;
            $ifd = $u32(4);
            $count = $ifd >= 8 ? $u16($ifd) : 0;
            for ($i = 0; $i < min($count, 500); $i++) {
                $tag = $u16($ifd + 2 + $i * 12);
                if ($tag === -1) {
                    break;
                }
                if ($tag === 0x0112) {
                    $v = $u16($ifd + 2 + $i * 12 + 8);
                    return $v >= 1 && $v <= 8 ? $v : 1;
                }
            }
            return 1;
        }
        return 1;
    }

    /** JPEG 要保留的區段：影像必要資料、JFIF、Adobe 色彩轉換與色彩描述檔；其餘（EXIF、XMP、IPTC、註解…）移除 */
    private static function keepJpegSegment(int $marker, string $seg): bool
    {
        if ($marker === 0xFE) {
            return false;
        }
        if ($marker < 0xE0 || $marker > 0xEF) {
            return true;
        }
        return $marker === 0xE0 || $marker === 0xEE || ($marker === 0xE2 && substr($seg, 4, 12) === "ICC_PROFILE\0");
    }

    /**
     * 不重新壓縮，直接移除 JPEG 的中繼資料與檔尾附加資料（例如內嵌的第二張圖），
     * 需要轉正的照片補回只含「方向」的最小 EXIF。格式無法解析時回傳 null。
     */
    private static function stripJpeg(string $data, int $orientation): ?string
    {
        if (strncmp($data, "\xFF\xD8", 2) !== 0) {
            return null;
        }
        $len = strlen($data);
        $out = "\xFF\xD8";
        $pos = 2;
        $exifPending = $orientation > 1;
        while ($pos + 2 <= $len) {
            if ($data[$pos] !== "\xFF") {
                return null;
            }
            $marker = ord($data[$pos + 1]);
            if ($marker === 0xFF) {
                $pos++;
                continue;
            }
            if ($marker === 0xD9) {
                return $out . "\xFF\xD9";
            }
            if ($exifPending && $marker !== 0xE0) {
                $out .= self::exifOrientation($orientation);
                $exifPending = false;
            }
            if ($marker === 0x01 || ($marker >= 0xD0 && $marker <= 0xD7)) {
                $out .= substr($data, $pos, 2);
                $pos += 2;
                continue;
            }
            if ($pos + 4 > $len) {
                return null;
            }
            $n = (ord($data[$pos + 2]) << 8) | ord($data[$pos + 3]);
            if ($n < 2 || $pos + 2 + $n > $len) {
                return null;
            }
            $seg = substr($data, $pos, 2 + $n);
            if (self::keepJpegSegment($marker, $seg)) {
                $out .= $seg;
            }
            $pos += 2 + $n;
            if ($marker === 0xDA) {
                // SOS 之後是壓縮影像資料，直到下一個 marker（FF00 與 RSTn 屬於影像資料）
                $start = $pos;
                while (true) {
                    $ff = strpos($data, "\xFF", $pos);
                    if ($ff === false || $ff + 1 >= $len) {
                        return $out . substr($data, $start) . "\xFF\xD9"; // 檔案缺少結尾，補上 EOI
                    }
                    $next = ord($data[$ff + 1]);
                    if ($next === 0xFF) {
                        $pos = $ff + 1;
                        continue;
                    }
                    if ($next === 0x00 || ($next >= 0xD0 && $next <= 0xD7)) {
                        $pos = $ff + 2;
                        continue;
                    }
                    $out .= substr($data, $start, $ff - $start);
                    $pos = $ff;
                    break;
                }
            }
        }
        return null;
    }

    /** 只含「方向」一個欄位的 EXIF 區段 */
    private static function exifOrientation(int $orientation): string
    {
        $tiff = "MM\x00\x2A" . pack('N', 8) . pack('n', 1) . pack('nnNn', 0x0112, 3, 1, $orientation) . "\x00\x00" . pack('N', 0);
        $payload = "Exif\0\0" . $tiff;
        return "\xFF\xE1" . pack('n', strlen($payload) + 2) . $payload;
    }

    /** 把原圖的 RGB 色彩描述檔（例如 iPhone 的 Display P3）放回重新壓縮的 JPEG，避免顏色變淡 */
    private static function withJpegIcc(string $out, string $orig): string
    {
        $icc = '';
        foreach (self::jpegHeader($orig) as [$marker, $seg]) {
            if ($marker === 0xE2 && substr($seg, 4, 12) === "ICC_PROFILE\0") {
                $icc .= $seg;
            }
        }
        if ($icc === '' || substr($icc, 34, 4) !== 'RGB ' || strncmp($out, "\xFF\xD8", 2) !== 0) {
            return $out;
        }
        $pos = 2;
        if (substr($out, 2, 2) === "\xFF\xE0") {
            $pos = 4 + ((ord($out[4]) << 8) | ord($out[5]));
        }
        return substr($out, 0, $pos) . $icc . substr($out, $pos);
    }

    /** @return list<array{0:string,1:string}>|null PNG 區塊：[[類型, 區塊位元組], ...] */
    private static function pngChunks(string $data): ?array
    {
        if (strncmp($data, "\x89PNG\r\n\x1a\n", 8) !== 0) {
            return null;
        }
        $pos = 8;
        $len = strlen($data);
        $chunks = [];
        while ($pos + 12 <= $len) {
            $n = (int) unpack('N', substr($data, $pos, 4))[1];
            if ($n > $len - $pos - 12) {
                return null;
            }
            $type = substr($data, $pos + 4, 4);
            $chunks[] = [$type, substr($data, $pos, 12 + $n)];
            $pos += 12 + $n;
            if ($type === 'IEND') {
                return $chunks;
            }
        }
        return null;
    }

    /** 移除 PNG 的 EXIF 與文字區塊（XMP 定位資訊放在這裡），並丟掉 IEND 之後的資料 */
    private static function stripPng(string $data): ?string
    {
        $chunks = self::pngChunks($data);
        if ($chunks === null) {
            return null;
        }
        $out = "\x89PNG\r\n\x1a\n";
        foreach ($chunks as [$type, $raw]) {
            if (!in_array($type, ['eXIf', 'tEXt', 'zTXt', 'iTXt'], true)) {
                $out .= $raw;
            }
        }
        return $out;
    }

    /** 把原圖的 RGB 色彩描述檔放回縮小後的 PNG（例如 Mac 截圖的 Display P3） */
    private static function withPngIcc(string $out, string $orig): string
    {
        $icc = null;
        foreach (self::pngChunks($orig) ?? [] as [$type, $raw]) {
            if ($type === 'iCCP') {
                $icc = $raw;
                break;
            }
        }
        if ($icc === null || !function_exists('gzuncompress')) {
            return $out;
        }
        $body = substr($icc, 8, -4);
        $nul = strpos($body, "\0");
        $profile = $nul === false ? false : @gzuncompress(substr($body, $nul + 2));
        $chunks = self::pngChunks($out);
        if (!is_string($profile) || substr($profile, 16, 4) !== 'RGB ' || $chunks === null) {
            return $out;
        }
        $res = "\x89PNG\r\n\x1a\n";
        foreach ($chunks as [$type, $raw]) {
            if ($type === 'iCCP' || $type === 'sRGB') {
                continue;
            }
            $res .= $raw;
            if ($type === 'IHDR') {
                $res .= $icc;
            }
        }
        return $res;
    }

    /** 移除 WebP 的 EXIF／XMP 區塊並清除對應旗標 */
    private static function stripWebp(string $data): ?string
    {
        if (strlen($data) < 20 || substr($data, 0, 4) !== 'RIFF' || substr($data, 8, 4) !== 'WEBP') {
            return null;
        }
        $end = min(strlen($data), 8 + (int) unpack('V', substr($data, 4, 4))[1]);
        $pos = 12;
        $body = '';
        while ($pos + 8 <= $end) {
            $type = substr($data, $pos, 4);
            $n = (int) unpack('V', substr($data, $pos + 4, 4))[1];
            if ($n > $end - $pos - 8) {
                return null;
            }
            $size = 8 + $n + ($n & 1);
            $chunk = str_pad(substr($data, $pos, $size), $size, "\0");
            if ($type === 'VP8X' && $n >= 10) {
                $chunk[8] = chr(ord($chunk[8]) & ~0x0C);
            }
            if ($type !== 'EXIF' && $type !== 'XMP ') {
                $body .= $chunk;
            }
            $pos += $size;
        }
        return $body === '' ? null : 'RIFF' . pack('V', 4 + strlen($body)) . 'WEBP' . $body;
    }

    /** 大圖解碼很吃記憶體，先估算避免超過主機 memory_limit */
    private static function enoughMemory(int $need): bool
    {
        $need += 16 * 1024 * 1024;
        $limit = self::bytes((string) ini_get('memory_limit'));
        if ($limit < 0) {
            return true;
        }
        if (memory_get_usage() + $need <= $limit) {
            return true;
        }
        @ini_set('memory_limit', '512M');
        $limit = self::bytes((string) ini_get('memory_limit'));
        return $limit < 0 || memory_get_usage() + $need <= $limit;
    }

    private static function bytes(string $v): int
    {
        $v = trim($v);
        if ($v === '' || $v === '-1') {
            return -1;
        }
        $n = (int) $v;
        return match (strtolower(substr($v, -1))) {
            'g' => $n * 1073741824,
            'm' => $n * 1048576,
            'k' => $n * 1024,
            default => $n,
        };
    }

    public static function present(array $r): array
    {
        return [
            'id' => (int) $r['id'],
            'url' => '/' . ltrim((string) $r['path'], '/'),
            'name' => (string) $r['original_name'],
            'mime' => (string) $r['mime'],
            'size' => (int) $r['size'],
            'width' => (int) $r['width'],
            'height' => (int) $r['height'],
            'alt' => (string) $r['alt'],
            'created' => fmt_date($r['created_at'] ?? null, 'Y/m/d'),
        ];
    }

    public static function list(int $page = 1, int $per = 60): array
    {
        $page = max(1, $page);
        $rows = DB::all('SELECT * FROM {media} ORDER BY `id` DESC LIMIT ' . ($per + 1) . ' OFFSET ' . (($page - 1) * $per));
        $hasMore = count($rows) > $per;
        return ['items' => array_map([self::class, 'present'], array_slice($rows, 0, $per)), 'hasMore' => $hasMore];
    }

    public static function delete(int $id): void
    {
        $row = DB::one('SELECT * FROM {media} WHERE `id` = ?', [$id]);
        if (!$row) {
            return;
        }
        $file = realpath(AMF_ROOT . '/public/' . $row['path']);
        $uploads = realpath(AMF_ROOT . '/public/uploads');
        if ($file && $uploads && str_starts_with($file, $uploads . DIRECTORY_SEPARATOR)) {
            @unlink($file);
        }
        DB::delete('media', '`id` = ?', [$id]);
    }
}
