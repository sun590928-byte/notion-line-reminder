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

    /** @return array{0:int,1:int} 寬高 */
    private static function store(string $tmp, string $target, string $mime, array $info): array
    {
        $w = (int) ($info[0] ?? 0);
        $h = (int) ($info[1] ?? 0);
        $gdTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $needsResize = $w > self::MAX_SIDE || $h > self::MAX_SIDE;
        $useGd = extension_loaded('gd') && in_array($mime, $gdTypes, true) && $w > 0 && ($needsResize || $mime === 'image/jpeg') && self::enoughMemory($w, $h);

        if ($useGd) {
            $src = match ($mime) {
                'image/jpeg' => @imagecreatefromjpeg($tmp),
                'image/png' => @imagecreatefrompng($tmp),
                default => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false,
            };
            if ($src) {
                if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
                    $exif = @exif_read_data($tmp);
                    $src = match ((int) ($exif['Orientation'] ?? 1)) {
                        3 => imagerotate($src, 180, 0) ?: $src,
                        6 => imagerotate($src, -90, 0) ?: $src,
                        8 => imagerotate($src, 90, 0) ?: $src,
                        default => $src,
                    };
                }
                $w = imagesx($src);
                $h = imagesy($src);
                $scale = min(1, self::MAX_SIDE / max($w, $h));
                $nw = max(1, (int) round($w * $scale));
                $nh = max(1, (int) round($h * $scale));
                $dst = $src;
                if ($scale < 1) {
                    $dst = imagecreatetruecolor($nw, $nh);
                    if ($mime !== 'image/jpeg') {
                        imagealphablending($dst, false);
                        imagesavealpha($dst, true);
                    }
                    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                } elseif ($mime !== 'image/jpeg') {
                    imagesavealpha($dst, true);
                }
                $ok = match ($mime) {
                    'image/jpeg' => imagejpeg($dst, $target, 85),
                    'image/png' => imagepng($dst, $target, 6),
                    default => imagewebp($dst, $target, 85),
                };
                if ($ok) {
                    @chmod($target, 0644);
                    return [$nw, $nh];
                }
            }
        }
        if (!@move_uploaded_file($tmp, $target) && !(PHP_SAPI === 'cli' && @copy($tmp, $target))) {
            throw new \RuntimeException('檔案儲存失敗');
        }
        @chmod($target, 0644);
        return [$w, $h];
    }

    /** 大圖解碼很吃記憶體，先估算避免超過主機 memory_limit */
    private static function enoughMemory(int $w, int $h): bool
    {
        $need = $w * $h * 5 + 16 * 1024 * 1024;
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
