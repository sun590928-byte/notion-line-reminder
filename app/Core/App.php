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

        $router = new Router();
        (require AMF_ROOT . '/app/routes.php')($router);
        $router->dispatch(Request::method(), $path);
    }
}
