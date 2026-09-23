<?php
// 本機開發用：php -S localhost:8000 -t public tools/dev-server.php
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . '/../public' . $path;
if ($path !== '/' && is_file($file)) {
    return false;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
// 測試用：AMF_FAKE_HTTP 指向一個會設定 App\Core\Http::$mock 的檔案（只在本機開發伺服器生效）
if (getenv('AMF_FAKE_HTTP')) {
    require_once __DIR__ . '/../app/bootstrap.php';
    require getenv('AMF_FAKE_HTTP');
}
require __DIR__ . '/../public/index.php';
