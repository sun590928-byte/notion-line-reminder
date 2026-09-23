<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;

/** 會員資料：一位會員可以同時綁定 LINE 與 Google */
final class Members
{
    public static function find(int $id): ?array
    {
        $row = DB::one('SELECT * FROM {members} WHERE `id` = ?', [$id]);
        if ($row) {
            $row['id'] = (int) $row['id'];
        }
        return $row;
    }

    public static function findByIdentity(string $provider, string $uid): ?int
    {
        $id = DB::value('SELECT `member_id` FROM {member_identities} WHERE `provider` = ? AND `provider_uid` = ?', [$provider, $uid]);
        return $id !== null ? (int) $id : null;
    }

    public static function identities(int $memberId): array
    {
        $rows = DB::all('SELECT * FROM {member_identities} WHERE `member_id` = ? ORDER BY `id`', [$memberId]);
        $out = [];
        foreach ($rows as $r) {
            $out[$r['provider']] = $r;
        }
        return $out;
    }

    public static function lineUserId(int $memberId): ?string
    {
        $uid = DB::value("SELECT `provider_uid` FROM {member_identities} WHERE `member_id` = ? AND `provider` = 'line'", [$memberId]);
        return $uid !== null ? (string) $uid : null;
    }

    /** 以第三方登入資料建立新會員 */
    public static function create(string $provider, array $profile): int
    {
        return DB::transaction(static function () use ($provider, $profile) {
            $id = DB::insert('members', [
                'display_name' => mb_substr($profile['name'] ?: '會員', 0, 191),
                'email' => $profile['email'] ? mb_substr($profile['email'], 0, 191) : null,
                'avatar_url' => self::safeAvatar($profile['avatar'] ?? null),
                'status' => 'active',
                'notify_line' => 1,
                'login_count' => 0,
                'created_at' => DB::now(),
            ]);
            self::attachIdentity($id, $provider, $profile);
            return $id;
        });
    }

    public static function attachIdentity(int $memberId, string $provider, array $profile): void
    {
        DB::upsert('member_identities', ['provider' => $provider, 'provider_uid' => $profile['uid']], [
            'member_id' => $memberId,
            'email' => $profile['email'] ? mb_substr($profile['email'], 0, 191) : null,
            'display_name' => mb_substr((string) $profile['name'], 0, 191),
            'avatar_url' => self::safeAvatar($profile['avatar'] ?? null),
            'last_used_at' => DB::now(),
        ]);
        DB::run('UPDATE {member_identities} SET `created_at` = ? WHERE `provider` = ? AND `provider_uid` = ? AND `created_at` IS NULL', [DB::now(), $provider, $profile['uid']]);
        // 會員資料缺少的欄位用第三方資料補上
        $m = self::find($memberId);
        if ($m) {
            $patch = [];
            if (empty($m['email']) && !empty($profile['email'])) {
                $patch['email'] = mb_substr($profile['email'], 0, 191);
            }
            if (empty($m['avatar_url']) && self::safeAvatar($profile['avatar'] ?? null)) {
                $patch['avatar_url'] = self::safeAvatar($profile['avatar']);
            }
            if ($patch) {
                DB::update('members', $patch, '`id` = ?', [$memberId]);
            }
        }
        if ($provider === 'line') {
            LineBot::linkContact($profile['uid'], $memberId, $profile);
        }
    }

    public static function detachIdentity(int $memberId, string $provider): bool
    {
        if (count(self::identities($memberId)) <= 1) {
            return false;
        }
        if ($provider === 'line') {
            DB::run("UPDATE {line_contacts} SET `member_id` = 0 WHERE `member_id` = ?", [$memberId]);
        }
        DB::delete('member_identities', '`member_id` = ? AND `provider` = ?', [$memberId, $provider]);
        return true;
    }

    public static function recordLogin(int $memberId): void
    {
        DB::run('UPDATE {members} SET `login_count` = `login_count` + 1, `last_login_at` = ? WHERE `id` = ?', [DB::now(), $memberId]);
    }

    public static function delete(int $memberId): void
    {
        DB::transaction(static function () use ($memberId) {
            DB::run('UPDATE {line_contacts} SET `member_id` = 0 WHERE `member_id` = ?', [$memberId]);
            DB::delete('member_identities', '`member_id` = ?', [$memberId]);
            DB::delete('members', '`id` = ?', [$memberId]);
        });
    }

    /** 只接受 https 的頭像網址 */
    public static function safeAvatar(?string $url): ?string
    {
        $url = trim((string) $url);
        return $url !== '' && preg_match('#^https://#i', $url) && strlen($url) <= 500 ? $url : null;
    }
}
