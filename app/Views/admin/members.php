<?php /** @var array $rows @var int $total @var int $page @var int $pages @var string $q @var string $provider @var bool $lineReady */ ?>
<section class="card" data-page="members">
  <form class="toolbar" method="get" action="<?= e(url('/admin/members')) ?>">
    <label class="search"><?= icon('search') ?><input name="q" value="<?= e($q) ?>" placeholder="搜尋名稱或 Email"></label>
    <select name="provider" onchange="this.form.submit()">
      <option value="">全部登入方式</option>
      <option value="line"<?= $provider === 'line' ? ' selected' : '' ?>>LINE</option>
      <option value="google"<?= $provider === 'google' ? ' selected' : '' ?>>Google</option>
    </select>
    <button class="btn" type="submit">搜尋</button>
    <span class="toolbar-note">共 <?= (int) $total ?> 位會員</span>
  </form>
  <?php if ($rows): ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>會員</th><th>登入方式</th><th>LINE 好友</th><th>加入時間</th><th>最後登入</th><th>狀態</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $m): $providers = array_filter(explode(',', (string) $m['providers'])); ?>
            <tr class="row-link" data-href="<?= e(url('/admin/members/' . (int) $m['id'])) ?>">
              <td><a class="person" href="<?= e(url('/admin/members/' . (int) $m['id'])) ?>">
                <?php if ($m['avatar_url']): ?><img class="avatar" src="<?= e($m['avatar_url']) ?>" alt="" referrerpolicy="no-referrer"><?php else: ?><span class="avatar ph"><?= e(mb_substr($m['display_name'], 0, 1)) ?></span><?php endif; ?>
                <span><strong><?= e($m['display_name']) ?></strong><small><?= e($m['email'] ?: '—') ?></small></span>
              </a></td>
              <td><?php foreach ($providers as $pv): ?><span class="badge <?= $pv === 'line' ? 'line' : 'muted' ?>"><?= $pv === 'line' ? 'LINE' : 'Google' ?></span> <?php endforeach; ?></td>
              <td><?= in_array('line', $providers, true) ? ((int) $m['is_friend'] === 1 ? '<span class="badge ok">已加好友</span>' : '<span class="badge muted">未加好友</span>') : '—' ?></td>
              <td><?= e(fmt_date($m['created_at'], 'Y/m/d')) ?></td>
              <td><?= e(time_ago($m['last_login_at'])) ?></td>
              <td><?= $m['status'] === 'active' ? '<span class="badge ok">正常</span>' : '<span class="badge danger">停權</span>' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($pages > 1): ?>
      <nav class="pager">
        <?php for ($i = 1; $i <= $pages; $i++): ?><a href="<?= e(url('/admin/members?' . http_build_query(['q' => $q, 'provider' => $provider, 'page' => $i]))) ?>"<?= $i === $page ? ' class="active"' : '' ?>><?= $i ?></a><?php endfor; ?>
      </nav>
    <?php endif; ?>
  <?php else: ?>
    <div class="empty"><?= icon('users') ?><p><?= $q !== '' || $provider !== '' ? '找不到符合條件的會員。' : '還沒有會員。到「LINE 整合」設定 LINE Login，或在「網站設定」設定 Google 登入後，訪客就能一鍵加入會員。' ?></p></div>
  <?php endif; ?>
</section>
