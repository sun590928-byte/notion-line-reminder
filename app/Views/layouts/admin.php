<?php
/** @var string $title @var array $admin @var string $nav @var string $content */
use App\Core\DB;
use App\Core\Settings;
use App\Services\Documents;

$mode = Settings::mode();
$unread = (int) DB::value('SELECT COUNT(*) FROM {form_submissions} WHERE `is_read` = 0');
$items = [
    ['dashboard', '/admin', 'layout-dashboard', '儀表板', ''],
    ['_label', '網站內容'],
    ['links', '/admin/links', 'link', '連結頁', Documents::hasChanges('links') ? '<i class="nav-dot" title="有未發布的變更"></i>' : ($mode === 'links' ? '<em class="nav-tag live">首頁</em>' : '')],
    ['site', '/admin/site', 'globe', '官方網站', $mode === 'website' ? '<em class="nav-tag live">公開</em>' : '<em class="nav-tag">隱藏</em>'],
    ['media', '/admin/media', 'images', '媒體庫', ''],
    ['_label', '會員與訊息'],
    ['members', '/admin/members', 'users', '會員', ''],
    ['forms', '/admin/forms', 'inbox', '表單訊息', $unread > 0 ? '<em class="nav-count">' . $unread . '</em>' : ''],
    ['line', '/admin/line', 'line', 'LINE 整合', ''],
    ['_label', '系統'],
    ['settings', '/admin/settings', 'settings', '網站設定', ''],
    ['account', '/admin/account', 'key', '管理員帳號', ''],
];
?>
<!doctype html>
<html lang="zh-Hant-TW">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="base-url" content="<?= e(url('')) ?>">
<title><?= e($title) ?>｜<?= e(Settings::site()['name']) ?> 後台</title>
<link rel="icon" href="<?= e(url('/assets/img/favicon.svg')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="adm">
<aside class="adm-side" id="adm-side">
  <a class="adm-brand" href="<?= e(url('/admin')) ?>">
    <img src="<?= e(url('/assets/img/favicon.svg')) ?>" alt="" width="34" height="34">
    <span><strong><?= e(Settings::site()['name']) ?></strong><small>網站後台</small></span>
  </a>
  <nav class="adm-nav" aria-label="後台選單">
    <?php foreach ($items as $it): ?>
      <?php if ($it[0] === '_label'): ?>
        <div class="adm-nav-label"><?= e($it[1]) ?></div>
      <?php else: ?>
        <a href="<?= e(url($it[1])) ?>"<?= $nav === $it[0] ? ' class="active" aria-current="page"' : '' ?>><?= icon($it[2]) ?><span><?= e($it[3]) ?></span><?= $it[4] ?></a>
      <?php endif; ?>
    <?php endforeach; ?>
  </nav>
  <div class="adm-side-foot">
    <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener"><?= icon('external-link') ?><span>查看網站</span></a>
    <form method="post" action="<?= e(url('/admin/logout')) ?>">
      <?= csrf_field() ?>
      <button type="submit"><?= icon('log-out') ?><span>登出（<?= e($admin['display_name']) ?>）</span></button>
    </form>
  </div>
</aside>
<div class="adm-backdrop" data-close-side></div>
<div class="adm-main">
  <header class="adm-top">
    <button class="adm-menu" type="button" data-open-side aria-label="開啟選單"><?= icon('menu') ?></button>
    <h1><?= e($title) ?></h1>
    <div class="adm-top-right">
      <span class="mode-pill mode-<?= e($mode) ?>" title="目前首頁顯示的內容"><i></i><?= $mode === 'links' ? '首頁：連結頁（官網隱藏）' : ($mode === 'website' ? '首頁：官方網站' : '首頁：即將推出') ?></span>
    </div>
  </header>
  <main class="adm-content">
    <?= $content ?>
  </main>
</div>
<div class="toasts" id="toasts" aria-live="polite"></div>
<script type="module" src="<?= e(asset('js/admin/app.js')) ?>"></script>
</body>
</html>
