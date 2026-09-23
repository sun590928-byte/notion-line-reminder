<?php
// aftermoonF 官網系統 — 啟動檔
// 注意：此檔刻意只使用舊版 PHP 也能解析的語法，才能在版本過舊時顯示提示訊息。

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'aftermoonF 需要 PHP 8.1 以上版本（目前為 ' . PHP_VERSION . '）。請到 Plesk「PHP 設定」切換為 8.2 或更新版本。';
    exit;
}

if (defined('AMF_ROOT')) {
    return;
}
define('AMF_ROOT', dirname(__DIR__));
define('AMF_VERSION', '1.0.0');

spl_autoload_register(static function ($class) {
    if (strncmp($class, 'App\\', 4) !== 0) {
        return;
    }
    $file = AMF_ROOT . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require_once AMF_ROOT . '/app/helpers.php';

App\Core\Config::load();
date_default_timezone_set((string) App\Core\Config::get('timezone', 'Asia/Taipei'));
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}
App\Core\ErrorHandler::register();
