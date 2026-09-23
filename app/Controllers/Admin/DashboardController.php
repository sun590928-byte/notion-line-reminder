<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\DB;
use App\Core\Request;
use App\Core\Schema;
use App\Core\Settings;
use App\Services\Documents;
use App\Services\LineBot;
use App\Services\OAuth;
use App\Services\Stats;

final class DashboardController extends AdminBase
{
    public function index(): void
    {
        Schema::migrate();
        $links = Documents::get('links');
        $site = Documents::get('site');
        $linksDoc = Documents::draft('links');
        $titles = array_column($linksDoc['links'], 'title', 'id');
        $top = [];
        foreach (Stats::byRef('click', 30) as $id => $hits) {
            if (isset($titles[$id])) {
                $top[] = ['title' => $titles[$id], 'hits' => $hits];
            }
            if (count($top) >= 8) {
                break;
            }
        }
        $auth = OAuth::config();
        $lineBot = LineBot::config();

        $this->page('dashboard', '儀表板', [
            'welcome' => Request::query('welcome') === '1',
            'mode' => Settings::mode(),
            'links' => $links,
            'site' => $site,
            'linksChanged' => Documents::hasChanges('links'),
            'siteChanged' => Documents::hasChanges('site'),
            'views' => Stats::daily('view', 30),
            'clicks' => Stats::daily('click', 30),
            'viewsTotal' => Stats::total('view', 30),
            'clicksTotal' => Stats::total('click', 30),
            'topLinks' => $top,
            'membersTotal' => (int) DB::value('SELECT COUNT(*) FROM {members}'),
            'membersWeek' => (int) DB::value('SELECT COUNT(*) FROM {members} WHERE `created_at` >= ?', [date('Y-m-d H:i:s', strtotime('-7 days'))]),
            'friends' => LineBot::friendsCount(),
            'unread' => (int) DB::value('SELECT COUNT(*) FROM {form_submissions} WHERE `is_read` = 0'),
            'recentMembers' => DB::all('SELECT * FROM {members} ORDER BY `id` DESC LIMIT 5'),
            'recentForms' => DB::all('SELECT * FROM {form_submissions} ORDER BY `id` DESC LIMIT 5'),
            'checklist' => [
                ['done' => $links && $links['published'] !== null, 'label' => '編輯並發布連結頁', 'href' => '/admin/links'],
                ['done' => $auth['line']['enabled'] && $auth['line']['channelId'] !== '', 'label' => '設定 LINE Login（會員登入）', 'href' => '/admin/line#login'],
                ['done' => LineBot::configured(), 'label' => '串接 LINE Messaging API（通知與推播）', 'href' => '/admin/line#bot'],
                ['done' => count(LineBot::receivers()) > 0, 'label' => '綁定管理員 LINE 通知', 'href' => '/admin/line#notify'],
                ['done' => $auth['google']['enabled'] && $auth['google']['clientId'] !== '', 'label' => '設定 Google 登入（選用）', 'href' => '/admin/settings#members'],
                ['done' => str_starts_with(base_url(), 'https://'), 'label' => '網站使用 HTTPS（Plesk 可免費申請 SSL）', 'href' => '/admin/settings'],
                ['done' => $site && $site['published'] !== null, 'label' => '完成官網並發布（發布後仍可保持隱藏）', 'href' => '/admin/site'],
            ],
        ], 'dashboard');
    }
}
