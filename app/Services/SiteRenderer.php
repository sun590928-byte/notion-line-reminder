<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Crypto;
use App\Core\Settings;
use App\Core\View;

/** 官方網站渲染：把網站文件（主題＋頁面＋區塊）輸出成 HTML */
final class SiteRenderer
{
    public array $doc;
    public array $ctx;
    private ?array $section = null;
    private int $index = 0;
    private ?array $linksDoc = null;
    private bool $firstHero = true;

    /**
     * @param array $ctx preview（草稿預覽）、editor（後台編輯器內）、instant（略過進場動畫）、base（頁面網址前綴）
     */
    public function __construct(array $doc, array $ctx = [])
    {
        $this->doc = $doc;
        $this->ctx = $ctx + ['preview' => false, 'editor' => false, 'instant' => false, 'base' => ''];
    }

    public function render(array $page): string
    {
        $member = Auth::member();
        $locked = !empty($page['membersOnly']) && !$member && !$this->ctx['editor'];
        $body = $locked ? View::capture('site/locked', ['r' => $this, 'page' => $page]) : $this->sections($page);
        return $this->document($page, $body);
    }

    private function document(array $page, string $body, string $bodyClass = ''): string
    {
        return View::render('site/page', [
            'r' => $this,
            'page' => $page,
            'site' => Settings::site(),
            'member' => Auth::member(),
            'body' => $body,
            'bodyClass' => $bodyClass,
        ], 'site');
    }

    /** 渲染會員中心、登入頁等非區塊頁面時，套用官網外框 */
    public function wrap(string $title, string $content, string $bodyClass = ''): string
    {
        $page = ['id' => '_', 'title' => $title, 'seoTitle' => $title . '｜' . Settings::site()['name'], 'seoDescription' => '', 'seoImage' => '', 'sections' => []];
        return $this->document($page, $content, $bodyClass);
    }

    public function sections(array $page): string
    {
        $out = '';
        $this->index = 0;
        foreach ($page['sections'] as $s) {
            if (!empty($s['hidden']) && !$this->ctx['editor']) {
                continue;
            }
            $out .= $this->section($s);
            $this->index++;
        }
        if ($out === '' && $this->ctx['editor']) {
            $out = '<section class="sec bg-alt pad-xl"><div class="wrap" style="text-align:center"><p class="sec-intro">這個頁面還沒有內容，從左側「＋ 新增區塊」開始吧。</p></div></section>';
        }
        return $out;
    }

    public function section(array $s): string
    {
        $this->section = $s;
        $st = $s['style'];
        $classes = ['sec', 'sec-' . $s['type'], 'bg-' . $st['bg'], 'pad-' . $st['padding'], 'al-' . $st['align']];
        if ($st['hideOn'] !== 'none') {
            $classes[] = 'hide-' . $st['hideOn'];
        }
        if (!empty($s['hidden'])) {
            $classes[] = 'is-hidden-sec';
        }
        $style = '';
        if ($st['bg'] === 'custom') {
            $style = Palette::toneVars('s', Palette::tone($st['bgColor'], $this->doc['theme']));
            $classes[] = 'has-tone';
        }
        if ($st['bg'] === 'image' && $st['bgImage'] === '') {
            $classes[] = 'bg-dark';
        }
        $id = $st['anchor'] !== '' ? $st['anchor'] : $s['id'];
        $attrs = 'id="' . e($id) . '" class="' . e(implode(' ', $classes)) . '" data-anim="' . e($st['anim']) . '"';
        if ($this->ctx['editor']) {
            $attrs .= ' data-sid="' . e($s['id']) . '" data-type="' . e($s['type']) . '"';
        }
        if ($style !== '') {
            $attrs .= ' style="' . e($style) . '"';
        }
        $bg = '';
        if ($st['bg'] === 'image' && $st['bgImage'] !== '') {
            $bg = '<div class="sec-bg' . ($st['bgFixed'] ? ' is-fixed' : '') . '" style="background-image:url(\'' . e(media_url($st['bgImage'])) . '\')"></div>'
                . '<div class="sec-overlay" style="opacity:' . round($st['bgOverlay'] / 100, 2) . '"></div>';
        }
        $inner = View::capture('site/sections/' . $s['type'], ['r' => $this, 's' => $s, 'd' => $s['data'], 'st' => $st]);
        $this->section = null;
        return '<section ' . $attrs . '>' . $bg . $inner . "</section>\n";
    }

    /** 第一個區塊是主視覺時，頁首改為透明浮在上方；回傳主視覺底色深淺（dark/light），否則空字串 */
    public function heroMode(array $page): string
    {
        if (($this->doc['header']['style'] ?? 'auto') !== 'auto') {
            return '';
        }
        $t = $this->doc['theme'];
        foreach ($page['sections'] as $s) {
            if (!empty($s['hidden']) && !$this->ctx['editor']) {
                continue;
            }
            if ($s['type'] !== 'hero') {
                return '';
            }
            if ($s['data']['image'] !== '' && $s['data']['layout'] !== 'split') {
                return 'dark';
            }
            $bg = match ($s['style']['bg']) {
                'alt' => $t['surface'],
                'primary' => $t['primary'],
                'dark' => $t['dark'],
                'accent' => $t['accent'],
                'custom' => $s['style']['bgColor'],
                'image' => $s['style']['bgImage'] !== '' ? '#000000' : $t['dark'],
                default => $t['bg'],
            };
            return Palette::isDark($bg) ? 'dark' : 'light';
        }
        return '';
    }

    public function sectionIndex(): int
    {
        return $this->index;
    }

    /** 第一個主視覺使用 h1，其餘使用 h2（SEO） */
    public function heroTag(): string
    {
        if ($this->firstHero) {
            $this->firstHero = false;
            return 'h1';
        }
        return 'h2';
    }

    public function wrapClass(): string
    {
        return 'wrap w-' . ($this->section['style']['width'] ?? 'normal');
    }

    /** 後台即時編輯用的欄位標記 */
    public function ed(string $path): string
    {
        return $this->ctx['editor'] ? ' data-edit="' . e($path) . '"' : '';
    }

    public function nl(string $text): string
    {
        return nl2br(e($text), false);
    }

    /** 共用的區塊標題（小標／標題／說明） */
    public function head(array $d, int $i = 0): string
    {
        $eyebrow = $d['eyebrow'] ?? '';
        $heading = $d['heading'] ?? '';
        $intro = $d['intro'] ?? '';
        if ($eyebrow === '' && $heading === '' && $intro === '') {
            return '';
        }
        $html = '<header class="sec-head rv" style="--i:' . $i . '">';
        if ($eyebrow !== '') {
            $html .= '<p class="eyebrow"' . $this->ed('eyebrow') . '>' . e($eyebrow) . '</p>';
        }
        if ($heading !== '') {
            $html .= '<h2 class="sec-title"' . $this->ed('heading') . '>' . $this->nl($heading) . '</h2>';
        }
        if ($intro !== '') {
            $html .= '<p class="sec-intro"' . $this->ed('intro') . '>' . $this->nl($intro) . '</p>';
        }
        return $html . '</header>';
    }

    /** 解析連結：page:頁面ID#錨點、#錨點、站內路徑、外部網址 */
    public function url(string $url): string
    {
        if ($url === '') {
            return '';
        }
        if (str_starts_with($url, 'page:')) {
            [$id, $anchor] = array_pad(explode('#', substr($url, 5), 2), 2, '');
            $page = SiteDoc::findById($this->doc, $id);
            if (!$page) {
                return '#';
            }
            return $this->pageUrl($page) . ($anchor !== '' ? '#' . $anchor : '');
        }
        if ($url[0] === '/' && !str_starts_with($url, '//')) {
            return url($url);
        }
        return $url;
    }

    public function pageUrl(array $page): string
    {
        $isHome = $page['id'] === ($this->doc['homeId'] ?? '');
        if ($this->ctx['base'] !== '') {
            return url($this->ctx['base'] . ($isHome ? '' : '/' . $page['slug'])) . ($this->ctx['editor'] ? '?editor=1' : '');
        }
        return url($isHome ? '/' : '/' . $page['slug']);
    }

    /** href 與外部連結屬性 */
    public function href(string $url): string
    {
        $resolved = $this->url($url);
        if ($resolved === '') {
            return 'href="#"';
        }
        $attrs = 'href="' . e($resolved) . '"';
        if (Url::isExternal($resolved)) {
            $attrs .= ' target="_blank" rel="noopener"';
        }
        return $attrs;
    }

    public function button(array $b, string $path, string $extra = ''): string
    {
        if (($b['label'] ?? '') === '') {
            return '';
        }
        $style = in_array($b['style'] ?? '', ['primary', 'outline', 'ghost'], true) ? $b['style'] : 'primary';
        $arrow = $style === 'ghost' ? icon('arrow-right', 'btn-arrow') : '';
        return '<a class="btn btn-' . $style . ' ' . e($extra) . '" ' . $this->href((string) ($b['url'] ?? '')) . '><span' . $this->ed($path . '.label') . '>' . e($b['label']) . '</span>' . $arrow . '</a>';
    }

    public function buttons(array $list, string $path = 'buttons'): string
    {
        $html = '';
        foreach ($list as $i => $b) {
            $html .= $this->button($b, "{$path}.{$i}");
        }
        return $html !== '' ? '<div class="btn-row">' . $html . '</div>' : '';
    }

    public function img(string $src, string $alt = '', string $class = '', bool $eager = false): string
    {
        $url = media_url($src);
        if ($url === '') {
            return '';
        }
        return '<img src="' . e($url) . '" alt="' . e($alt) . '"' . ($class !== '' ? ' class="' . e($class) . '"' : '')
            . ($eager ? ' fetchpriority="high"' : ' loading="lazy"') . ' decoding="async">';
    }

    public function icon(string $name, string $class = ''): string
    {
        return Icons::svg($name, $class);
    }

    public function isEditor(): bool
    {
        return (bool) $this->ctx['editor'];
    }

    /** 已發布連結頁（提供給「連結按鈕」區塊與頁尾社群圖示） */
    public function linksDoc(): array
    {
        if ($this->linksDoc === null) {
            $this->linksDoc = ($this->ctx['preview'] ? Documents::draft('links') : Documents::published('links')) ?? LinkPage::defaults();
        }
        return $this->linksDoc;
    }

    public function socials(): array
    {
        return array_values(array_filter($this->linksDoc()['socials'] ?? [], static fn ($s) => ($s['url'] ?? '') !== ''));
    }

    /** 選單：顯示在選單的頁面＋額外連結 */
    public function nav(): array
    {
        $items = [];
        foreach ($this->doc['pages'] as $p) {
            if (!empty($p['showInNav'])) {
                $items[] = ['label' => $p['title'], 'href' => $this->pageUrl($p), 'id' => $p['id']];
            }
        }
        foreach ($this->doc['header']['menu'] ?? [] as $m) {
            if (($m['label'] ?? '') !== '') {
                $items[] = ['label' => $m['label'], 'href' => $this->url((string) $m['url']), 'id' => ''];
            }
        }
        return $items;
    }

    /** 聯絡表單防機器人權杖（含產生時間，簽章防偽造） */
    public function formToken(): string
    {
        $ts = (string) time();
        return $ts . '.' . substr(Crypto::sign('form|' . $ts), 0, 32);
    }

    /** 主題 CSS 變數 */
    public function themeCss(): string
    {
        $t = $this->doc['theme'];
        $vars = [
            '--c-primary' => $t['primary'], '--c-accent' => $t['accent'], '--c-bg' => $t['bg'], '--c-surface' => $t['surface'],
            '--c-text' => $t['text'], '--c-muted' => $t['muted'], '--c-dark' => $t['dark'],
            '--c-on-primary' => Palette::onColor($t['primary']), '--c-on-accent' => Palette::onColor($t['accent']),
            '--radius' => $t['radius'] . 'px',
            '--radius-btn' => match ($t['buttonShape']) { 'pill' => '999px', 'square' => '4px', default => max(6, (int) $t['radius'] - 4) . 'px' },
            '--font-heading' => Fonts::stack($t['headingFont']),
            '--font-body' => Fonts::stack($t['bodyFont']),
        ];
        $css = ':root{';
        foreach ($vars as $k => $v) {
            $css .= $k . ':' . $v . ';';
        }
        foreach (['default' => $t['bg'], 'alt' => $t['surface'], 'primary' => $t['primary'], 'dark' => $t['dark'], 'accent' => $t['accent']] as $name => $bg) {
            $css .= Palette::toneVars('t-' . $name, Palette::tone($bg, $t));
        }
        $css .= Palette::toneVars('t-image', Palette::tone('#101014', $t));
        return $css . '}';
    }

    public function fontsUrl(): string
    {
        return Fonts::googleUrl([$this->doc['theme']['headingFont'], $this->doc['theme']['bodyFont']]);
    }

    public function currentSection(): ?array
    {
        return $this->section;
    }
}
