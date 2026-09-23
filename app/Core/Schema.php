<?php
declare(strict_types=1);

namespace App\Core;

/**
 * 資料表定義與建立（同一份定義同時產生 MySQL 與 SQLite 語法）。
 * 欄位型別：pk | str:長度 | text | longtext | int | bool | datetime，結尾加 ? 表示可為 NULL。
 */
final class Schema
{
    public const VERSION = 1;

    public static function tables(): array
    {
        return [
            'settings' => [
                'name' => 'str:100',
                'value' => 'longtext?',
                'updated_at' => 'datetime?',
                '_pk' => ['name'],
            ],
            'documents' => [
                'name' => 'str:50',
                'draft' => 'longtext?',
                'published' => 'longtext?',
                'rev' => 'int',
                'updated_at' => 'datetime?',
                'published_at' => 'datetime?',
                'published_by' => 'int',
                '_pk' => ['name'],
            ],
            'revisions' => [
                'id' => 'pk',
                'doc' => 'str:50',
                'data' => 'longtext?',
                'note' => 'str:255',
                'admin_id' => 'int',
                'created_at' => 'datetime?',
                '_index' => [['doc', 'id']],
            ],
            'admins' => [
                'id' => 'pk',
                'username' => 'str:100',
                'password_hash' => 'str:255',
                'display_name' => 'str:100',
                'last_login_at' => 'datetime?',
                'created_at' => 'datetime?',
                '_unique' => [['username']],
            ],
            'members' => [
                'id' => 'pk',
                'display_name' => 'str:191',
                'email' => 'str:191?',
                'avatar_url' => 'str:500?',
                'status' => 'str:20',
                'notify_line' => 'bool',
                'note' => 'text?',
                'login_count' => 'int',
                'created_at' => 'datetime?',
                'last_login_at' => 'datetime?',
                '_index' => [['email'], ['created_at']],
            ],
            'member_identities' => [
                'id' => 'pk',
                'member_id' => 'int',
                'provider' => 'str:20',
                'provider_uid' => 'str:150',
                'email' => 'str:191?',
                'display_name' => 'str:191?',
                'avatar_url' => 'str:500?',
                'created_at' => 'datetime?',
                'last_used_at' => 'datetime?',
                '_unique' => [['provider', 'provider_uid']],
                '_index' => [['member_id']],
            ],
            'line_contacts' => [
                'user_id' => 'str:64',
                'display_name' => 'str:191?',
                'picture_url' => 'str:500?',
                'is_friend' => 'bool',
                'is_admin_receiver' => 'bool',
                'member_id' => 'int',
                'followed_at' => 'datetime?',
                'unfollowed_at' => 'datetime?',
                'last_message_at' => 'datetime?',
                'created_at' => 'datetime?',
                'updated_at' => 'datetime?',
                '_pk' => ['user_id'],
                '_index' => [['member_id'], ['is_friend']],
            ],
            'line_messages' => [
                'id' => 'pk',
                'event_id' => 'str:64?',
                'direction' => 'str:5',
                'user_id' => 'str:64?',
                'target' => 'str:20',
                'msg_type' => 'str:20',
                'text' => 'text?',
                'status' => 'str:20',
                'error' => 'text?',
                'admin_id' => 'int',
                'created_at' => 'datetime?',
                '_unique' => [['event_id']],
                '_index' => [['created_at'], ['user_id']],
            ],
            'form_submissions' => [
                'id' => 'pk',
                'form' => 'str:50',
                'page' => 'str:191',
                'name' => 'str:191',
                'email' => 'str:191',
                'phone' => 'str:50',
                'message' => 'text?',
                'data' => 'longtext?',
                'member_id' => 'int',
                'is_read' => 'bool',
                'ip_hash' => 'str:64',
                'created_at' => 'datetime?',
                '_index' => [['is_read'], ['created_at']],
            ],
            'media' => [
                'id' => 'pk',
                'path' => 'str:255',
                'original_name' => 'str:255',
                'mime' => 'str:100',
                'size' => 'int',
                'width' => 'int',
                'height' => 'int',
                'alt' => 'str:255',
                'created_at' => 'datetime?',
                '_index' => [['created_at']],
            ],
            'stats_daily' => [
                'id' => 'pk',
                'day' => 'str:10',
                'kind' => 'str:10',
                'ref' => 'str:150',
                'hits' => 'int',
                '_unique' => [['day', 'kind', 'ref']],
            ],
            'rate_limits' => [
                'k' => 'str:191',
                'hits' => 'int',
                'reset_at' => 'int',
                '_pk' => ['k'],
            ],
        ];
    }

    /** 產生建立資料表的 SQL 陳述句 */
    public static function statements(string $driver, string $prefix): array
    {
        $out = [];
        foreach (self::tables() as $name => $def) {
            $table = $prefix . $name;
            $cols = [];
            $pk = $def['_pk'] ?? null;
            foreach ($def as $col => $type) {
                if ($col[0] === '_') {
                    continue;
                }
                $cols[] = self::column($driver, $col, $type);
            }
            if ($pk) {
                $cols[] = 'PRIMARY KEY (`' . implode('`,`', $pk) . '`)';
            }
            if ($driver === 'mysql') {
                foreach ($def['_unique'] ?? [] as $i => $idx) {
                    $cols[] = "UNIQUE KEY `{$table}_u{$i}` (`" . implode('`,`', $idx) . '`)';
                }
                foreach ($def['_index'] ?? [] as $i => $idx) {
                    $cols[] = "KEY `{$table}_i{$i}` (`" . implode('`,`', $idx) . '`)';
                }
                $out[] = "CREATE TABLE IF NOT EXISTS `{$table}` (\n  " . implode(",\n  ", $cols)
                    . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            } else {
                $out[] = "CREATE TABLE IF NOT EXISTS `{$table}` (\n  " . implode(",\n  ", $cols) . "\n)";
                foreach ($def['_unique'] ?? [] as $i => $idx) {
                    $out[] = "CREATE UNIQUE INDEX IF NOT EXISTS `{$table}_u{$i}` ON `{$table}` (`" . implode('`,`', $idx) . '`)';
                }
                foreach ($def['_index'] ?? [] as $i => $idx) {
                    $out[] = "CREATE INDEX IF NOT EXISTS `{$table}_i{$i}` ON `{$table}` (`" . implode('`,`', $idx) . '`)';
                }
            }
        }
        return $out;
    }

    private static function column(string $driver, string $name, string $type): string
    {
        $nullable = str_ends_with($type, '?');
        $type = rtrim($type, '?');
        [$base, $len] = array_pad(explode(':', $type, 2), 2, null);
        $mysql = $driver === 'mysql';
        $sql = match ($base) {
            'pk' => $mysql ? 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT',
            'str' => ($mysql ? 'VARCHAR(' . (int) $len . ')' : 'TEXT') . ($nullable ? ' NULL' : " NOT NULL DEFAULT ''"),
            'text' => $mysql ? 'TEXT NULL' : 'TEXT NULL',
            'longtext' => $mysql ? 'LONGTEXT NULL' : 'TEXT NULL',
            'int' => ($mysql ? 'INT' : 'INTEGER') . ' NOT NULL DEFAULT 0',
            'bool' => ($mysql ? 'TINYINT(1)' : 'INTEGER') . ' NOT NULL DEFAULT 0',
            'datetime' => ($mysql ? 'DATETIME' : 'TEXT') . ' NULL',
            default => throw new \InvalidArgumentException("未知欄位型別：{$type}"),
        };
        return "`{$name}` {$sql}";
    }

    public static function create(): void
    {
        $pdo = DB::pdo();
        foreach (self::statements(DB::driver(), DB::rawTable('')) as $sql) {
            $pdo->exec($sql);
        }
    }

    /** 未來版本升級時在這裡加入遷移步驟 */
    public static function migrate(): void
    {
        $current = (int) (Settings::get('schema_version') ?? 0);
        if ($current >= self::VERSION) {
            return;
        }
        self::create();
        Settings::set('schema_version', self::VERSION);
    }
}
