<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\DB;
use App\Core\Request;

final class AccountController extends AdminBase
{
    public function index(): void
    {
        $this->page('account', '管理員帳號', [
            'admins' => DB::all('SELECT `id`, `username`, `display_name`, `last_login_at`, `created_at` FROM {admins} ORDER BY `id`'),
        ], 'account');
    }

    public function password(): void
    {
        $this->api();
        $row = DB::one('SELECT * FROM {admins} WHERE `id` = ?', [$this->admin['id']]);
        if (!$row || !password_verify((string) Request::input('current', ''), (string) $row['password_hash'])) {
            json_response(['ok' => false, 'error' => '目前的密碼不正確'], 422);
        }
        $new = (string) Request::input('password', '');
        if (strlen($new) < 8) {
            json_response(['ok' => false, 'error' => '新密碼至少 8 個字元'], 422);
        }
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $name = mb_substr(Request::str('display_name'), 0, 100);
        DB::update('admins', ['password_hash' => $hash] + ($name !== '' ? ['display_name' => $name] : []), '`id` = ?', [$this->admin['id']]);
        Auth::refreshAdminStamp($hash);
        json_response(['ok' => true]);
    }

    public function create(): void
    {
        $this->api();
        $username = Request::str('username');
        $password = (string) Request::input('password', '');
        if (!preg_match('/^[A-Za-z0-9_.@-]{3,50}$/', $username)) {
            json_response(['ok' => false, 'error' => '帳號需為 3–50 個英文、數字或 _ . @ -'], 422);
        }
        if (strlen($password) < 8) {
            json_response(['ok' => false, 'error' => '密碼至少 8 個字元'], 422);
        }
        if (DB::value('SELECT 1 FROM {admins} WHERE `username` = ?', [$username])) {
            json_response(['ok' => false, 'error' => '這個帳號已經存在'], 422);
        }
        DB::insert('admins', [
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'display_name' => mb_substr(Request::str('display_name') ?: $username, 0, 100),
            'created_at' => DB::now(),
        ]);
        json_response(['ok' => true]);
    }

    public function delete(array $p): void
    {
        $this->api();
        $id = (int) $p['id'];
        if ($id === $this->admin['id']) {
            json_response(['ok' => false, 'error' => '不能刪除自己的帳號'], 422);
        }
        if ((int) DB::value('SELECT COUNT(*) FROM {admins}') <= 1) {
            json_response(['ok' => false, 'error' => '至少需要保留一位管理員'], 422);
        }
        DB::delete('admins', '`id` = ?', [$id]);
        json_response(['ok' => true]);
    }
}
