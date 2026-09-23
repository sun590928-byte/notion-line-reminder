<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    private static string $path = '/';
    private static string $base = '';
    private static ?array $json = null;
    private static ?string $raw = null;

    /** 解析網址路徑與子目錄（支援文件根目錄設在 public/ 或專案根目錄兩種部署方式） */
    public static function capture(): void
    {
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        $dir = rtrim(dirname($script), '/.');
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $uriPath = rawurldecode((string) strtok($uri, '?'));
        if ($uriPath === '') {
            $uriPath = '/';
        }
        // 專案根目錄當作文件根目錄時，.htaccess 會把請求轉給 public/，網址上不應出現 /public
        if (str_ends_with($dir, '/public') && !str_starts_with($uriPath, $dir . '/') && $uriPath !== $dir) {
            $dir = substr($dir, 0, -7);
        }
        self::$base = $dir;
        $path = $uriPath;
        if ($dir !== '' && str_starts_with($path, $dir)) {
            $path = substr($path, strlen($dir));
        }
        if (str_starts_with($path, '/index.php')) {
            $path = substr($path, 10);
        }
        $path = '/' . trim((string) $path, '/');
        self::$path = preg_replace('#/+#', '/', $path) ?? '/';
    }

    public static function path(): string
    {
        return self::$path;
    }

    public static function base(): string
    {
        return self::$base;
    }

    public static function method(): string
    {
        $m = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        return $m === 'HEAD' ? 'GET' : $m;
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    public static function isHttps(): bool
    {
        $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
        if ($https !== '' && $https !== 'off') {
            return true;
        }
        if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
            return true;
        }
        return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }

    public static function host(): string
    {
        $host = (string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost');
        return preg_match('/^[A-Za-z0-9.\-:\[\]]+$/', $host) ? $host : 'localhost';
    }

    /** 目前請求推算出的網站根網址（不含結尾斜線） */
    public static function detectedBaseUrl(): string
    {
        return (self::isHttps() ? 'https' : 'http') . '://' . self::host() . self::$base;
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $_POST)) {
            return $_POST[$key];
        }
        $json = self::json();
        if (array_key_exists($key, $json)) {
            return $json[$key];
        }
        return $_GET[$key] ?? $default;
    }

    public static function str(string $key, string $default = ''): string
    {
        $v = self::input($key, $default);
        return is_scalar($v) ? trim((string) $v) : $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        $v = self::input($key, $default);
        return is_numeric($v) ? (int) $v : $default;
    }

    public static function bool(string $key): bool
    {
        $v = self::input($key, false);
        return $v === true || $v === 1 || $v === '1' || $v === 'on' || $v === 'true';
    }

    public static function query(string $key, string $default = ''): string
    {
        $v = $_GET[$key] ?? $default;
        return is_scalar($v) ? (string) $v : $default;
    }

    public static function raw(): string
    {
        if (self::$raw === null) {
            self::$raw = (string) file_get_contents('php://input');
        }
        return self::$raw;
    }

    public static function json(): array
    {
        if (self::$json === null) {
            self::$json = [];
            if (str_contains(strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? '')), 'application/json')) {
                $data = json_decode(self::raw(), true);
                self::$json = is_array($data) ? $data : [];
            }
        }
        return self::$json;
    }

    public static function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $v = $_SERVER[$key] ?? null;
        return is_string($v) ? $v : null;
    }

    public static function ip(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public static function userAgent(): string
    {
        return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
    }

    public static function wantsJson(): bool
    {
        return str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
            || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    public static function isBot(): bool
    {
        return (bool) preg_match('/bot|crawl|spider|slurp|preview|facebookexternalhit|lighthouse|headless|curl|wget|python|monitor/i', self::userAgent());
    }
}
