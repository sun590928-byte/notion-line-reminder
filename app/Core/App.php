<?php
declare(strict_types=1);

namespace App\Core;

use App\Controllers\InstallController;

final class App
{
    public static function run(): void
    {
        Request::capture();
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');

        $path = Request::path();
        if (!Config::installed()) {
            if ($path !== '/install') {
                redirect('/install');
            }
            (new InstallController())->handle();
            return;
        }

        // 文件根目錄設在 httpdocs 時，直接輸入 /public/... 也能開到網站：導回正式網址，避免整站出現兩份
        $base = Request::base();
        if (str_ends_with($base, '/public') && Request::method() === 'GET') {
            $canonical = base_url();
            if (rtrim((string) parse_url($canonical, PHP_URL_PATH), '/') . '/public' === $base) {
                $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
                redirect($canonical . $path . ($query !== '' ? '?' . $query : ''), 301);
            }
        }

        $router = new Router();
        (require AMF_ROOT . '/app/routes.php')($router);
        $router->dispatch(Request::method(), $path);
    }
}
