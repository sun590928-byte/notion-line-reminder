<?php
declare(strict_types=1);

namespace App\Services;

/**
 * 依欄位定義清理資料（後台表單產生器使用同一份定義）。
 * 欄位型別：text textarea richtext code url image color select toggle number range icon datetime list
 */
final class Fields
{
    public static function normalize(array $fields, mixed $data): array
    {
        $data = is_array($data) ? $data : [];
        $out = [];
        foreach ($fields as $f) {
            $key = $f['key'];
            $has = array_key_exists($key, $data);
            $out[$key] = self::value($f, $has ? $data[$key] : null, $has);
        }
        return $out;
    }

    public static function value(array $f, mixed $v, bool $has): mixed
    {
        $default = $f['default'] ?? null;
        switch ($f['type']) {
            case 'text':
            case 'textarea':
                return $has && is_scalar($v) ? Sanitizer::text((string) $v, (int) ($f['maxlength'] ?? 5000)) : (string) ($default ?? '');
            case 'richtext':
                return $has && is_string($v) ? Sanitizer::html($v) : (string) ($default ?? '');
            case 'code':
                return $has && is_string($v) ? mb_substr($v, 0, 50000) : (string) ($default ?? '');
            case 'url':
                return $has && is_string($v) ? Url::clean($v) : (string) ($default ?? '');
            case 'image':
                return $has && is_string($v) ? Url::image($v) : (string) ($default ?? '');
            case 'color':
                return $has && is_string($v) && preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? strtolower($v) : (string) ($default ?? '');
            case 'select':
                $options = array_map('strval', array_keys($f['options']));
                $v = is_scalar($v) ? (string) $v : null;
                return $has && in_array($v, $options, true) ? $v : (string) ($default ?? $options[0]);
            case 'toggle':
                return $has ? filter_var($v, FILTER_VALIDATE_BOOLEAN) : (bool) $default;
            case 'number':
            case 'range':
                $n = $has && is_numeric($v) ? $v + 0 : ($default ?? 0);
                if (isset($f['min'])) {
                    $n = max($f['min'], $n);
                }
                if (isset($f['max'])) {
                    $n = min($f['max'], $n);
                }
                return $n;
            case 'icon':
                return $has && is_string($v) ? Icons::clean($v) : (string) ($default ?? '');
            case 'datetime':
                return $has && is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $v) ? $v : '';
            case 'list':
                $items = $has ? (is_array($v) ? array_values($v) : []) : ($default ?? []);
                $items = array_slice($items, 0, (int) ($f['max'] ?? 50));
                return array_map(static fn ($item) => self::normalize($f['fields'], $item), $items);
            default:
                return null;
        }
    }

    /** 取得欄位預設值（新增區塊時使用） */
    public static function defaults(array $fields): array
    {
        return self::normalize($fields, []);
    }
}
