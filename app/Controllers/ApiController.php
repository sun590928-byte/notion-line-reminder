<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Crypto;
use App\Core\DB;
use App\Core\RateLimit;
use App\Core\Request;
use App\Core\View;
use App\Services\Documents;
use App\Services\Notifier;

/** 公開 API：連結點擊統計、聯絡表單 */
final class ApiController
{
    public function click(): void
    {
        $data = json_decode(Request::raw(), true);
        $id = is_array($data) ? (string) ($data['id'] ?? '') : '';
        if (!preg_match('/^[A-Za-z0-9_-]{3,40}$/', $id)) {
            json_response(['ok' => false], 400);
        }
        if (Request::isBot()) {
            json_response(['ok' => true]);
        }
        if (!RateLimit::hit('click:' . client_hash(), 120, 600)) {
            json_response(['ok' => false], 429);
        }
        $links = Documents::published('links')['links'] ?? [];
        if (!in_array($id, array_column($links, 'id'), true)) {
            json_response(['ok' => false], 404);
        }
        DB::bumpStat('click', $id);
        json_response(['ok' => true]);
    }

    public function contact(): void
    {
        // 蜜罐欄位：機器人會填寫，直接假裝成功
        if (Request::str('website') !== '') {
            $this->respond(true, '已送出');
        }
        [$ts, $sig] = array_pad(explode('.', Request::str('token'), 2), 2, '');
        if (!ctype_digit($ts) || !hash_equals(substr(Crypto::sign('form|' . $ts), 0, 32), $sig)) {
            $this->respond(false, '表單已過期，請重新整理頁面後再試一次。', 400);
        }
        $age = time() - (int) $ts;
        if ($age < 3) {
            $this->respond(false, '送出速度太快了，請稍候再試。', 400);
        }
        if ($age > 172800) {
            $this->respond(false, '表單已過期，請重新整理頁面後再試一次。', 400);
        }
        $name = mb_substr(Request::str('name'), 0, 100);
        $email = mb_substr(Request::str('email'), 0, 191);
        $phone = mb_substr(preg_replace('/[^0-9+#\-\s()]/', '', Request::str('phone')) ?? '', 0, 50);
        $message = mb_substr(str_replace("\r\n", "\n", Request::str('message')), 0, 5000);
        if ($name === '' || $message === '') {
            $this->respond(false, '請填寫姓名與訊息內容。', 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->respond(false, 'Email 格式不正確。', 422);
        }
        // 同一來源 15 分鐘最多 5 則、一天最多 20 則
        $source = client_hash();
        if (!RateLimit::hit('contact:' . $source, 5, 900) || !RateLimit::hit('contact:day:' . $source, 20, 86400)) {
            $this->respond(false, '送出次數太多，請稍後再試。', 429);
        }
        $member = Auth::member();
        $row = [
            'form' => 'contact',
            'page' => mb_substr(Request::str('page'), 0, 191),
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'message' => $message,
            'data' => null,
            'member_id' => $member['id'] ?? 0,
            'is_read' => 0,
            'ip_hash' => $source,
            'created_at' => DB::now(),
        ];
        DB::insert('form_submissions', $row);
        Notifier::contact($row);
        $this->respond(true, '已送出，謝謝你的訊息！');
    }

    private function respond(bool $ok, string $message, int $status = 200): never
    {
        if (Request::wantsJson()) {
            json_response($ok ? ['ok' => true] : ['ok' => false, 'error' => $message], $status);
        }
        http_response_code($status);
        echo View::render('errors/error', ['code' => $ok ? '✓' : $status, 'message' => $message, 'title' => $message], 'bare');
        exit;
    }
}
