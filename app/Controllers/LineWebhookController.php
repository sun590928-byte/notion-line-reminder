<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\DB;
use App\Core\ErrorHandler;
use App\Core\RateLimit;
use App\Core\Request;
use App\Core\Settings;
use App\Services\LineBot;
use App\Services\Notifier;

/**
 * LINE Messaging API Webhook：https://你的網域/api/line/webhook
 *  - follow / unfollow：同步好友狀態
 *  - message：記錄訊息；處理「綁定 123456」管理員通知綁定指令
 */
final class LineWebhookController
{
    public function handle(): void
    {
        $body = Request::raw();
        if (!LineBot::verifySignature($body, (string) Request::header('X-Line-Signature'))) {
            ErrorHandler::log('LINE Webhook 簽章驗證失敗（請確認 Channel secret 是否正確）');
            json_response(['ok' => false, 'error' => 'invalid signature'], 401);
        }
        Settings::set('line_webhook_last', date('Y-m-d H:i:s'));
        $payload = json_decode($body, true);
        foreach ((array) ($payload['events'] ?? []) as $event) {
            try {
                if (is_array($event)) {
                    $this->event($event);
                }
            } catch (\Throwable $e) {
                ErrorHandler::log($e);
            }
        }
        json_response(['ok' => true]);
    }

    private function event(array $ev): void
    {
        $type = (string) ($ev['type'] ?? '');
        $userId = (string) ($ev['source']['userId'] ?? '');
        $eventId = isset($ev['webhookEventId']) ? (string) $ev['webhookEventId'] : null;
        if ($userId === '' || ($ev['source']['type'] ?? '') !== 'user') {
            return;
        }
        // LINE 重送的事件只處理一次
        if ($eventId !== null && DB::value('SELECT 1 FROM {line_messages} WHERE `event_id` = ?', [$eventId])) {
            return;
        }

        switch ($type) {
            case 'follow':
                $contact = LineBot::touchContact($userId, ['is_friend' => 1, 'followed_at' => DB::now()]);
                $this->log($eventId, $userId, 'follow', '加入好友');
                Notifier::newFriend($contact);
                break;
            case 'unfollow':
                LineBot::touchContact($userId, ['is_friend' => 0, 'unfollowed_at' => DB::now()]);
                $this->log($eventId, $userId, 'unfollow', '封鎖／刪除好友');
                break;
            case 'message':
                $msg = (array) ($ev['message'] ?? []);
                $msgType = (string) ($msg['type'] ?? 'text');
                $text = $msgType === 'text' ? (string) ($msg['text'] ?? '') : '[' . $msgType . ']';
                LineBot::touchContact($userId, ['is_friend' => 1, 'last_message_at' => DB::now()]);
                $this->log($eventId, $userId, $msgType, $text);
                if ($msgType === 'text') {
                    $this->command($userId, $text, (string) ($ev['replyToken'] ?? ''));
                }
                break;
            case 'postback':
                $this->log($eventId, $userId, 'postback', (string) ($ev['postback']['data'] ?? ''));
                break;
        }
    }

    /** 管理員通知綁定：傳送「綁定 123456」給官方帳號 */
    private function command(string $userId, string $text, string $replyToken): void
    {
        $t = trim((string) preg_replace('/\s+/u', ' ', $text));
        if (preg_match('/^(?:綁定|bind)\s*[:：]?\s*(\d{6})$/iu', $t, $m)) {
            // 防止猜測綁定碼：每位使用者 10 分鐘內最多嘗試 5 次，累計錯誤 10 次綁定碼即作廢
            if (!RateLimit::hit('bind:' . $userId, 5, 600)) {
                LineBot::reply($replyToken, [LineBot::text('嘗試次數太多，請 10 分鐘後再試。')]);
                return;
            }
            $bind = Settings::get('line_bind', []);
            $bind = is_array($bind) ? $bind : [];
            $valid = ($bind['code'] ?? '') !== '' && (int) ($bind['expires'] ?? 0) >= time();
            if ($valid && hash_equals((string) $bind['code'], $m[1])) {
                DB::update('line_contacts', ['is_admin_receiver' => 1, 'updated_at' => DB::now()], '`user_id` = ?', [$userId]);
                Settings::set('line_bind', []);
                LineBot::reply($replyToken, [LineBot::text("✅ 綁定成功！\n之後網站有新會員或新留言時，會在這裡通知你。\n\n若要停止通知，請傳送「解除綁定」。")]);
            } else {
                if ($valid) {
                    $bind['fails'] = (int) ($bind['fails'] ?? 0) + 1;
                    Settings::set('line_bind', $bind['fails'] >= 10 ? [] : $bind);
                }
                LineBot::reply($replyToken, [LineBot::text('綁定碼錯誤或已過期，請到網站後台「LINE 整合」重新產生。')]);
            }
            return;
        }
        if (preg_match('/^(?:解除綁定|unbind)$/iu', $t)) {
            $contact = LineBot::contact($userId);
            if ($contact && (int) $contact['is_admin_receiver'] === 1) {
                DB::update('line_contacts', ['is_admin_receiver' => 0, 'updated_at' => DB::now()], '`user_id` = ?', [$userId]);
                LineBot::reply($replyToken, [LineBot::text('已停止傳送網站管理通知。')]);
            }
        }
    }

    private function log(?string $eventId, string $userId, string $type, string $text): void
    {
        DB::insert('line_messages', [
            'event_id' => $eventId,
            'direction' => 'in',
            'user_id' => $userId,
            'target' => 'user',
            'msg_type' => mb_substr($type, 0, 20),
            'text' => mb_substr($text, 0, 5000),
            'status' => 'received',
            'error' => null,
            'admin_id' => 0,
            'created_at' => DB::now(),
        ]);
    }
}
