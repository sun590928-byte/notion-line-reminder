<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;

/** 每日瀏覽與點擊統計（只存每日總數，不記錄個人資料） */
final class Stats
{
    /** 最近 N 天每日數量（缺少的日期補 0），回傳 [['day' => 'Y-m-d', 'hits' => n], ...] */
    public static function daily(string $kind, int $days = 30): array
    {
        $from = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
        $rows = DB::all('SELECT `day`, SUM(`hits`) AS hits FROM {stats_daily} WHERE `kind` = ? AND `day` >= ? GROUP BY `day`', [$kind, $from]);
        $map = array_column($rows, 'hits', 'day');
        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $out[] = ['day' => $d, 'hits' => (int) ($map[$d] ?? 0)];
        }
        return $out;
    }

    public static function total(string $kind, int $days = 30): int
    {
        $from = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
        return (int) DB::value('SELECT COALESCE(SUM(`hits`), 0) FROM {stats_daily} WHERE `kind` = ? AND `day` >= ?', [$kind, $from]);
    }

    /** 依 ref 加總（例如每個連結的點擊數） */
    public static function byRef(string $kind, int $days = 30): array
    {
        $from = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
        $rows = DB::all('SELECT `ref`, SUM(`hits`) AS hits FROM {stats_daily} WHERE `kind` = ? AND `day` >= ? GROUP BY `ref` ORDER BY hits DESC', [$kind, $from]);
        return array_map('intval', array_column($rows, 'hits', 'ref'));
    }
}
