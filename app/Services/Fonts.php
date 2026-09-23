<?php
declare(strict_types=1);

namespace App\Services;

final class Fonts
{
    private static ?array $fonts = null;

    public static function all(): array
    {
        if (self::$fonts === null) {
            self::$fonts = require AMF_ROOT . '/app/Data/fonts.php';
        }
        return self::$fonts;
    }

    public static function options(): array
    {
        return array_map(static fn ($f) => $f['label'], self::all());
    }

    public static function stack(string $name): string
    {
        return self::all()[$name]['stack'] ?? self::all()['Noto Sans TC']['stack'];
    }

    /** 產生 Google Fonts 樣式表網址（多個字體合併成一個請求） */
    public static function googleUrl(array $names): string
    {
        $families = [];
        foreach (array_unique($names) as $name) {
            foreach (self::all()[$name]['google'] ?? [] as $family) {
                $families[$family] = true;
            }
        }
        if (!$families) {
            return '';
        }
        return 'https://fonts.googleapis.com/css2?family=' . implode('&family=', array_keys($families)) . '&display=swap';
    }
}
