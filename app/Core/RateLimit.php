<?php
declare(strict_types=1);

namespace App\Core;

/** 簡易流量限制（以資料庫計數，適用共享主機） */
final class RateLimit
{
    /** 記錄一次並回傳是否仍在限制內 */
    public static function hit(string $key, int $max, int $window): bool
    {
        $key = substr($key, 0, 191);
        $now = time();
        if (random_int(1, 100) === 1) {
            DB::delete('rate_limits', '`reset_at` < ?', [$now - 86400]);
        }
        $row = DB::one('SELECT `hits`, `reset_at` FROM {rate_limits} WHERE `k` = ?', [$key]);
        if (!$row || (int) $row['reset_at'] <= $now) {
            DB::upsert('rate_limits', ['k' => $key], ['hits' => 1, 'reset_at' => $now + $window]);
            return true;
        }
        if ((int) $row['hits'] >= $max) {
            return false;
        }
        DB::run('UPDATE {rate_limits} SET `hits` = `hits` + 1 WHERE `k` = ?', [$key]);
        return true;
    }

    public static function blocked(string $key, int $max): bool
    {
        $row = DB::one('SELECT `hits`, `reset_at` FROM {rate_limits} WHERE `k` = ?', [substr($key, 0, 191)]);
        return $row && (int) $row['reset_at'] > time() && (int) $row['hits'] >= $max;
    }

    public static function clear(string $key): void
    {
        DB::delete('rate_limits', '`k` = ?', [substr($key, 0, 191)]);
    }
}
