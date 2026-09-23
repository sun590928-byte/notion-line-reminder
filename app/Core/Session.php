<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
    public const NAME = 'amf_session';
    private const LIFETIME = 2592000; // 30 天

    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started) {
            return;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            // 使用自己的 session 目錄，避免主機的清理排程提早刪除登入狀態
            $dir = AMF_ROOT . '/storage/sessions';
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            if (is_dir($dir) && is_writable($dir)) {
                session_save_path($dir);
                ini_set('session.gc_probability', '1');
                ini_set('session.gc_divisor', '200');
            }
            ini_set('session.gc_maxlifetime', (string) self::LIFETIME);
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            session_name(self::NAME);
            session_set_cookie_params([
                'lifetime' => self::LIFETIME,
                'path' => (Request::base() ?: '') . '/',
                'secure' => Request::isHttps(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
        self::$started = true;
    }

    /** 只有在瀏覽器已帶有 session cookie 時才啟動，避免為每位訪客建立 session */
    public static function startIfExists(): void
    {
        if (isset($_COOKIE[self::NAME])) {
            self::start();
        }
    }

    public static function active(): bool
    {
        return self::$started;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$started ? ($_SESSION[$key] ?? $default) : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        if (self::$started) {
            unset($_SESSION[$key]);
        }
    }

    public static function pull(string $key, mixed $default = null): mixed
    {
        $v = self::get($key, $default);
        self::forget($key);
        return $v;
    }

    public static function flash(string $type, string $message): void
    {
        self::start();
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function flashes(): array
    {
        self::startIfExists();
        $list = self::get('_flash', []);
        self::forget('_flash');
        return is_array($list) ? $list : [];
    }

    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    /** 設定與 session 相同屬性的 cookie；$path 以網站子目錄為基準，$expires 為 0 表示關閉瀏覽器即失效 */
    public static function cookie(string $name, string $value, int $expires, string $path = '/'): void
    {
        if (headers_sent()) {
            return;
        }
        setcookie($name, $value, [
            'expires' => $expires,
            'path' => Request::base() . $path,
            'secure' => Request::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function destroyKeys(array $keys): void
    {
        foreach ($keys as $k) {
            self::forget($k);
        }
    }
}
