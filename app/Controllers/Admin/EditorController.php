<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Settings;
use App\Core\View;
use App\Services\DocumentConflict;
use App\Services\Documents;
use App\Services\Fonts;
use App\Services\LinkPage;
use App\Services\Sections;
use App\Services\SiteDoc;
use App\Services\Stats;

/** Wix 式編輯器：連結頁與官方網站（草稿自動儲存 → 預覽 → 發布） */
final class EditorController extends AdminBase
{
    public function links(): void
    {
        $row = Documents::get('links');
        $this->editor('links', '連結頁編輯器', [
            'doc' => Documents::draft('links'),
            'rev' => (int) ($row['rev'] ?? 0),
            'publishedAt' => $row['published_at'] ?? null,
            'hasChanges' => Documents::hasChanges('links'),
            'schema' => [
                'profile' => LinkPage::profileFields(),
                'link' => LinkPage::linkFields(),
                'social' => LinkPage::socialFields(),
                'theme' => LinkPage::themeFields(),
                'settings' => LinkPage::settingsFields(),
            ],
            'themes' => LinkPage::themes(),
            'clicks' => Stats::byRef('click', 30),
            'previewUrl' => url('/preview/links'),
            'publicUrl' => url(Settings::mode() === 'links' ? '/' : '/links'),
            'mode' => Settings::mode(),
        ]);
    }

    public function site(): void
    {
        $row = Documents::get('site');
        $this->editor('site', '官網編輯器', [
            'doc' => Documents::draft('site'),
            'rev' => (int) ($row['rev'] ?? 0),
            'publishedAt' => $row['published_at'] ?? null,
            'hasChanges' => Documents::hasChanges('site'),
            'schema' => [
                'sections' => Sections::forEditor(),
                'theme' => SiteDoc::themeFields(),
                'header' => SiteDoc::headerFields(),
                'footer' => SiteDoc::footerFields(),
                'page' => SiteDoc::pageFields(),
            ],
            'themes' => SiteDoc::themes(),
            'fonts' => Fonts::options(),
            'reservedSlugs' => SiteDoc::RESERVED_SLUGS,
            'previewBase' => url('/preview/site'),
            'publicUrl' => url('/'),
            'mode' => Settings::mode(),
        ]);
    }

    private function editor(string $name, string $title, array $config): void
    {
        $config += [
            'name' => $name,
            'csrf' => csrf_token(),
            'base' => Request::base(),
            'api' => url('/admin/api'),
            'admin' => $this->admin['display_name'],
        ];
        echo View::render('admin/editor', ['title' => $title, 'name' => $name, 'config' => $config], 'editor');
    }

    private function name(array $p): string
    {
        $name = (string) ($p['name'] ?? '');
        if (!in_array($name, Documents::NAMES, true)) {
            abort(404);
        }
        return $name;
    }

    public function save(array $p): void
    {
        $this->api();
        $name = $this->name($p);
        $doc = Request::input('doc');
        if (!is_array($doc)) {
            json_response(['ok' => false, 'error' => '內容格式錯誤'], 400);
        }
        try {
            $result = Documents::saveDraft($name, $doc, Request::int('rev'), Request::bool('force'));
        } catch (DocumentConflict $e) {
            json_response(['ok' => false, 'conflict' => true, 'error' => $e->getMessage()], 409);
        }
        $out = ['ok' => true, 'rev' => $result['rev'], 'hasChanges' => Documents::hasChanges($name), 'savedAt' => date('H:i:s')];
        if ($name === 'site') {
            // 回傳伺服器調整後的網址代稱，讓編輯器同步
            $out['slugs'] = array_column($result['doc']['pages'], 'slug', 'id');
            $out['homeId'] = $result['doc']['homeId'];
        }
        json_response($out);
    }

    public function publish(array $p): void
    {
        $this->api();
        $name = $this->name($p);
        Documents::publish($name, $this->admin['id'], mb_substr(Request::str('note'), 0, 200));
        $mode = Request::str('mode');
        if (in_array($mode, ['links', 'website', 'maintenance'], true)) {
            Settings::set('mode', $mode);
        }
        json_response(['ok' => true, 'publishedAt' => date('Y-m-d H:i:s'), 'hasChanges' => false, 'mode' => Settings::mode()]);
    }

    public function discard(array $p): void
    {
        $this->api();
        $name = $this->name($p);
        Documents::discard($name);
        $row = Documents::get($name);
        json_response(['ok' => true, 'doc' => $row['draft'], 'rev' => $row['rev'], 'hasChanges' => false]);
    }

    public function revisions(array $p): void
    {
        $name = $this->name($p);
        json_response(['ok' => true, 'items' => array_map(static fn ($r) => [
            'id' => (int) $r['id'],
            'note' => $r['note'],
            'at' => fmt_date($r['created_at'], 'Y/m/d H:i'),
            'by' => $r['admin_name'] ?? '',
        ], Documents::revisions($name))]);
    }

    public function restore(array $p): void
    {
        $this->api();
        $name = $this->name($p);
        Documents::restore($name, Request::int('id'));
        $row = Documents::get($name);
        json_response(['ok' => true, 'doc' => $row['draft'], 'rev' => $row['rev'], 'hasChanges' => Documents::hasChanges($name)]);
    }
}
