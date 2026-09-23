<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\DB;
use App\Core\RateLimit;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;

/** 後台登入／登出 */
final class AuthController
{
    public function form(): void
    {
        if (Auth::admin()) {
            redirect('/admin');
        }
        header('Cache-Control: no-store');
        echo View::render('admin/login', [
            'title' => '後台登入',
            'next' => safe_return(Request::query('next'), '/admin'),
            'flashes' => Session::flashes(),
            'username' => '',
        ], 'bare');
    }

    public function login(): void
    {
        verify_csrf();
        $username = mb_substr(Request::str('username'), 0, 100);
        $password = (string) Request::input('password', '');
        $next = safe_return(Request::str('next'), '/admin');
        $ipKey = 'login:ip:' . Request::ip();
        $userKey = 'login:user:' . strtolower($username);

        if (RateLimit::blocked($ipKey, 10) || RateLimit::blocked($userKey, 20)) {
            flash('error', '登入失敗次數太多，請 15 分鐘後再試。');
            redirect('/admin/login?next=' . rawurlencode($next));
        }

        $row = DB::one('SELECT * FROM {admins} WHERE `username` = ?', [$username]);
        // 帳號不存在時也做一次雜湊比對，避免從回應時間猜出帳號
        $hash = $row['password_hash'] ?? '$2y$10$usesomesillystringforsaltnoonewilleverguess0123456789ab';
        $ok = password_verify($password, (string) $hash) && $row !== null;

        if (!$ok) {
            RateLimit::hit($ipKey, 10, 900);
            RateLimit::hit($userKey, 20, 3600);
            flash('error', '帳號或密碼錯誤。');
            redirect('/admin/login?next=' . rawurlencode($next));
        }
        if (password_needs_rehash((string) $row['password_hash'], PASSWORD_DEFAULT)) {
            $row['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            DB::update('admins', ['password_hash' => $row['password_hash']], '`id` = ?', [(int) $row['id']]);
        }
        RateLimit::clear($ipKey);
        Auth::loginAdmin($row, Request::bool('remember'));
        redirect($next);
    }

    public function logout(): void
    {
        verify_csrf();
        Auth::logoutAdmin();
        Session::regenerate();
        flash('ok', '已登出後台。');
        redirect('/admin/login');
    }
}
