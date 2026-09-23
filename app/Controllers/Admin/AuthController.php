<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Crypto;
use App\Core\DB;
use App\Core\RateLimit;
use App\Core\Request;
use App\Core\Session;
use App\Core\Settings;
use App\Core\View;

/** 後台登入／登出 */
final class AuthController
{
    private const DEVICE_COOKIE = 'amf_device';

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
        $username = mb_substr(trim(Request::str('username')), 0, 100);
        $password = (string) Request::input('password', '');
        $next = safe_return(Request::str('next'), '/admin');
        $back = '/admin/login?next=' . rawurlencode($next);

        // 同一來源（IPv6 以 /64 網段計）15 分鐘內最多失敗 10 次
        $ipKey = 'login:ip:' . Request::ipGroup();
        if (RateLimit::blocked($ipKey, 10)) {
            flash('error', '登入失敗次數太多，請 15 分鐘後再試。');
            redirect($back);
        }

        $row = $username === '' ? null : DB::one('SELECT * FROM {admins} WHERE `username` = ?', [$username]);
        // 帳號層級的限制以比對到的帳號 ID 計算（資料庫比對不分大小寫與全半形，不能用輸入的字串當鍵）。
        // 曾在這個瀏覽器成功登入的管理員另外計算，別人故意輸錯密碼也鎖不住本人。
        $device = $row ? self::trustedDevice((int) $row['id']) : null;
        $acctKey = $device !== null
            ? 'login:device:' . $device
            : 'login:acct:' . ($row ? 'id:' . $row['id'] : 'name:' . mb_strtolower($username));
        $acctMax = $device !== null ? 10 : 20;
        if (RateLimit::blocked($acctKey, $acctMax)) {
            flash('error', '這個帳號登入失敗次數太多，已暫時鎖定，請 1 小時後再試。');
            redirect($back);
        }

        // 帳號不存在時也做一次同樣成本的雜湊比對，避免從回應時間猜出帳號
        $ok = password_verify($password, $row ? (string) $row['password_hash'] : self::dummyHash()) && $row !== null;
        if (!$ok) {
            RateLimit::hit($ipKey, 10, 900);
            RateLimit::hit($acctKey, $acctMax, 3600);
            flash('error', '帳號或密碼錯誤。');
            redirect($back);
        }
        if (password_needs_rehash((string) $row['password_hash'], PASSWORD_DEFAULT)) {
            $row['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            DB::update('admins', ['password_hash' => $row['password_hash']], '`id` = ?', [(int) $row['id']]);
        }
        RateLimit::clear($ipKey);
        RateLimit::clear($acctKey);
        self::rememberDevice((int) $row['id'], $device);
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

    /** 這個瀏覽器若曾以該管理員成功登入，回傳裝置代碼（受信任裝置有自己的失敗次數額度） */
    private static function trustedDevice(int $adminId): ?string
    {
        $parts = explode('.', (string) ($_COOKIE[self::DEVICE_COOKIE] ?? ''));
        if (count($parts) !== 3 || $parts[0] !== (string) $adminId || !preg_match('/^[a-f0-9]{24}$/', $parts[1])) {
            return null;
        }
        return hash_equals(Crypto::sign('device|' . $parts[0] . '|' . $parts[1]), $parts[2]) ? $parts[1] : null;
    }

    private static function rememberDevice(int $adminId, ?string $device): void
    {
        $device ??= Crypto::token(12);
        $value = $adminId . '.' . $device . '.' . Crypto::sign('device|' . $adminId . '|' . $device);
        Session::cookie(self::DEVICE_COOKIE, $value, time() + 31536000, '/admin');
    }

    /** 與目前密碼雜湊相同成本的假雜湊（PHP 預設成本改變時自動更新） */
    private static function dummyHash(): string
    {
        $hash = Settings::get('auth_dummy_hash');
        if (!is_string($hash) || password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $hash = password_hash(Crypto::token(16), PASSWORD_DEFAULT);
            Settings::set('auth_dummy_hash', $hash);
        }
        return $hash;
    }
}
