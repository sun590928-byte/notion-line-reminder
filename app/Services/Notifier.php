<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use App\Core\ErrorHandler;

/** 管理員 LINE 通知（新會員、新留言、新好友）。通知失敗不影響使用者操作。 */
final class Notifier
{
    public static function admins(string $text): bool
    {
        try {
            if (!LineBot::configured()) {
                return false;
            }
            $ids = array_column(DB::all('SELECT `user_id` FROM {line_contacts} WHERE `is_admin_receiver` = 1'), 'user_id');
            if (!$ids) {
                return false;
            }
            $msg = [LineBot::text($text)];
            $res = count($ids) === 1 ? LineBot::push($ids[0], $msg) : LineBot::multicast($ids, $msg);
            return (bool) $res['ok'];
        } catch (\Throwable $e) {
            ErrorHandler::log($e);
            return false;
        }
    }

    public static function newMember(int $memberId, string $provider): void
    {
        if (!LineBot::config()['notifyNewMember']) {
            return;
        }
        $m = Members::find($memberId);
        if (!$m) {
            return;
        }
        self::admins("🌙 新會員加入\n" . $m['display_name'] . '（' . (OAuth::LABELS[$provider] ?? $provider) . " 登入）\n" . absolute_url('/admin/members/' . $memberId));
    }

    public static function contact(array $s): void
    {
        if (!LineBot::config()['notifyContact']) {
            return;
        }
        $lines = ['✉️ 網站有新的留言', '姓名：' . $s['name'], 'Email：' . $s['email']];
        if (($s['phone'] ?? '') !== '') {
            $lines[] = '電話：' . $s['phone'];
        }
        $lines[] = '';
        $lines[] = str_limit((string) $s['message'], 400);
        $lines[] = '';
        $lines[] = absolute_url('/admin/forms');
        self::admins(implode("\n", $lines));
    }

    public static function newFriend(array $contact): void
    {
        if (!LineBot::config()['notifyNewFriend']) {
            return;
        }
        self::admins('💚 LINE 官方帳號新增好友：' . (($contact['display_name'] ?? '') !== '' ? $contact['display_name'] : '（未知名稱）'));
    }
}
