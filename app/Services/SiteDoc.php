<?php
declare(strict_types=1);

namespace App\Services;

/** 官方網站文件（主題、頁首頁尾、頁面與區塊）的欄位定義與資料清理 */
final class SiteDoc
{
    public const RESERVED_SLUGS = ['admin', 'api', 'auth', 'login', 'logout', 'member', 'links', 'preview', 'install',
        'assets', 'uploads', 'sitemap.xml', 'robots.txt', 'index.php', 'go', 'favicon.ico',
        // 專案資料夾名稱（文件根目錄設在 httpdocs 時這些網址會被 .htaccess 擋下或導向）
        'public', 'app', 'config', 'storage', 'tools', 'docs', 'node_modules', 'vendor'];

    public static function themes(): array
    {
        return (require AMF_ROOT . '/app/Data/themes.php')['site'];
    }

    public static function themeFields(): array
    {
        $fonts = Fonts::options();
        $presets = array_map(static fn ($t) => $t['label'], self::themes()) + ['custom' => '自訂'];
        return [
            ['key' => 'preset', 'type' => 'select', 'label' => '主題', 'options' => $presets, 'default' => 'moonlight'],
            ['key' => 'primary', 'type' => 'color', 'label' => '主色', 'default' => '#2e2a5b'],
            ['key' => 'accent', 'type' => 'color', 'label' => '強調色', 'default' => '#c9955b'],
            ['key' => 'bg', 'type' => 'color', 'label' => '背景', 'default' => '#faf7f2'],
            ['key' => 'surface', 'type' => 'color', 'label' => '淺色區塊底色', 'default' => '#f1ebe1'],
            ['key' => 'text', 'type' => 'color', 'label' => '文字', 'default' => '#1f1b2d'],
            ['key' => 'muted', 'type' => 'color', 'label' => '次要文字', 'default' => '#6e6878'],
            ['key' => 'dark', 'type' => 'color', 'label' => '深色區塊', 'default' => '#16142a'],
            ['key' => 'headingFont', 'type' => 'select', 'label' => '標題字體', 'options' => $fonts, 'default' => 'Noto Serif TC'],
            ['key' => 'bodyFont', 'type' => 'select', 'label' => '內文字體', 'options' => $fonts, 'default' => 'Noto Sans TC'],
            ['key' => 'radius', 'type' => 'range', 'label' => '圓角大小', 'min' => 0, 'max' => 32, 'step' => 2, 'default' => 14, 'unit' => 'px'],
            ['key' => 'buttonShape', 'type' => 'select', 'label' => '按鈕形狀', 'options' => ['pill' => '膠囊', 'rounded' => '圓角', 'square' => '直角'], 'default' => 'pill'],
            ['key' => 'animation', 'type' => 'select', 'label' => '動畫強度', 'options' => ['lively' => '活潑', 'normal' => '標準', 'subtle' => '輕微', 'none' => '關閉'], 'default' => 'normal'],
            ['key' => 'scrollProgress', 'type' => 'toggle', 'label' => '頁面頂端顯示閱讀進度條', 'default' => true],
            ['key' => 'backToTop', 'type' => 'toggle', 'label' => '顯示「回到頂端」按鈕', 'default' => true],
            ['key' => 'cursorGlow', 'type' => 'toggle', 'label' => '深色區塊的滑鼠光暈效果（電腦版）', 'default' => true],
        ];
    }

    public static function headerFields(): array
    {
        return [
            ['key' => 'logo', 'type' => 'image', 'label' => 'Logo 圖片', 'help' => '建議使用透明背景 PNG，高度 80px 以上。'],
            ['key' => 'showName', 'type' => 'toggle', 'label' => '顯示網站名稱', 'default' => true],
            ['key' => 'style', 'type' => 'select', 'label' => '頁首樣式', 'options' => ['auto' => '在主視覺上透明，捲動後變實色', 'solid' => '固定實色'], 'default' => 'auto'],
            ['key' => 'hideOnScroll', 'type' => 'toggle', 'label' => '向下捲動時自動隱藏頁首', 'default' => true],
            ['key' => 'menu', 'type' => 'list', 'label' => '選單額外連結', 'max' => 8, 'itemLabel' => 'label', 'fields' => [
                ['key' => 'label', 'type' => 'text', 'label' => '文字'],
                ['key' => 'url', 'type' => 'url', 'label' => '連結'],
            ]],
            ['key' => 'showMember', 'type' => 'toggle', 'label' => '顯示會員登入／會員中心按鈕', 'default' => true],
            ['key' => 'ctaLabel', 'type' => 'text', 'label' => '頁首按鈕文字'],
            ['key' => 'ctaUrl', 'type' => 'url', 'label' => '頁首按鈕連結'],
        ];
    }

    public static function footerFields(): array
    {
        return [
            ['key' => 'about', 'type' => 'textarea', 'label' => '頁尾簡介', 'rows' => 3],
            ['key' => 'links', 'type' => 'list', 'label' => '頁尾連結', 'max' => 12, 'itemLabel' => 'label', 'fields' => [
                ['key' => 'label', 'type' => 'text', 'label' => '文字'],
                ['key' => 'url', 'type' => 'url', 'label' => '連結'],
            ]],
            ['key' => 'showSocial', 'type' => 'toggle', 'label' => '顯示社群圖示（取自連結頁）', 'default' => true],
            ['key' => 'copyright', 'type' => 'text', 'label' => '版權文字', 'default' => '© {year} aftermoonF. All rights reserved.', 'help' => '{year} 會自動換成今年年份。'],
        ];
    }

    public static function pageFields(): array
    {
        return [
            ['key' => 'title', 'type' => 'text', 'label' => '頁面名稱', 'default' => '新頁面'],
            ['key' => 'slug', 'type' => 'text', 'label' => '網址', 'help' => '只能使用英文小寫、數字與 -，例如 about。'],
            ['key' => 'showInNav', 'type' => 'toggle', 'label' => '顯示在選單', 'default' => true],
            ['key' => 'membersOnly', 'type' => 'toggle', 'label' => '會員限定頁面', 'default' => false, 'help' => '未登入的訪客會看到登入提示。'],
            ['key' => 'seoTitle', 'type' => 'text', 'label' => 'SEO 標題', 'help' => '搜尋結果與分享時顯示的標題，留空則使用頁面名稱。'],
            ['key' => 'seoDescription', 'type' => 'textarea', 'label' => 'SEO 描述', 'rows' => 3],
            ['key' => 'seoImage', 'type' => 'image', 'label' => '分享縮圖（建議 1200×630）'],
        ];
    }

    public static function slugify(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = (string) preg_replace('/[^a-z0-9]+/', '-', $slug);
        return substr(trim($slug, '-'), 0, 60);
    }

    public static function normalize(mixed $doc): array
    {
        $doc = is_array($doc) ? $doc : [];
        $pages = [];
        $usedSlugs = [];
        $usedIds = [];
        foreach (array_slice((array) ($doc['pages'] ?? []), 0, 40) as $i => $p) {
            if (!is_array($p)) {
                continue;
            }
            $page = Fields::normalize(self::pageFields(), $p);
            $id = (string) ($p['id'] ?? '');
            if (!preg_match('/^[A-Za-z0-9_-]{3,40}$/', $id) || isset($usedIds[$id])) {
                $id = Sections::newId('p');
            }
            $slug = self::slugify((string) $page['slug']);
            if ($slug === '' || in_array($slug, self::RESERVED_SLUGS, true)) {
                $slug = $slug === '' ? 'page-' . ($i + 1) : $slug . '-page';
            }
            $base = $slug;
            $n = 2;
            while (isset($usedSlugs[$slug])) {
                $slug = $base . '-' . $n++;
            }
            $usedSlugs[$slug] = true;
            $usedIds[$id] = true;
            $sections = [];
            foreach (array_slice((array) ($p['sections'] ?? []), 0, 80) as $s) {
                $clean = Sections::normalize($s);
                if ($clean) {
                    $sections[] = $clean;
                }
            }
            $pages[] = ['id' => $id, 'slug' => $slug] + $page + ['sections' => $sections];
        }
        if (!$pages) {
            $pages[] = ['id' => 'p_home', 'slug' => 'home'] + Fields::normalize(self::pageFields(), ['title' => '首頁']) + ['sections' => []];
        }
        $homeId = (string) ($doc['homeId'] ?? '');
        if (!in_array($homeId, array_column($pages, 'id'), true)) {
            $homeId = $pages[0]['id'];
        }
        return [
            'v' => 1,
            'theme' => Fields::normalize(self::themeFields(), $doc['theme'] ?? []),
            'header' => Fields::normalize(self::headerFields(), $doc['header'] ?? []),
            'footer' => Fields::normalize(self::footerFields(), $doc['footer'] ?? []),
            'homeId' => $homeId,
            'pages' => $pages,
        ];
    }

    public static function defaults(): array
    {
        return self::normalize(require AMF_ROOT . '/app/Data/default-site.php');
    }

    public static function homePage(array $doc): ?array
    {
        foreach ($doc['pages'] ?? [] as $p) {
            if ($p['id'] === ($doc['homeId'] ?? '')) {
                return $p;
            }
        }
        return $doc['pages'][0] ?? null;
    }

    public static function findBySlug(array $doc, string $slug): ?array
    {
        foreach ($doc['pages'] ?? [] as $p) {
            if ($p['slug'] === $slug) {
                return $p;
            }
        }
        return null;
    }

    public static function findById(array $doc, string $id): ?array
    {
        foreach ($doc['pages'] ?? [] as $p) {
            if ($p['id'] === $id) {
                return $p;
            }
        }
        return null;
    }
}
