<?php
declare(strict_types=1);

use App\Core\Config;
use App\Core\Request;
use App\Core\Session;
use App\Core\Settings;
use App\Core\View;
use App\Services\Icons;

/** HTML 跳脫 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** 站內網址（自動加上子目錄） */
function url(string $path = '/'): string
{
    if ($path === '' || preg_match('#^([a-z][a-z0-9+.\-]*:|//|\#)#i', $path)) {
        return $path;
    }
    return Request::base() . '/' . ltrim($path, '/');
}

/** 網站正式網址（OAuth 回呼、分享連結使用） */
function base_url(): string
{
    $configured = trim((string) Config::get('base_url', ''));
    if ($configured === '' && Config::installed()) {
        try {
            $configured = trim((string) (Settings::site()['url'] ?? ''));
        } catch (\Throwable) {
            $configured = '';
        }
    }
    return rtrim($configured !== '' ? $configured : Request::detectedBaseUrl(), '/');
}

function absolute_url(string $path = '/'): string
{
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return base_url() . '/' . ltrim($path, '/');
}

/** 靜態檔網址（附檔案修改時間，更新後瀏覽器會重新下載） */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = AMF_ROOT . '/public/assets/' . $path;
    return url('/assets/' . $path) . '?v=' . (is_file($file) ? (string) filemtime($file) : AMF_VERSION);
}

/** 圖片網址（上傳檔存成 /uploads/...，輸出時補上子目錄） */
function media_url(?string $src): string
{
    $src = trim((string) $src);
    if ($src === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $src)) {
        return $src;
    }
    if (str_starts_with($src, '/') && !str_starts_with($src, '//')) {
        return url($src);
    }
    return '';
}

function redirect(string $to, int $status = 302): never
{
    if (str_starts_with($to, '/') && !str_starts_with($to, '//')) {
        $to = url($to);
    }
    header('Location: ' . $to, true, $status);
    exit;
}

function json_response(mixed $data, int $status = 200): never
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function abort(int $code, string $message = ''): never
{
    $defaults = [
        400 => '請求格式錯誤',
        401 => '請先登入',
        403 => '沒有權限',
        404 => '找不到這個頁面',
        405 => '不支援的請求方式',
        419 => '頁面已過期，請重新整理後再試一次',
        429 => '操作太頻繁，請稍後再試',
        503 => '網站維護中',
    ];
    $message = $message !== '' ? $message : ($defaults[$code] ?? '發生錯誤');
    if (str_contains(Request::path(), '/api/') || Request::wantsJson()) {
        json_response(['ok' => false, 'error' => $message], $code);
    }
    http_response_code($code);
    echo View::render($code === 404 ? 'errors/404' : 'errors/error', ['code' => $code, 'message' => $message, 'title' => $message], 'bare');
    exit;
}

function view(string $template, array $data = [], ?string $layout = null): void
{
    echo View::render($template, $data, $layout);
}

function csrf_token(): string
{
    Session::start();
    $token = Session::get('_csrf');
    if (!is_string($token) || strlen($token) < 32) {
        $token = bin2hex(random_bytes(32));
        Session::set('_csrf', $token);
    }
    return $token;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    Session::startIfExists();
    $known = (string) Session::get('_csrf', '');
    $sent = $_POST['_csrf'] ?? Request::header('X-CSRF-Token') ?? Request::json()['_csrf'] ?? '';
    if ($known === '' || !is_string($sent) || !hash_equals($known, $sent)) {
        abort(419);
    }
}

function flash(string $type, string $message): void
{
    Session::flash($type, $message);
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function fmt_date(?string $datetime, string $format = 'Y/m/d H:i'): string
{
    if (!$datetime) {
        return '—';
    }
    $ts = strtotime($datetime);
    return $ts ? date($format, $ts) : '—';
}

/** 相對時間，例如「3 分鐘前」 */
function time_ago(?string $datetime): string
{
    if (!$datetime) {
        return '—';
    }
    $diff = time() - (int) strtotime($datetime);
    if ($diff < 60) {
        return '剛剛';
    }
    if ($diff < 3600) {
        return intdiv($diff, 60) . ' 分鐘前';
    }
    if ($diff < 86400) {
        return intdiv($diff, 3600) . ' 小時前';
    }
    if ($diff < 86400 * 30) {
        return intdiv($diff, 86400) . ' 天前';
    }
    return fmt_date($datetime, 'Y/m/d');
}

function icon(string $name, string $class = ''): string
{
    return Icons::svg($name, $class);
}

function str_limit(string $text, int $width): string
{
    return function_exists('mb_strimwidth') ? mb_strimwidth($text, 0, $width, '…', 'UTF-8') : substr($text, 0, $width);
}

/** 只接受站內相對路徑，防止開放式轉址 */
function safe_return(?string $path, string $fallback = '/'): string
{
    $path = (string) $path;
    if ($path === '' || !str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, '\\') || preg_match('/[\x00-\x1f]/', $path)) {
        return $fallback;
    }
    return $path;
}

function client_hash(): string
{
    return substr(hash('sha256', Request::ip() . '|' . date('Y-m-d') . '|' . Config::get('app_key', '')), 0, 32);
}
