<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\View;

/** 後台控制器基底：所有後台頁面都需要管理員登入 */
abstract class AdminBase
{
    protected array $admin;

    public function __construct()
    {
        $this->admin = Auth::requireAdmin();
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex, nofollow');
    }

    protected function page(string $view, string $title, array $data = [], string $nav = ''): void
    {
        echo View::render('admin/' . $view, $data + ['title' => $title, 'admin' => $this->admin, 'nav' => $nav], 'admin');
    }

    /** JSON API：檢查 CSRF */
    protected function api(): void
    {
        verify_csrf();
    }
}
