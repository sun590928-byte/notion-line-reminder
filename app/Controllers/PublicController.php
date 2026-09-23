<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\DB;
use App\Core\Request;
use App\Core\Settings;
use App\Core\View;
use App\Services\Documents;
use App\Services\OAuth;
use App\Services\SiteDoc;
use App\Services\SiteRenderer;

/**
 * 公開頁面。首頁顯示內容由後台「首頁模式」決定：
 *   links       → 連結頁（預設，官網隱藏）
 *   website     → 官方網站（連結頁移到 /links）
 *   maintenance → 即將推出頁
 */
final class PublicController
{
    public function home(): void
    {
        $mode = Settings::mode();
        if ($mode === 'maintenance') {
            $this->comingSoon();
        }
        if ($mode === 'website') {
            $doc = Documents::published('site');
            $page = $doc ? SiteDoc::homePage($doc) : null;
            if ($doc && $page) {
                $this->track('/');
                echo (new SiteRenderer($doc))->render($page);
                return;
            }
        }
        $this->renderLinks('/');
    }

    public function links(): void
    {
        if (Settings::mode() === 'maintenance') {
            $this->comingSoon();
        }
        $this->renderLinks('/links');
    }

    public function page(array $p): void
    {
        $slug = strtolower((string) $p['slug']);
        if (Settings::mode() !== 'website') {
            abort(404);
        }
        $doc = Documents::published('site') ?? abort(404);
        $page = SiteDoc::findBySlug($doc, $slug) ?? abort(404);
        if ($page['id'] === $doc['homeId']) {
            redirect('/', 301);
        }
        $this->track('/' . $slug);
        echo (new SiteRenderer($doc))->render($page);
    }

    private function renderLinks(string $path): void
    {
        $doc = Documents::published('links');
        if (!$doc) {
            $this->comingSoon();
        }
        $mode = Settings::mode();
        $this->track($path);
        echo View::render('links/page', [
            'doc' => $doc,
            'site' => Settings::site(),
            'preview' => false,
            'editor' => false,
            'showWebsite' => $mode === 'website' && Documents::published('site') !== null,
            'member' => Auth::member(),
            'providers' => OAuth::enabled(),
            'canonical' => absolute_url($mode === 'links' ? '/' : '/links'),
        ]);
    }

    private function comingSoon(): never
    {
        http_response_code(503);
        header('Retry-After: 3600');
        $links = Documents::published('links');
        echo View::render('errors/coming-soon', [
            'title' => Settings::site()['name'],
            'site' => Settings::site(),
            'socials' => array_values(array_filter($links['socials'] ?? [], static fn ($s) => $s['url'] !== '')),
        ], 'bare');
        exit;
    }

    /** 瀏覽次數（不記錄機器人與管理員） */
    private function track(string $path): void
    {
        if (Request::isBot() || Auth::admin()) {
            return;
        }
        try {
            DB::bumpStat('view', mb_substr($path, 0, 150));
        } catch (\Throwable) {
            // 統計失敗不影響頁面顯示
        }
    }

    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        $urls = [];
        $mode = Settings::mode();
        if ($mode !== 'maintenance') {
            $urls[] = absolute_url('/');
        }
        if ($mode === 'website' && ($doc = Documents::published('site'))) {
            foreach ($doc['pages'] as $page) {
                if ($page['id'] !== $doc['homeId'] && empty($page['membersOnly'])) {
                    $urls[] = absolute_url('/' . $page['slug']);
                }
            }
            $urls[] = absolute_url('/links');
        }
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            echo '  <url><loc>' . e($u) . "</loc></url>\n";
        }
        echo "</urlset>\n";
    }

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\n";
        foreach (['/admin', '/preview', '/member', '/auth', '/api', '/login'] as $p) {
            echo 'Disallow: ' . url($p) . "\n";
        }
        echo "\nSitemap: " . absolute_url('/sitemap.xml') . "\n";
    }
}
