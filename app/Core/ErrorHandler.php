<?php
declare(strict_types=1);

namespace App\Core;

final class ErrorHandler
{
    public static function register(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', Config::get('debug') ? '1' : '0');
        ini_set('log_errors', '1');

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            if (in_array($severity, [E_DEPRECATED, E_USER_DEPRECATED], true)) {
                return true;
            }
            if (Config::get('debug')) {
                throw new \ErrorException($message, 0, $severity, $file, $line);
            }
            self::log("PHP {$message} @ {$file}:{$line}");
            return true;
        });

        set_exception_handler([self::class, 'handle']);
    }

    public static function handle(\Throwable $e): void
    {
        self::log($e);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code(500);
        }
        $wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');
        if ($wantsJson) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'ok' => false,
                'error' => Config::get('debug') ? $e->getMessage() : '伺服器發生錯誤，請稍後再試。',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (Config::get('debug')) {
            echo '<pre style="white-space:pre-wrap;font:14px/1.5 monospace;padding:24px">'
                . htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') . '</pre>';
            return;
        }
        try {
            echo View::render('errors/500', ['title' => '伺服器錯誤'], 'bare');
        } catch (\Throwable) {
            echo '<!doctype html><meta charset="utf-8"><title>Error</title><p style="font-family:sans-serif;padding:40px">伺服器發生錯誤，請稍後再試。</p>';
        }
    }

    public static function log(\Throwable|string $e): void
    {
        $dir = AMF_ROOT . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $line = '[' . date('Y-m-d H:i:s') . '] ' . ($e instanceof \Throwable
            ? get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString()
            : $e) . "\n";
        @file_put_contents($dir . '/app-' . date('Y-m') . '.log', $line, FILE_APPEND | LOCK_EX);
    }
}
