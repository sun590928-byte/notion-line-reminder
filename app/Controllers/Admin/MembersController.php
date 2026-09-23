<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\DB;
use App\Core\Http;
use App\Core\Request;
use App\Services\LineBot;
use App\Services\Members;

final class MembersController extends AdminBase
{
    private const PER_PAGE = 30;

    public function index(): void
    {
        $q = trim(Request::query('q'));
        $provider = Request::query('provider');
        $page = max(1, (int) Request::query('page', '1'));
        $where = ['1 = 1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(m.`display_name` LIKE ? OR m.`email` LIKE ?)';
            $params[] = '%' . $q . '%';
            $params[] = '%' . $q . '%';
        }
        if (in_array($provider, ['line', 'google'], true)) {
            $where[] = 'EXISTS (SELECT 1 FROM {member_identities} i WHERE i.`member_id` = m.`id` AND i.`provider` = ?)';
            $params[] = $provider;
        }
        $whereSql = implode(' AND ', $where);
        $total = (int) DB::value("SELECT COUNT(*) FROM {members} m WHERE {$whereSql}", $params);
        $rows = DB::all(
            "SELECT m.*, c.`is_friend`,
                (SELECT GROUP_CONCAT(i.`provider`) FROM {member_identities} i WHERE i.`member_id` = m.`id`) AS providers
             FROM {members} m LEFT JOIN {line_contacts} c ON c.`member_id` = m.`id`
             WHERE {$whereSql} ORDER BY m.`id` DESC LIMIT " . self::PER_PAGE . ' OFFSET ' . (($page - 1) * self::PER_PAGE),
            $params
        );
        $this->page('members', '會員', [
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / self::PER_PAGE)),
            'q' => $q,
            'provider' => $provider,
            'lineReady' => LineBot::configured(),
        ], 'members');
    }

    public function show(array $p): void
    {
        $member = Members::find((int) $p['id']) ?? abort(404, '找不到這位會員');
        $identities = Members::identities($member['id']);
        $lineUid = $identities['line']['provider_uid'] ?? null;
        $this->page('member', $member['display_name'], [
            'member' => $member,
            'identities' => $identities,
            'contact' => $lineUid ? LineBot::contact($lineUid) : null,
            'messages' => $lineUid ? DB::all('SELECT * FROM {line_messages} WHERE `user_id` = ? ORDER BY `id` DESC LIMIT 30', [$lineUid]) : [],
            'forms' => DB::all('SELECT * FROM {form_submissions} WHERE `member_id` = ? ORDER BY `id` DESC LIMIT 20', [$member['id']]),
            'lineReady' => LineBot::configured(),
        ], 'members');
    }

    public function update(array $p): void
    {
        $this->api();
        $id = (int) $p['id'];
        $data = [];
        if (Request::input('status') !== null) {
            $data['status'] = Request::str('status') === 'blocked' ? 'blocked' : 'active';
        }
        if (Request::input('note') !== null) {
            $data['note'] = mb_substr(Request::str('note'), 0, 2000);
        }
        if (Request::input('notify_line') !== null) {
            $data['notify_line'] = Request::bool('notify_line') ? 1 : 0;
        }
        if ($data) {
            DB::update('members', $data, '`id` = ?', [$id]);
        }
        json_response(['ok' => true]);
    }

    public function delete(array $p): void
    {
        $this->api();
        Members::delete((int) $p['id']);
        json_response(['ok' => true]);
    }

    /** 以 LINE 傳訊息給單一會員 */
    public function message(array $p): void
    {
        $this->api();
        $text = trim(Request::str('text'));
        if ($text === '') {
            json_response(['ok' => false, 'error' => '請輸入訊息內容'], 422);
        }
        if (!LineBot::configured()) {
            json_response(['ok' => false, 'error' => '尚未設定 LINE Messaging API'], 422);
        }
        $uid = Members::lineUserId((int) $p['id']);
        if (!$uid) {
            json_response(['ok' => false, 'error' => '這位會員沒有綁定 LINE'], 422);
        }
        $res = LineBot::push($uid, [LineBot::text($text)], $this->admin['id']);
        json_response($res['ok'] ? ['ok' => true] : ['ok' => false, 'error' => '傳送失敗：' . Http::errorMessage($res) . '（對方需先加入官方帳號好友）'], $res['ok'] ? 200 : 422);
    }
}
