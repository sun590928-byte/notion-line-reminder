<?php
declare(strict_types=1);

namespace App\Core;

/**
 * 資料庫存取（PDO）。支援 MySQL / MariaDB（Plesk 主機建議）與 SQLite（免設定）。
 * SQL 中以 {table} 表示資料表，會自動加上前綴。
 */
final class DB
{
    private static ?\PDO $pdo = null;
    private static string $driver = 'mysql';
    private static string $prefix = '';

    public static function connect(array $cfg): \PDO
    {
        $driver = ($cfg['driver'] ?? 'mysql') === 'sqlite' ? 'sqlite' : 'mysql';
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ];
        if ($driver === 'sqlite') {
            $path = (string) ($cfg['path'] ?? '');
            if ($path === '') {
                $path = AMF_ROOT . '/storage/database.sqlite';
            } elseif (!str_starts_with($path, '/') && !preg_match('#^[A-Za-z]:[\\\\/]#', $path)) {
                $path = AMF_ROOT . '/' . $path;
            }
            $pdo = new \PDO('sqlite:' . $path, null, null, $options);
            $pdo->exec('PRAGMA busy_timeout = 5000');
            try {
                $pdo->exec('PRAGMA journal_mode = WAL');
            } catch (\Throwable) {
                // 部分主機不支援 WAL，使用預設模式即可
            }
        } else {
            $host = (string) ($cfg['host'] ?? 'localhost');
            $port = (int) ($cfg['port'] ?? 3306);
            if (preg_match('/^(.+):(\d+)$/', $host, $m)) {
                $host = $m[1];
                $port = (int) $m[2];
            }
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port ?: 3306, (string) ($cfg['database'] ?? ''));
            $options[\PDO::ATTR_EMULATE_PREPARES] = false;
            $options[\PDO::ATTR_TIMEOUT] = 5;
            $pdo = new \PDO($dsn, (string) ($cfg['username'] ?? ''), (string) ($cfg['password'] ?? ''), $options);
            $pdo->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
        }
        self::$pdo = $pdo;
        self::$driver = $driver;
        self::$prefix = preg_replace('/[^A-Za-z0-9_]/', '', (string) ($cfg['prefix'] ?? 'amf_')) ?? '';
        return $pdo;
    }

    public static function pdo(): \PDO
    {
        if (self::$pdo === null) {
            self::connect((array) Config::get('db', []));
        }
        return self::$pdo;
    }

    public static function driver(): string
    {
        self::pdo();
        return self::$driver;
    }

    public static function table(string $name): string
    {
        self::pdo();
        return '`' . self::$prefix . $name . '`';
    }

    public static function rawTable(string $name): string
    {
        self::pdo();
        return self::$prefix . $name;
    }

    public static function sql(string $sql): string
    {
        self::pdo();
        return (string) preg_replace_callback('/\{([a-z_]+)\}/', static fn ($m) => '`' . self::$prefix . $m[1] . '`', $sql);
    }

    public static function run(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::pdo()->prepare(self::sql($sql));
        $stmt->execute(array_values($params));
        return $stmt;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $v = self::run($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = 'INSERT INTO ' . self::table($table) . ' (`' . implode('`,`', $cols) . '`) VALUES ('
            . implode(',', array_fill(0, count($cols), '?')) . ')';
        self::pdo()->prepare($sql)->execute(array_values($data));
        return (int) self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $params = []): int
    {
        $set = implode(', ', array_map(static fn ($c) => "`{$c}` = ?", array_keys($data)));
        $stmt = self::pdo()->prepare('UPDATE ' . self::table($table) . " SET {$set} WHERE {$where}");
        $stmt->execute([...array_values($data), ...array_values($params)]);
        return $stmt->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int
    {
        $stmt = self::pdo()->prepare('DELETE FROM ' . self::table($table) . " WHERE {$where}");
        $stmt->execute(array_values($params));
        return $stmt->rowCount();
    }

    /** 依主鍵欄位新增或更新 */
    public static function upsert(string $table, array $keys, array $data): void
    {
        $where = implode(' AND ', array_map(static fn ($c) => "`{$c}` = ?", array_keys($keys)));
        $exists = self::value('SELECT 1 FROM ' . self::table($table) . " WHERE {$where}", array_values($keys));
        if ($exists) {
            if ($data) {
                self::update($table, $data, $where, array_values($keys));
            }
        } else {
            self::insert($table, $keys + $data);
        }
    }

    /** 每日統計計數（原子遞增） */
    public static function bumpStat(string $kind, string $ref, int $by = 1, ?string $day = null): void
    {
        $day ??= date('Y-m-d');
        $t = self::table('stats_daily');
        if (self::driver() === 'sqlite') {
            $sql = "INSERT INTO {$t} (`day`, `kind`, `ref`, `hits`) VALUES (?, ?, ?, ?) ON CONFLICT(`day`, `kind`, `ref`) DO UPDATE SET `hits` = `hits` + ?";
        } else {
            $sql = "INSERT INTO {$t} (`day`, `kind`, `ref`, `hits`) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `hits` = `hits` + ?";
        }
        self::pdo()->prepare($sql)->execute([$day, $kind, $ref, $by, $by]);
    }

    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $fn();
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
