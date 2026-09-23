<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private static array|false|null $admin = null;
    private static array|false|null $member = null;

    public static function admin(): ?array
    {
        if (self::$admin === null) {
            self::$admin = false;
            Session::startIfExists();
            $id = (int) Session::get('admin_id', 0);
            if ($id > 0) {
                $ttl = (int) Session::get('admin_ttl', 43200);
                $last = (int) Session::get('admin_last', 0);
                $row = DB::one('SELECT `id`, `username`, `display_name`, `password_hash` FROM {admins} WHERE `id` = ?', [$id]);
                $stamp = $row ? substr(hash('sha256', (string) $row['password_hash']), 0, 16) : '';
                if (!$row || ($last > 0 && time() - $last > $ttl) || !hash_equals($stamp, (string) Session::get('admin_stamp', ''))) {
                    self::logoutAdmin();
                } else {
                    unset($row['password_hash']);
                    $row['id'] = (int) $row['id'];
                    self::$admin = $row;
                    Session::set('admin_last', time());
                }
            }
        }
        return self::$admin ?: null;
    }

    public static function loginAdmin(array $row, bool $remember): void
    {
        Session::regenerate();
        Session::set('admin_id', (int) $row['id']);
        Session::set('admin_ttl', $remember ? 1209600 : 43200); // 記住我：14 天；否則 12 小時未活動自動登出
        Session::set('admin_last', time());
        Session::set('admin_stamp', substr(hash('sha256', (string) $row['password_hash']), 0, 16));
        DB::update('admins', ['last_login_at' => DB::now()], '`id` = ?', [(int) $row['id']]);
        self::$admin = null;
    }

    public static function logoutAdmin(): void
    {
        Session::destroyKeys(['admin_id', 'admin_ttl', 'admin_last', 'admin_stamp']);
        self::$admin = false;
    }

    /** 更新密碼後刷新目前 session 的驗證戳記 */
    public static function refreshAdminStamp(string $passwordHash): void
    {
        Session::set('admin_stamp', substr(hash('sha256', $passwordHash), 0, 16));
    }

    public static function member(): ?array
    {
        if (self::$member === null) {
            self::$member = false;
            Session::startIfExists();
            $id = (int) Session::get('member_id', 0);
            if ($id > 0) {
                $row = DB::one('SELECT * FROM {members} WHERE `id` = ?', [$id]);
                if ($row && $row['status'] === 'active') {
                    $row['id'] = (int) $row['id'];
                    self::$member = $row;
                } else {
                    self::logoutMember();
                }
            }
        }
        return self::$member ?: null;
    }

    public static function loginMember(int $memberId): void
    {
        Session::regenerate();
        Session::set('member_id', $memberId);
        self::$member = null;
    }

    public static function logoutMember(): void
    {
        Session::forget('member_id');
        self::$member = false;
    }

    /** 後台頁面：未登入導向登入頁；API：回傳 401 */
    public static function requireAdmin(): array
    {
        $admin = self::admin();
        if ($admin) {
            return $admin;
        }
        if (str_starts_with(Request::path(), '/admin/api/') || Request::wantsJson()) {
            json_response(['ok' => false, 'error' => '登入逾時，請重新登入後台。', 'login' => true], 401);
        }
        redirect('/admin/login?next=' . rawurlencode(Request::path()));
    }
}
