<?php
declare(strict_types=1);

namespace App\Services;

/** 色彩計算：依背景亮度自動決定文字、卡片、按鈕顏色，確保任何主題都清楚易讀 */
final class Palette
{
    public static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return [0, 0, 0];
        }
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    public static function hex(array $rgb): string
    {
        return sprintf('#%02x%02x%02x', ...array_map(static fn ($v) => max(0, min(255, (int) round($v))), $rgb));
    }

    public static function mix(string $a, string $b, float $t): string
    {
        $x = self::rgb($a);
        $y = self::rgb($b);
        return self::hex([$x[0] + ($y[0] - $x[0]) * $t, $x[1] + ($y[1] - $x[1]) * $t, $x[2] + ($y[2] - $x[2]) * $t]);
    }

    public static function rgba(string $hex, float $alpha): string
    {
        [$r, $g, $b] = self::rgb($hex);
        return "rgba({$r}, {$g}, {$b}, " . round($alpha, 3) . ')';
    }

    public static function luminance(string $hex): float
    {
        $c = array_map(static function ($v) {
            $v /= 255;
            return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
        }, self::rgb($hex));
        return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
    }

    public static function contrast(string $a, string $b): float
    {
        $l1 = self::luminance($a);
        $l2 = self::luminance($b);
        return (max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05);
    }

    public static function isDark(string $hex): bool
    {
        return self::luminance($hex) < 0.26;
    }

    /** 背景上最清楚的文字色 */
    public static function onColor(string $bg, string $light = '#ffffff', string $dark = '#16141f'): string
    {
        return self::contrast($bg, $light) >= self::contrast($bg, $dark) ? $light : $dark;
    }

    /**
     * 計算某個背景色上的整組配色
     * @return array{bg:string,text:string,muted:string,card:string,border:string,link:string,btn:string,btnText:string}
     */
    public static function tone(string $bg, array $t): array
    {
        $dark = self::isDark($bg);
        if ($dark) {
            $text = self::isDark($t['text']) ? '#f7f5fb' : $t['text'];
            $muted = self::rgba($text, .72);
            $card = self::rgba('#ffffff', .06);
            $border = self::rgba('#ffffff', .14);
        } else {
            $text = self::isDark($t['text']) ? $t['text'] : '#1b1a22';
            $muted = self::contrast($t['muted'], $bg) >= 3 ? $t['muted'] : self::mix($text, $bg, .35);
            $card = self::luminance($bg) > 0.93 ? (self::luminance($t['surface']) > 0.5 && $t['surface'] !== $bg ? $t['surface'] : self::mix($bg, $text, .04)) : '#ffffff';
            $border = self::rgba($text, .12);
        }
        $candidates = [$t['primary'], $t['accent'], $text];
        $link = $text;
        foreach ($candidates as $c) {
            if (self::contrast($c, $bg) >= 3) {
                $link = $c;
                break;
            }
        }
        $btn = $text;
        foreach ([$t['primary'], $t['accent'], $dark ? '#ffffff' : $text] as $c) {
            if (self::contrast($c, $bg) >= 2.2) {
                $btn = $c;
                break;
            }
        }
        return [
            'bg' => $bg,
            'text' => $text,
            'muted' => $muted,
            'card' => $card,
            'border' => $border,
            'link' => $link,
            'btn' => $btn,
            'btnText' => self::onColor($btn),
        ];
    }

    public static function toneVars(string $prefix, array $tone): string
    {
        $out = '';
        foreach ($tone as $k => $v) {
            $out .= "--{$prefix}-{$k}:{$v};";
        }
        return $out;
    }
}
