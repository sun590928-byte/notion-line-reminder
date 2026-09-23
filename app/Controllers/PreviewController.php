<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Settings;
use App\Core\View;
use App\Services\Documents;
use App\Services\OAuth;
use App\Services\SiteDoc;
use App\Services\SiteRenderer;

/** 草稿預覽（只有管理員看得到；後台編輯器的即時預覽也使用這裡） */
final class PreviewController
{
    public function site(array $p): void
    {
        Auth::requireAdmin();
        $doc = Documents::draft('site');
        $slug = strtolower((string) ($p['slug'] ?? ''));
        $page = $slug === '' ? SiteDoc::homePage($doc) : SiteDoc::findBySlug($doc, $slug);
        if (!$page) {
            abort(404);
        }
        $this->noCache();
        echo (new SiteRenderer($doc, [
            'preview' => true,
            'editor' => Request::query('editor') === '1',
            'instant' => Request::query('instant') === '1',
            'base' => '/preview/site',
        ]))->render($page);
    }

    public function links(): void
    {
        Auth::requireAdmin();
        $this->noCache();
        echo View::render('links/page', [
            'doc' => Documents::draft('links'),
            'site' => Settings::site(),
            'preview' => true,
            'editor' => Request::query('editor') === '1',
            'showWebsite' => Settings::mode() === 'website',
            'member' => Auth::member(),
            'providers' => OAuth::enabled(),
            'canonical' => absolute_url('/'),
        ]);
    }

    private function noCache(): void
    {
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex, nofollow');
    }
}
