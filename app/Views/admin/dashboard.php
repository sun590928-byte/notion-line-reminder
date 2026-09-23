<?php
/**
 * @var bool $welcome @var string $mode @var ?array $links @var ?array $site @var bool $linksChanged @var bool $siteChanged
 * @var array $views @var array $clicks @var int $viewsTotal @var int $clicksTotal @var array $topLinks
 * @var int $membersTotal @var int $membersWeek @var int $friends @var int $unread @var array $recentMembers @var array $recentForms @var array $checklist
 */
$sitePublished = $site && $site['published'] !== null;
$maxTop = $topLinks ? max(1, max(array_column($topLinks, 'hits'))) : 1;
$compact = static function (int $n): string {
    if ($n >= 10000) {
        return rtrim(rtrim(number_format($n / 10000, 1), '0'), '.') . ' 萬';
    }
    return number_format($n);
};
$done = count(array_filter(array_column($checklist, 'done')));
?>
<?php if ($welcome): ?>
  <div class="banner">
    <?= icon('party-popper') ?>
    <div>
      <strong>安裝完成，歡迎使用！</strong>
      <p>目前網站首頁顯示「連結頁」，官方網站已建立為<b>未發布的草稿</b>並保持隱藏。你可以先編輯連結頁與設定 LINE 登入，官網準備好後再一鍵公開。</p>
    </div>
  </div>
<?php endif; ?>

<section class="mode-switch card" data-mode-switch>
  <div class="card-head">
    <h2>首頁模式</h2>
    <p class="muted">決定訪客打開網站首頁時看到什麼。切換後立即生效。</p>
  </div>
  <div class="mode-options">
    <label class="mode-opt">
      <input type="radio" name="mode" value="links" <?= $mode === 'links' ? 'checked' : '' ?>>
      <span class="mode-card"><?= icon('link') ?><strong>連結頁</strong><small>Linktree 風格的連結頁，官網隱藏（目前建議）</small></span>
    </label>
    <label class="mode-opt">
      <input type="radio" name="mode" value="website" <?= $mode === 'website' ? 'checked' : '' ?>>
      <span class="mode-card"><?= icon('globe') ?><strong>官方網站</strong><small>公開官網，連結頁移到 /links<?= $sitePublished ? '' : '（需先發布官網）' ?></small></span>
    </label>
    <label class="mode-opt">
      <input type="radio" name="mode" value="maintenance" <?= $mode === 'maintenance' ? 'checked' : '' ?>>
      <span class="mode-card"><?= icon('moon') ?><strong>即將推出</strong><small>所有公開頁面暫時隱藏，只顯示預告頁</small></span>
    </label>
  </div>
</section>

<div class="grid-2">
  <section class="card doc-card">
    <div class="doc-card-icon"><?= icon('link') ?></div>
    <div class="doc-card-body">
      <h2>連結頁</h2>
      <p class="muted">
        <?php if ($links && $links['published'] !== null): ?>已發布・<?= e(time_ago($links['published_at'])) ?><?php else: ?>尚未發布<?php endif; ?>
        <?php if ($linksChanged): ?><span class="badge warn">有未發布的變更</span><?php endif; ?>
      </p>
      <div class="btn-row">
        <a class="btn btn-primary" href="<?= e(url('/admin/links')) ?>"><?= icon('pencil') ?>編輯連結頁</a>
        <a class="btn" href="<?= e(url($mode === 'links' ? '/' : '/links')) ?>" target="_blank" rel="noopener"><?= icon('external-link') ?>查看</a>
      </div>
    </div>
  </section>
  <section class="card doc-card">
    <div class="doc-card-icon alt"><?= icon('globe') ?></div>
    <div class="doc-card-body">
      <h2>官方網站</h2>
      <p class="muted">
        <?php if (!$sitePublished): ?>未發布草稿（隱藏中）<?php elseif ($mode === 'website'): ?>公開中・<?= e(time_ago($site['published_at'])) ?>發布<?php else: ?>已發布，但目前隱藏<?php endif; ?>
        <?php if ($sitePublished && $siteChanged): ?><span class="badge warn">有未發布的變更</span><?php endif; ?>
      </p>
      <div class="btn-row">
        <a class="btn btn-primary" href="<?= e(url('/admin/site')) ?>"><?= icon('layout-template') ?>開啟官網編輯器</a>
        <a class="btn" href="<?= e(url('/preview/site')) ?>" target="_blank" rel="noopener"><?= icon('eye') ?>預覽草稿</a>
      </div>
    </div>
  </section>
</div>

<section class="kpis">
  <div class="kpi"><span class="kpi-label">瀏覽次數（近 30 天）</span><strong class="kpi-value"><?= e($compact($viewsTotal)) ?></strong></div>
  <div class="kpi"><span class="kpi-label">連結點擊（近 30 天）</span><strong class="kpi-value"><?= e($compact($clicksTotal)) ?></strong></div>
  <div class="kpi"><span class="kpi-label">會員</span><strong class="kpi-value"><?= e($compact($membersTotal)) ?></strong><span class="kpi-delta<?= $membersWeek > 0 ? ' up' : '' ?>"><?= $membersWeek > 0 ? '↑ ' : '' ?>本週新增 <?= (int) $membersWeek ?></span></div>
  <div class="kpi"><span class="kpi-label">LINE 好友</span><strong class="kpi-value"><?= e($compact($friends)) ?></strong><span class="kpi-delta">透過 Webhook 同步</span></div>
  <a class="kpi" href="<?= e(url('/admin/forms?filter=unread')) ?>"><span class="kpi-label">未讀留言</span><strong class="kpi-value"><?= (int) $unread ?></strong><span class="kpi-delta"><?= $unread > 0 ? '前往查看 →' : '都看完了' ?></span></a>
</section>

<div class="grid-2">
  <?php foreach ([['每日瀏覽次數', '瀏覽', $views], ['每日連結點擊', '點擊', $clicks]] as [$chartTitle, $unit, $series]): ?>
    <section class="card">
      <div class="card-head"><h2><?= e($chartTitle) ?></h2><p class="muted">近 30 天・不含管理員與搜尋引擎</p></div>
      <div class="chart" data-chart="<?= e(json_encode($series)) ?>" data-unit="<?= e($unit) ?>" role="img" aria-label="<?= e($chartTitle) ?>長條圖"></div>
      <details class="chart-table">
        <summary>以表格檢視</summary>
        <table class="table compact"><thead><tr><th>日期</th><th class="num"><?= e($unit) ?></th></tr></thead><tbody>
          <?php foreach (array_reverse($series) as $d): ?><tr><td><?= e(date('m/d', strtotime($d['day']))) ?></td><td class="num"><?= (int) $d['hits'] ?></td></tr><?php endforeach; ?>
        </tbody></table>
      </details>
    </section>
  <?php endforeach; ?>
</div>

<div class="grid-2">
  <section class="card">
    <div class="card-head"><h2>熱門連結</h2><p class="muted">連結頁與官網「連結按鈕」近 30 天點擊</p></div>
    <?php if ($topLinks): ?>
      <ol class="hbars">
        <?php foreach ($topLinks as $t): ?>
          <li><span class="hbar-label"><?= e($t['title']) ?></span><span class="hbar-track"><span class="hbar" style="width:<?= max(2, round($t['hits'] / $maxTop * 100)) ?>%"></span></span><span class="hbar-value"><?= (int) $t['hits'] ?></span></li>
        <?php endforeach; ?>
      </ol>
    <?php else: ?>
      <div class="empty"><?= icon('mouse-pointer-click') ?><p>還沒有點擊紀錄。連結頁公開後，訪客點擊的次數會顯示在這裡。</p></div>
    <?php endif; ?>
  </section>
  <section class="card">
    <div class="card-head"><h2>設定清單</h2><p class="muted">已完成 <?= $done ?> / <?= count($checklist) ?></p></div>
    <div class="progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="<?= count($checklist) ?>" aria-valuenow="<?= $done ?>"><span style="width:<?= round($done / max(1, count($checklist)) * 100) ?>%"></span></div>
    <ul class="checklist">
      <?php foreach ($checklist as $c): ?>
        <li class="<?= $c['done'] ? 'done' : '' ?>"><a href="<?= e(url($c['href'])) ?>"><?= icon($c['done'] ? 'circle-check' : 'circle-alert') ?><span><?= e($c['label']) ?></span><?= $c['done'] ? '<em>完成</em>' : icon('chevron-right') ?></a></li>
      <?php endforeach; ?>
    </ul>
  </section>
</div>

<div class="grid-2">
  <section class="card">
    <div class="card-head row"><h2>最新會員</h2><a class="link" href="<?= e(url('/admin/members')) ?>">全部會員 →</a></div>
    <?php if ($recentMembers): ?>
      <ul class="list">
        <?php foreach ($recentMembers as $m): ?>
          <li><a href="<?= e(url('/admin/members/' . (int) $m['id'])) ?>">
            <?php if ($m['avatar_url']): ?><img class="avatar" src="<?= e($m['avatar_url']) ?>" alt="" referrerpolicy="no-referrer"><?php else: ?><span class="avatar ph"><?= e(mb_substr($m['display_name'], 0, 1)) ?></span><?php endif; ?>
            <span class="list-main"><strong><?= e($m['display_name']) ?></strong><small><?= e($m['email'] ?: '—') ?></small></span>
            <span class="list-meta"><?= e(time_ago($m['created_at'])) ?></span>
          </a></li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <div class="empty"><?= icon('users') ?><p>還沒有會員。設定 LINE／Google 登入後，訪客就能加入會員。</p></div>
    <?php endif; ?>
  </section>
  <section class="card">
    <div class="card-head row"><h2>最新留言</h2><a class="link" href="<?= e(url('/admin/forms')) ?>">全部訊息 →</a></div>
    <?php if ($recentForms): ?>
      <ul class="list">
        <?php foreach ($recentForms as $f): ?>
          <li><a href="<?= e(url('/admin/forms#f' . (int) $f['id'])) ?>">
            <span class="avatar ph<?= (int) $f['is_read'] ? '' : ' unread' ?>"><?= icon('mail') ?></span>
            <span class="list-main"><strong><?= e($f['name']) ?></strong><small><?= e(str_limit((string) $f['message'], 60)) ?></small></span>
            <span class="list-meta"><?= e(time_ago($f['created_at'])) ?></span>
          </a></li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <div class="empty"><?= icon('inbox') ?><p>官網公開後，聯絡表單的留言會出現在這裡，並同步通知到你的 LINE。</p></div>
    <?php endif; ?>
  </section>
</div>
