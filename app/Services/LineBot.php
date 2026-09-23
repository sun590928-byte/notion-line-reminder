<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Crypto;
use App\Core\DB;
use App\Core\Http;
use App\Core\Settings;

/**
 * LINE Messaging API：推播、群發、回覆、好友資料同步。
 * 注意：LINE Login Channel 與 Messaging API Channel 必須在同一個 Provider 底下，會員的 userId 才會一致。
 */
final class LineBot
{
    private const API = 'https://api.line.me/v2/bot';

    public static function config(): array
    {
        $c = Settings::get('line_bot', []);
        return array_replace([
            'channelSecret' => '',
            'accessToken' => '',
            'basicId' => '',
            'notifyNewMember' => true,
            'notifyContact' => true,
            'notifyNewFriend' => false,
        ], is_array($c) ? $c : []);
    }

    public static function secret(): string
    {
        return Crypto::decrypt(self::config()['channelSecret']);
    }

    public static function token(): string
    {
        return Crypto::decrypt(self::config()['accessToken']);
    }

    public static function configured(): bool
    {
        return self::token() !== '' && self::secret() !== '';
    }

    /** 加入好友連結，例如 https://line.me/R/ti/p/@123abcde */
    public static function addFriendUrl(): string
    {
        $id = trim((string) self::config()['basicId']);
        if ($id === '' || !preg_match('/^@?[A-Za-z0-9._-]{2,40}$/', $id)) {
            return '';
        }
        return 'https://line.me/R/ti/p/' . ($id[0] === '@' ? $id : '@' . $id);
    }

    /** 驗證 Webhook 簽章（X-Line-Signature = Base64(HMAC-SHA256(channel secret, body))） */
    public static function verifySignature(string $body, string $signature): bool
    {
        $secret = self::secret();
        if ($secret === '' || $signature === '') {
            return false;
        }
        return hash_equals(base64_encode(hash_hmac('sha256', $body, $secret, true)), $signature);
    }

    private static function api(string $method, string $path, ?array $json = null): array
    {
        $opt = ['headers' => ['Authorization: Bearer ' . self::token()], 'timeout' => 20];
        if ($json !== null) {
            $opt['json'] = $json;
        }
        return Http::request($method, self::API . $path, $opt);
    }

    public static function text(string $text): array
    {
        return ['type' => 'text', 'text' => mb_substr($text, 0, 5000)];
    }

    public static function image(string $url): array
    {
        return ['type' => 'image', 'originalContentUrl' => $url, 'previewImageUrl' => $url];
    }

    public static function push(string $to, array $messages, int $adminId = 0): array
    {
        $res = self::api('POST', '/message/push', ['to' => $to, 'messages' => $messages]);
        self::logOut('user', $to, $messages, $res, $adminId);
        return $res;
    }

    /** 一次傳給多位好友（每批最多 500 人） */
    public static function multicast(array $userIds, array $messages, int $adminId = 0): array
    {
        $userIds = array_values(array_unique(array_filter($userIds)));
        $ok = true;
        $errors = [];
        foreach (array_chunk($userIds, 500) as $chunk) {
            $res = self::api('POST', '/message/multicast', ['to' => $chunk, 'messages' => $messages]);
            self::logOut('multicast', null, $messages, $res, $adminId, count($chunk) . ' 人');
            if (!$res['ok']) {
                $ok = false;
                $errors[] = Http::errorMessage($res);
            }
        }
        return ['ok' => $ok, 'count' => count($userIds), 'error' => $errors ? implode('；', $errors) : null];
    }

    /** 傳給官方帳號的所有好友 */
    public static function broadcast(array $messages, int $adminId = 0): array
    {
        $res = self::api('POST', '/message/broadcast', ['messages' => $messages]);
        self::logOut('broadcast', null, $messages, $res, $adminId, '所有好友');
        return $res;
    }

    public static function reply(string $replyToken, array $messages): array
    {
        if ($replyToken === '') {
            return ['ok' => false];
        }
        return self::api('POST', '/message/reply', ['replyToken' => $replyToken, 'messages' => $messages]);
    }

    public static function profile(string $userId): ?array
    {
        $res = self::api('GET', '/profile/' . rawurlencode($userId));
        return $res['ok'] ? $res['json'] : null;
    }

    public static function botInfo(): array
    {
        return self::api('GET', '/info');
    }

    /** 本月訊息用量（推播／群發會計入官方帳號方案的免費則數） */
    public static function quota(): ?array
    {
        $q = self::api('GET', '/message/quota');
        $c = self::api('GET', '/message/quota/consumption');
        if (!$q['ok']) {
            return null;
        }
        return [
            'type' => $q['json']['type'] ?? '',
            'limit' => $q['json']['value'] ?? null,
            'used' => $c['json']['totalUsage'] ?? null,
        ];
    }

    private static function logOut(string $target, ?string $userId, array $messages, array $res, int $adminId, string $label = ''): void
    {
        $summary = implode(' / ', array_map(static fn ($m) => $m['type'] === 'text' ? $m['text'] : '[' . $m['type'] . ']', $messages));
        DB::insert('line_messages', [
            'direction' => 'out',
            'user_id' => $userId,
            'target' => $target,
            'msg_type' => (string) ($messages[0]['type'] ?? 'text'),
            'text' => mb_substr(($label !== '' ? "［{$label}］" : '') . $summary, 0, 5000),
            'status' => $res['ok'] ? 'sent' : 'failed',
            'error' => $res['ok'] ? null : mb_substr(Http::errorMessage($res), 0, 1000),
            'admin_id' => $adminId,
            'created_at' => DB::now(),
        ]);
    }

    // ── 好友名單 ─────────────────────────────

    public static function contact(string $userId): ?array
    {
        return DB::one('SELECT * FROM {line_contacts} WHERE `user_id` = ?', [$userId]);
    }

    /** 會員用 LINE 登入時，把好友資料與會員連結 */
    public static function linkContact(string $userId, int $memberId, array $profile): void
    {
        $row = self::contact($userId);
        $data = ['member_id' => $memberId, 'updated_at' => DB::now()];
        if (!empty($profile['name'])) {
            $data['display_name'] = mb_substr((string) $profile['name'], 0, 191);
        }
        if ($avatar = Members::safeAvatar($profile['avatar'] ?? null)) {
            $data['picture_url'] = $avatar;
        }
        if (isset($profile['friend']) && $profile['friend'] !== null) {
            $data['is_friend'] = $profile['friend'] ? 1 : 0;
            if ($profile['friend'] && (!$row || !(int) $row['is_friend'])) {
                $data['followed_at'] = DB::now();
            }
        }
        if ($row) {
            DB::update('line_contacts', $data, '`user_id` = ?', [$userId]);
        } else {
            DB::insert('line_contacts', ['user_id' => $userId, 'created_at' => DB::now()] + $data);
        }
    }

    /** 確保聯絡人存在（第一次互動時向 LINE 取得名稱與頭像） */
    public static function touchContact(string $userId, array $extra = []): array
    {
        $row = self::contact($userId);
        $data = $extra + ['updated_at' => DB::now()];
        if (!$row || empty($row['display_name'])) {
            $p = self::profile($userId);
            if ($p) {
                $data['display_name'] = mb_substr((string) ($p['displayName'] ?? ''), 0, 191);
                $data['picture_url'] = Members::safeAvatar($p['pictureUrl'] ?? null);
            }
        }
        $memberId = Members::findByIdentity('line', $userId);
        if ($memberId) {
            $data['member_id'] = $memberId;
        }
        if ($row) {
            DB::update('line_contacts', $data, '`user_id` = ?', [$userId]);
        } else {
            DB::insert('line_contacts', ['user_id' => $userId, 'created_at' => DB::now()] + $data);
        }
        return self::contact($userId) ?? [];
    }

    public static function receivers(): array
    {
        return DB::all('SELECT * FROM {line_contacts} WHERE `is_admin_receiver` = 1 ORDER BY `updated_at` DESC');
    }

    public static function friendsCount(): int
    {
        return (int) DB::value('SELECT COUNT(*) FROM {line_contacts} WHERE `is_friend` = 1');
    }

    /** 已加好友且願意接收通知的會員 LINE userId */
    public static function memberRecipients(): array
    {
        return array_column(DB::all(
            "SELECT c.`user_id` FROM {line_contacts} c JOIN {members} m ON m.`id` = c.`member_id` WHERE c.`is_friend` = 1 AND m.`notify_line` = 1 AND m.`status` = 'active'"
        ), 'user_id');
    }
}
