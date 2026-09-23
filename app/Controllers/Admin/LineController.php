<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Crypto;
use App\Core\DB;
use App\Core\Http;
use App\Core\Request;
use App\Core\Settings;
use App\Services\LineBot;
use App\Services\Notifier;
use App\Services\OAuth;

/** LINE 整合：LINE Login、Messaging API、管理員通知、傳送訊息 */
final class LineController extends AdminBase
{
    public function index(): void
    {
        $bot = LineBot::config();
        $auth = OAuth::config();
        $bind = Settings::get('line_bind', []);
        $this->page('line', 'LINE 整合', [
            'bot' => $bot,
            'login' => $auth['line'],
            'secretMask' => [
                'login' => Crypto::mask(Crypto::decrypt($auth['line']['channelSecret'])),
                'bot' => Crypto::mask(LineBot::secret()),
                'token' => Crypto::mask(LineBot::token()),
            ],
            'configured' => LineBot::configured(),
            'webhookUrl' => absolute_url('/api/line/webhook'),
            'callbackUrl' => absolute_url('/auth/line/callback'),
            'webhookLast' => Settings::get('line_webhook_last'),
            'receivers' => LineBot::receivers(),
            'bind' => is_array($bind) && ($bind['expires'] ?? 0) > time() ? $bind : null,
            'friends' => LineBot::friendsCount(),
            'memberRecipients' => count(LineBot::memberRecipients()),
            'messages' => DB::all('SELECT m.*, c.`display_name` FROM {line_messages} m LEFT JOIN {line_contacts} c ON c.`user_id` = m.`user_id` ORDER BY m.`id` DESC LIMIT 40'),
            'addFriendUrl' => LineBot::addFriendUrl(),
        ], 'line');
    }

    public function saveSettings(): void
    {
        $this->api();
        $section = Request::str('section');
        if ($section === 'login') {
            $auth = OAuth::config();
            $line = $auth['line'];
            $line['enabled'] = Request::bool('enabled');
            $line['channelId'] = preg_replace('/\D/', '', Request::str('channelId')) ?? '';
            $secret = preg_replace('/\s+/', '', Request::str('channelSecret')) ?? '';
            if ($secret !== '') {
                $line['channelSecret'] = Crypto::encrypt($secret);
            }
            $line['botPrompt'] = in_array(Request::str('botPrompt'), ['aggressive', 'normal', 'none'], true) ? Request::str('botPrompt') : 'aggressive';
            $line['requestEmail'] = Request::bool('requestEmail');
            if ($line['enabled'] && ($line['channelId'] === '' || $line['channelSecret'] === '')) {
                json_response(['ok' => false, 'error' => '啟用前請填寫 Channel ID 與 Channel secret'], 422);
            }
            $auth['line'] = $line;
            Settings::set('auth', $auth);
        } elseif ($section === 'bot') {
            $bot = LineBot::config();
            $secret = preg_replace('/\s+/', '', Request::str('channelSecret')) ?? '';
            $token = preg_replace('/\s+/', '', Request::str('accessToken')) ?? '';
            if ($secret !== '') {
                $bot['channelSecret'] = Crypto::encrypt($secret);
            }
            if ($token !== '') {
                $bot['accessToken'] = Crypto::encrypt($token);
            }
            $basic = trim(Request::str('basicId'));
            if ($basic !== '' && !preg_match('/^@?[A-Za-z0-9._-]{2,40}$/', $basic)) {
                json_response(['ok' => false, 'error' => '官方帳號 ID 格式不正確（例如 @123abcde）'], 422);
            }
            $bot['basicId'] = $basic === '' ? '' : ($basic[0] === '@' ? $basic : '@' . $basic);
            Settings::set('line_bot', $bot);
        } elseif ($section === 'notify') {
            $bot = LineBot::config();
            $bot['notifyNewMember'] = Request::bool('notifyNewMember');
            $bot['notifyContact'] = Request::bool('notifyContact');
            $bot['notifyNewFriend'] = Request::bool('notifyNewFriend');
            Settings::set('line_bot', $bot);
        } else {
            json_response(['ok' => false, 'error' => '未知的設定區塊'], 400);
        }
        json_response(['ok' => true]);
    }

    /** 測試 Messaging API 連線並取得本月用量 */
    public function test(): void
    {
        $this->api();
        if (!LineBot::configured()) {
            json_response(['ok' => false, 'error' => '請先填寫 Channel secret 與 Channel access token'], 422);
        }
        $info = LineBot::botInfo();
        if (!$info['ok']) {
            json_response(['ok' => false, 'error' => '連線失敗：' . Http::errorMessage($info)], 422);
        }
        json_response([
            'ok' => true,
            'bot' => [
                'name' => $info['json']['displayName'] ?? '',
                'picture' => $info['json']['pictureUrl'] ?? '',
                'basicId' => $info['json']['basicId'] ?? '',
                'chatMode' => $info['json']['chatMode'] ?? '',
            ],
            'quota' => LineBot::quota(),
        ]);
    }

    /** 產生 6 位數綁定碼（10 分鐘內有效） */
    public function bindCode(): void
    {
        $this->api();
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Settings::set('line_bind', ['code' => $code, 'expires' => time() + 600]);
        json_response(['ok' => true, 'code' => $code, 'expires' => date('H:i', time() + 600)]);
    }

    public function removeReceiver(array $p): void
    {
        $this->api();
        DB::update('line_contacts', ['is_admin_receiver' => 0, 'updated_at' => DB::now()], '`user_id` = ?', [(string) $p['id']]);
        json_response(['ok' => true]);
    }

    public function notifyTest(): void
    {
        $this->api();
        $ok = Notifier::admins('🔔 這是來自 ' . Settings::site()['name'] . ' 後台的測試通知，看到這則訊息代表設定成功！');
        json_response($ok ? ['ok' => true] : ['ok' => false, 'error' => '傳送失敗，請確認已綁定管理員並設定 Messaging API（詳細錯誤請看下方訊息紀錄）'], $ok ? 200 : 422);
    }

    /** 傳送訊息：members＝已加好友且同意通知的會員；all＝官方帳號所有好友 */
    public function send(): void
    {
        $this->api();
        if (!LineBot::configured()) {
            json_response(['ok' => false, 'error' => '尚未設定 LINE Messaging API'], 422);
        }
        $text = trim(Request::str('text'));
        $image = trim(Request::str('image'));
        if ($text === '' && $image === '') {
            json_response(['ok' => false, 'error' => '請輸入訊息內容'], 422);
        }
        $messages = [];
        if ($image !== '') {
            $url = absolute_url(media_url($image) ?: $image);
            if (!str_starts_with($url, 'https://')) {
                json_response(['ok' => false, 'error' => 'LINE 圖片訊息需要 https 網址（請先為網站啟用 SSL）'], 422);
            }
            $messages[] = LineBot::image($url);
        }
        if ($text !== '') {
            $messages[] = LineBot::text($text);
        }
        if (Request::str('target') === 'all') {
            $res = LineBot::broadcast($messages, $this->admin['id']);
            json_response($res['ok'] ? ['ok' => true, 'message' => '已傳送給所有好友'] : ['ok' => false, 'error' => Http::errorMessage($res)], $res['ok'] ? 200 : 422);
        }
        $ids = LineBot::memberRecipients();
        if (!$ids) {
            json_response(['ok' => false, 'error' => '目前沒有已加好友且同意接收通知的會員'], 422);
        }
        $res = LineBot::multicast($ids, $messages, $this->admin['id']);
        json_response($res['ok'] ? ['ok' => true, 'message' => '已傳送給 ' . $res['count'] . ' 位會員'] : ['ok' => false, 'error' => (string) $res['error']], $res['ok'] ? 200 : 422);
    }
}
