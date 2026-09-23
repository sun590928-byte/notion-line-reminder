<?php
declare(strict_types=1);

namespace App\Services;

/** 使用者輸入網址的清理（阻擋 javascript: 等危險協定） */
final class Url
{
    public static function clean(string $url): string
    {
        $url = trim($url);
        if ($url === '' || preg_match('/[\x00-\x1f\x7f]/', $url)) {
            return '';
        }
        if (preg_match('/^page:[A-Za-z0-9_-]+(#[A-Za-z0-9_-]*)?$/', $url)) {
            return $url;
        }
        if ($url[0] === '#') {
            return preg_match('/^#[A-Za-z0-9_-]*$/', $url) ? $url : '';
        }
        if ($url[0] === '/' && !str_starts_with($url, '//') && !str_contains($url, '\\')) {
            return mb_substr($url, 0, 1000);
        }
        if (preg_match('#^(https?://|mailto:|tel:|sms:)#i', $url)) {
            return mb_substr($url, 0, 2000);
        }
        // 省略 https:// 的網址，例如 www.example.com
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9.-]*\.[A-Za-z]{2,}(\/\S*)?$/', $url)) {
            return 'https://' . $url;
        }
        return '';
    }

    public static function image(string $src): string
    {
        $src = trim($src);
        if ($src === '' || preg_match('/[\x00-\x1f\x7f"\'<>\s]/', $src)) {
            return '';
        }
        if ($src[0] === '/' && !str_starts_with($src, '//') && !str_contains($src, '..')) {
            return $src;
        }
        if (preg_match('#^https://#i', $src)) {
            return mb_substr($src, 0, 2000);
        }
        return '';
    }

    public static function isExternal(string $url): bool
    {
        return (bool) preg_match('#^https?://#i', $url);
    }

    /** YouTube / Vimeo 影片網址轉為嵌入網址 */
    public static function videoEmbed(string $url): string
    {
        $url = trim($url);
        if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?rel=0';
        }
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }
        return '';
    }

    /** Google 地圖：可貼地址或 Google 分享的嵌入網址 */
    public static function mapEmbed(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (preg_match('#^https://(www\.)?google\.[a-z.]+/maps/embed\?#i', $value)) {
            return $value;
        }
        if (preg_match('/<iframe[^>]+src="([^"]+)"/i', $value, $m) && preg_match('#^https://(www\.)?google\.[a-z.]+/maps/embed\?#i', html_entity_decode($m[1]))) {
            return html_entity_decode($m[1]);
        }
        return 'https://www.google.com/maps?q=' . rawurlencode($value) . '&output=embed';
    }
}
