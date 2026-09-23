<?php
declare(strict_types=1);

namespace App\Core;

/** 網站設定（key → JSON 值） */
final class Settings
{
    private static array $cache = [];

    public static function get(string $name, mixed $default = null): mixed
    {
        if (!array_key_exists($name, self::$cache)) {
            $raw = DB::value('SELECT `value` FROM {settings} WHERE `name` = ?', [$name]);
            self::$cache[$name] = is_string($raw) ? json_decode($raw, true) : null;
        }
        return self::$cache[$name] ?? $default;
    }

    public static function set(string $name, mixed $value): void
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        DB::upsert('settings', ['name' => $name], ['value' => $json, 'updated_at' => DB::now()]);
        self::$cache[$name] = $value;
    }

    /** 合併更新陣列型設定 */
    public static function merge(string $name, array $values): array
    {
        $current = self::get($name, []);
        $merged = array_replace(is_array($current) ? $current : [], $values);
        self::set($name, $merged);
        return $merged;
    }

    public static function forget(string $name): void
    {
        DB::delete('settings', '`name` = ?', [$name]);
        unset(self::$cache[$name]);
    }

    /** 常用設定的快捷存取 */
    public static function site(): array
    {
        $defaults = [
            'name' => 'aftermoonF',
            'tagline' => '',
            'url' => '',
            'logo' => '',
            'favicon' => '',
            'seoTitle' => '',
            'seoDescription' => '',
            'ogImage' => '',
            'gaId' => '',
            'headCode' => '',
            'bodyCode' => '',
            'contactEmail' => '',
        ];
        $v = self::get('site', []);
        return array_replace($defaults, is_array($v) ? $v : []);
    }

    public static function mode(): string
    {
        $mode = self::get('mode', 'links');
        return in_array($mode, ['links', 'website', 'maintenance'], true) ? $mode : 'links';
    }
}
