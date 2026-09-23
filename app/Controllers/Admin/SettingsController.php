<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Crypto;
use App\Core\DB;
use App\Core\Request;
use App\Core\Settings;
use App\Services\Documents;
use App\Services\OAuth;
use App\Services\Url;

final class SettingsController extends AdminBase
{
    public function index(): void
    {
        $auth = OAuth::config();
        $this->page('settings', '網站設定', [
            'site' => Settings::site(),
            'mode' => Settings::mode(),
            'sitePublished' => Documents::published('site') !== null,
            'auth' => $auth,
            'googleMask' => Crypto::mask(Crypto::decrypt($auth['google']['clientSecret'])),
            'googleCallback' => absolute_url('/auth/google/callback'),
            'baseUrl' => base_url(),
            'detectedUrl' => \App\Core\Request::detectedBaseUrl(),
            'server' => [
                'PHP 版本' => PHP_VERSION,
                '資料庫' => DB::driver() === 'sqlite' ? 'SQLite' : 'MySQL / MariaDB',
                '上傳上限' => ini_get('upload_max_filesize') . '（POST ' . ini_get('post_max_size') . '）',
                '記憶體上限' => ini_get('memory_limit'),
                '系統版本' => 'aftermoonF ' . AMF_VERSION,
            ],
        ], 'settings');
    }

    public function save(): void
    {
        $this->api();
        $section = Request::str('section');
        if ($section === 'general') {
            $url = rtrim(Request::str('url'), '/');
            if ($url !== '' && !preg_match('#^https?://[A-Za-z0-9.\-]+(:\d+)?(/[^\s]*)?$#', $url)) {
                json_response(['ok' => false, 'error' => '網站網址格式不正確'], 422);
            }
            $name = mb_substr(Request::str('name'), 0, 80);
            if ($name === '') {
                json_response(['ok' => false, 'error' => '請填寫網站名稱'], 422);
            }
            Settings::merge('site', [
                'name' => $name,
                'tagline' => mb_substr(Request::str('tagline'), 0, 120),
                'url' => $url,
                'logo' => Url::image(Request::str('logo')),
                'favicon' => Url::image(Request::str('favicon')),
                'contactEmail' => filter_var(Request::str('contactEmail'), FILTER_VALIDATE_EMAIL) ?: '',
            ]);
        } elseif ($section === 'seo') {
            $ga = strtoupper(Request::str('gaId'));
            if ($ga !== '' && !preg_match('/^G-[A-Z0-9]{4,20}$/', $ga)) {
                json_response(['ok' => false, 'error' => 'GA4 評估 ID 格式應為 G-XXXXXXXXXX'], 422);
            }
            Settings::merge('site', [
                'seoDescription' => mb_substr(Request::str('seoDescription'), 0, 300),
                'ogImage' => Url::image(Request::str('ogImage')),
                'gaId' => $ga,
            ]);
        } elseif ($section === 'code') {
            Settings::merge('site', [
                'headCode' => mb_substr((string) Request::input('headCode', ''), 0, 20000),
                'bodyCode' => mb_substr((string) Request::input('bodyCode', ''), 0, 20000),
            ]);
        } elseif ($section === 'members') {
            $auth = OAuth::config();
            $auth['allowRegistration'] = Request::bool('allowRegistration');
            $g = $auth['google'];
            $g['enabled'] = Request::bool('googleEnabled');
            $g['clientId'] = trim(Request::str('googleClientId'));
            $secret = preg_replace('/\s+/', '', Request::str('googleClientSecret')) ?? '';
            if ($secret !== '') {
                $g['clientSecret'] = Crypto::encrypt($secret);
            }
            if ($g['enabled'] && ($g['clientId'] === '' || $g['clientSecret'] === '')) {
                json_response(['ok' => false, 'error' => '啟用 Google 登入前請填寫用戶端 ID 與密鑰'], 422);
            }
            $auth['google'] = $g;
            Settings::set('auth', $auth);
        } else {
            json_response(['ok' => false, 'error' => '未知的設定區塊'], 400);
        }
        json_response(['ok' => true]);
    }

    /** 首頁模式：links（連結頁，官網隱藏）／website（公開官網）／maintenance（即將推出） */
    public function mode(): void
    {
        $this->api();
        $mode = Request::str('mode');
        if (!in_array($mode, ['links', 'website', 'maintenance'], true)) {
            json_response(['ok' => false, 'error' => '未知的模式'], 400);
        }
        if ($mode === 'website' && Documents::published('site') === null) {
            json_response(['ok' => false, 'error' => '官網還沒有發布過。請先到「官方網站」編輯器按「發布」。'], 422);
        }
        Settings::set('mode', $mode);
        json_response(['ok' => true, 'mode' => $mode]);
    }
}
