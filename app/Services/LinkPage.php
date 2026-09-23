<?php
declare(strict_types=1);

namespace App\Services;

/** 連結頁（Linktree 風格）文件的欄位定義與資料清理 */
final class LinkPage
{
    public static function themes(): array
    {
        return (require AMF_ROOT . '/app/Data/themes.php')['links'];
    }

    public static function profileFields(): array
    {
        return [
            ['key' => 'avatar', 'type' => 'image', 'label' => '大頭貼', 'default' => '/assets/img/avatar-default.svg'],
            ['key' => 'avatarShape', 'type' => 'select', 'label' => '大頭貼形狀', 'options' => ['circle' => '圓形', 'rounded' => '圓角方形'], 'default' => 'circle'],
            ['key' => 'name', 'type' => 'text', 'label' => '名稱', 'default' => 'aftermoonF', 'maxlength' => 80],
            ['key' => 'verified', 'type' => 'toggle', 'label' => '名稱旁顯示認證徽章', 'default' => false],
            ['key' => 'bio', 'type' => 'textarea', 'label' => '簡介', 'rows' => 3, 'maxlength' => 300],
            ['key' => 'cover', 'type' => 'image', 'label' => '頂部封面圖（選填）'],
        ];
    }

    public static function linkFields(): array
    {
        return [
            ['key' => 'type', 'type' => 'select', 'label' => '類型', 'options' => ['link' => '連結', 'header' => '分類標題'], 'default' => 'link'],
            ['key' => 'title', 'type' => 'text', 'label' => '標題', 'maxlength' => 120],
            ['key' => 'desc', 'type' => 'text', 'label' => '副標（選填）', 'maxlength' => 160],
            ['key' => 'url', 'type' => 'url', 'label' => '網址'],
            ['key' => 'icon', 'type' => 'icon', 'label' => '圖示'],
            ['key' => 'thumb', 'type' => 'image', 'label' => '縮圖（會取代圖示）'],
            ['key' => 'highlight', 'type' => 'select', 'label' => '醒目效果', 'options' => ['none' => '無', 'pulse' => '脈動光暈', 'shine' => '光澤掃過', 'shake' => '搖晃', 'bounce' => '彈跳'], 'default' => 'none'],
            ['key' => 'enabled', 'type' => 'toggle', 'label' => '顯示', 'default' => true],
            ['key' => 'newTab', 'type' => 'toggle', 'label' => '在新分頁開啟', 'default' => true],
            ['key' => 'start', 'type' => 'datetime', 'label' => '開始顯示'],
            ['key' => 'end', 'type' => 'datetime', 'label' => '結束顯示'],
        ];
    }

    public static function socialFields(): array
    {
        return [
            ['key' => 'icon', 'type' => 'icon', 'label' => '平台', 'default' => 'instagram'],
            ['key' => 'url', 'type' => 'url', 'label' => '網址'],
        ];
    }

    public static function themeFields(): array
    {
        $presets = array_map(static fn ($t) => $t['label'], self::themes()) + ['custom' => '自訂'];
        return [
            ['key' => 'preset', 'type' => 'select', 'label' => '主題', 'options' => $presets, 'default' => 'twilight'],
            ['key' => 'bgType', 'type' => 'select', 'label' => '背景類型', 'options' => ['gradient' => '漸層', 'solid' => '純色', 'image' => '圖片'], 'default' => 'gradient'],
            ['key' => 'bgColor', 'type' => 'color', 'label' => '背景顏色', 'default' => '#121331'],
            ['key' => 'bgColor2', 'type' => 'color', 'label' => '漸層第二色', 'default' => '#7a4a72', 'showIf' => ['bgType', 'gradient']],
            ['key' => 'bgAngle', 'type' => 'range', 'label' => '漸層角度', 'min' => 0, 'max' => 360, 'step' => 5, 'default' => 165, 'unit' => '°', 'showIf' => ['bgType', 'gradient']],
            ['key' => 'bgImage', 'type' => 'image', 'label' => '背景圖片', 'showIf' => ['bgType', 'image']],
            ['key' => 'bgOverlay', 'type' => 'range', 'label' => '圖片遮罩', 'min' => 0, 'max' => 90, 'step' => 5, 'default' => 40, 'unit' => '%', 'showIf' => ['bgType', 'image']],
            ['key' => 'bgEffect', 'type' => 'select', 'label' => '背景特效', 'options' => ['stars' => '星空閃爍', 'aurora' => '流動極光', 'flow' => '漸層流動', 'none' => '無'], 'default' => 'stars'],
            ['key' => 'font', 'type' => 'select', 'label' => '字體', 'options' => Fonts::options(), 'default' => 'Noto Sans TC'],
            ['key' => 'textColor', 'type' => 'color', 'label' => '文字顏色', 'default' => '#fff8ec'],
            ['key' => 'accent', 'type' => 'color', 'label' => '強調色（醒目效果）', 'default' => '#f4c979'],
            ['key' => 'buttonStyle', 'type' => 'select', 'label' => '按鈕樣式', 'options' => ['glass' => '毛玻璃', 'fill' => '實心', 'outline' => '外框', 'soft' => '柔和陰影', 'hard' => '立體硬陰影'], 'default' => 'glass'],
            ['key' => 'buttonShape', 'type' => 'select', 'label' => '按鈕形狀', 'options' => ['pill' => '膠囊', 'rounded' => '圓角', 'square' => '直角'], 'default' => 'pill'],
            ['key' => 'buttonColor', 'type' => 'color', 'label' => '按鈕顏色', 'default' => '#ffffff'],
            ['key' => 'buttonTextColor', 'type' => 'color', 'label' => '按鈕文字顏色', 'default' => '#fff8ec'],
            ['key' => 'iconStyle', 'type' => 'select', 'label' => '圖示顏色', 'options' => ['mono' => '跟隨按鈕文字', 'brand' => '品牌原色'], 'default' => 'mono'],
            ['key' => 'animation', 'type' => 'select', 'label' => '進場動畫', 'options' => ['rise' => '依序浮現', 'fade' => '淡入', 'none' => '無'], 'default' => 'rise'],
        ];
    }

    public static function settingsFields(): array
    {
        return [
            ['key' => 'socialPosition', 'type' => 'select', 'label' => '社群圖示位置', 'options' => ['top' => '簡介下方', 'bottom' => '頁面底部'], 'default' => 'top'],
            ['key' => 'showShare', 'type' => 'toggle', 'label' => '顯示分享按鈕', 'default' => true],
            ['key' => 'showLogin', 'type' => 'toggle', 'label' => '顯示會員登入連結（已設定 LINE/Google 登入時）', 'default' => true],
            ['key' => 'showWebsite', 'type' => 'toggle', 'label' => '官網公開後顯示「前往官方網站」按鈕', 'default' => true],
            ['key' => 'websiteLabel', 'type' => 'text', 'label' => '官網按鈕文字', 'default' => '前往官方網站'],
            ['key' => 'footer', 'type' => 'text', 'label' => '頁尾文字', 'default' => '© {year} aftermoonF', 'help' => '{year} 會自動換成今年年份。'],
            ['key' => 'seoTitle', 'type' => 'text', 'label' => 'SEO 標題', 'help' => '留空則使用名稱。'],
            ['key' => 'seoDescription', 'type' => 'textarea', 'label' => 'SEO 描述', 'rows' => 3, 'help' => '留空則使用簡介。'],
        ];
    }

    public static function normalize(mixed $doc): array
    {
        $doc = is_array($doc) ? $doc : [];
        $links = [];
        $ids = [];
        foreach (array_slice((array) ($doc['links'] ?? []), 0, 100) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = (string) ($item['id'] ?? '');
            if (!preg_match('/^[A-Za-z0-9_-]{3,40}$/', $id) || isset($ids[$id])) {
                $id = Sections::newId('l');
            }
            $ids[$id] = true;
            $links[] = ['id' => $id] + Fields::normalize(self::linkFields(), $item);
        }
        $socials = [];
        foreach (array_slice((array) ($doc['socials'] ?? []), 0, 20) as $s) {
            $clean = Fields::normalize(self::socialFields(), $s);
            if ($clean['icon'] !== '') {
                $socials[] = $clean;
            }
        }
        return [
            'v' => 1,
            'profile' => Fields::normalize(self::profileFields(), $doc['profile'] ?? []),
            'links' => $links,
            'socials' => $socials,
            'theme' => Fields::normalize(self::themeFields(), $doc['theme'] ?? []),
            'settings' => Fields::normalize(self::settingsFields(), $doc['settings'] ?? []),
        ];
    }

    /** 預設連結頁：暮光主題＋常用連結（安裝後即為公開首頁） */
    public static function defaults(): array
    {
        return self::normalize([
            'profile' => [
                'name' => 'aftermoonF',
                'bio' => "在午後的光與月色之間 ✦\n點擊下方連結，認識我們、與我們聊聊",
            ],
            'links' => [
                ['type' => 'link', 'title' => '加入 LINE 官方帳號', 'desc' => '第一時間收到最新消息與優惠', 'url' => 'https://line.me/', 'icon' => 'line', 'highlight' => 'pulse'],
                ['type' => 'link', 'title' => 'Instagram', 'url' => 'https://www.instagram.com/', 'icon' => 'instagram'],
                ['type' => 'link', 'title' => 'Facebook 粉絲專頁', 'url' => 'https://www.facebook.com/', 'icon' => 'facebook'],
                ['type' => 'link', 'title' => 'Threads', 'url' => 'https://www.threads.net/', 'icon' => 'threads'],
                ['type' => 'header', 'title' => '服務與合作'],
                ['type' => 'link', 'title' => '預約與諮詢', 'url' => 'https://line.me/', 'icon' => 'calendar'],
                ['type' => 'link', 'title' => '合作邀約 Email', 'url' => 'mailto:hello@example.com', 'icon' => 'mail', 'newTab' => false],
                ['type' => 'link', 'title' => '門市位置', 'url' => 'https://maps.google.com/', 'icon' => 'map-pin'],
            ],
            'socials' => [
                ['icon' => 'instagram', 'url' => 'https://www.instagram.com/'],
                ['icon' => 'facebook', 'url' => 'https://www.facebook.com/'],
                ['icon' => 'line', 'url' => 'https://line.me/'],
                ['icon' => 'youtube', 'url' => 'https://www.youtube.com/'],
                ['icon' => 'threads', 'url' => 'https://www.threads.net/'],
            ],
            'theme' => ['preset' => 'twilight'] + array_diff_key(self::themes()['twilight'], ['label' => true]),
        ]);
    }

    /** 連結頁主題 CSS 變數 */
    public static function cssVars(array $t): string
    {
        $vars = [
            '--bg' => $t['bgColor'],
            '--bg2' => $t['bgColor2'],
            '--angle' => (int) $t['bgAngle'] . 'deg',
            '--text' => $t['textColor'],
            '--muted' => Palette::rgba($t['textColor'], .74),
            '--line' => Palette::rgba($t['textColor'], .18),
            '--accent' => $t['accent'],
            '--accent-glow' => Palette::rgba($t['accent'], .55),
            '--btn' => $t['buttonColor'],
            '--btn-text' => $t['buttonTextColor'],
            '--btn-on' => Palette::onColor($t['buttonColor']),
            '--btn-glass' => Palette::rgba($t['buttonColor'], .13),
            '--btn-glass-hover' => Palette::rgba($t['buttonColor'], .22),
            '--btn-border' => Palette::rgba($t['buttonColor'], .3),
            '--btn-radius' => match ($t['buttonShape']) { 'pill' => '999px', 'square' => '4px', default => '14px' },
            '--overlay' => (string) round(((int) $t['bgOverlay']) / 100, 2),
            '--font' => Fonts::stack($t['font']),
            '--card' => Palette::isDark($t['bgColor']) ? 'rgba(255, 255, 255, .08)' : 'rgba(255, 255, 255, .78)',
        ];
        $css = ':root{';
        foreach ($vars as $k => $v) {
            $css .= $k . ':' . $v . ';';
        }
        return $css . '}';
    }

    /** 連結頁的配色（會員頁在「連結頁模式」下沿用） */
    public static function isDark(array $t): bool
    {
        return $t['bgType'] === 'image' || Palette::isDark($t['bgColor']);
    }

    /** 目前應顯示的連結（啟用中且在排程時間內） */
    public static function visibleLinks(array $doc, bool $includeHidden = false): array
    {
        $now = date('Y-m-d\TH:i');
        return array_values(array_filter($doc['links'] ?? [], static function ($l) use ($now, $includeHidden) {
            if ($includeHidden) {
                return true;
            }
            if (empty($l['enabled'])) {
                return false;
            }
            if ($l['type'] === 'link' && ($l['url'] ?? '') === '') {
                return false;
            }
            if (($l['start'] ?? '') !== '' && $now < $l['start']) {
                return false;
            }
            if (($l['end'] ?? '') !== '' && $now > $l['end']) {
                return false;
            }
            return true;
        }));
    }
}
