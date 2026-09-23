<?php
declare(strict_types=1);

namespace App\Services;

/**
 * 圖示：內建品牌/一般圖示（app/Data/icons.php），也支援
 *   emoji:🌙      直接顯示表情符號
 *   img:/uploads/xxx.png  自訂圖片
 */
final class Icons
{
    private static ?array $icons = null;

    public static function all(): array
    {
        if (self::$icons === null) {
            self::$icons = require AMF_ROOT . '/app/Data/icons.php';
        }
        return self::$icons;
    }

    public static function has(string $name): bool
    {
        return isset(self::all()[$name]);
    }

    /** 驗證圖示值是否合法（用於內容正規化） */
    public static function clean(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (str_starts_with($value, 'emoji:')) {
            $emoji = trim(substr($value, 6));
            return $emoji !== '' && mb_strlen($emoji) <= 8 && !preg_match('/[<>"\'&]/', $emoji) ? 'emoji:' . $emoji : '';
        }
        if (str_starts_with($value, 'img:')) {
            $src = Url::image(substr($value, 4));
            return $src !== '' ? 'img:' . $src : '';
        }
        return self::has($value) ? $value : '';
    }

    public static function color(string $name): string
    {
        return (string) (self::all()[$name][2] ?? '');
    }

    public static function label(string $name): string
    {
        return (string) (self::all()[$name][0] ?? $name);
    }

    public static function isBrand(string $name): bool
    {
        return (self::all()[$name][1] ?? '') === 'brand';
    }

    public static function svg(string $name, string $class = ''): string
    {
        if (str_starts_with($name, 'emoji:')) {
            return '<span class="ico ico-emoji ' . e($class) . '" aria-hidden="true">' . e(substr($name, 6)) . '</span>';
        }
        if (str_starts_with($name, 'img:')) {
            return '<img class="ico ico-img ' . e($class) . '" src="' . e(media_url(substr($name, 4))) . '" alt="" loading="lazy">';
        }
        $icon = self::all()[$name] ?? null;
        if (!$icon) {
            return '';
        }
        $cls = trim('ico ' . $class);
        if ($icon[3] === 'f') {
            return '<svg class="' . e($cls) . '" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">' . $icon[4] . '</svg>';
        }
        return '<svg class="' . e($cls) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $icon[4] . '</svg>';
    }
}
