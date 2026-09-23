<?php /** @var array $admins @var array $admin */ ?>
<div class="grid-2 align-start" data-page="account">
  <section class="card">
    <div class="card-head"><h2>我的帳號</h2><p class="muted">目前登入：<?= e($admin['username']) ?></p></div>
    <form class="form" data-api="<?= e(url('/admin/api/account/password')) ?>" data-reset>
      <label class="field">顯示名稱<input name="display_name" value="<?= e($admin['display_name']) ?>" maxlength="100"></label>
      <label class="field">目前的密碼<input type="password" name="current" required autocomplete="current-password"></label>
      <label class="field">新密碼（至少 8 字元）<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
      <button class="btn btn-primary" type="submit">更新密碼</button>
      <p class="muted small">更新密碼後，其他裝置上的登入狀態會自動失效。</p>
    </form>
  </section>
  <section class="card">
    <div class="card-head"><h2>管理員</h2></div>
    <ul class="list">
      <?php foreach ($admins as $a): ?>
        <li><div class="list-row">
          <span class="avatar ph"><?= icon('user') ?></span>
          <span class="list-main"><strong><?= e($a['display_name']) ?></strong><small><?= e($a['username']) ?>・最後登入 <?= e(time_ago($a['last_login_at'])) ?></small></span>
          <?php if ((int) $a['id'] !== (int) $admin['id']): ?><button class="btn btn-sm btn-danger-ghost" type="button" data-delete-admin="<?= (int) $a['id'] ?>">刪除</button><?php else: ?><span class="badge muted">你</span><?php endif; ?>
        </div></li>
      <?php endforeach; ?>
    </ul>
    <h3>新增管理員</h3>
    <form class="form" data-api="<?= e(url('/admin/api/account/admins')) ?>" data-reload>
      <div class="row-2">
        <label class="field">帳號<input name="username" required pattern="[A-Za-z0-9_.@\-]{3,50}" autocomplete="off"></label>
        <label class="field">顯示名稱<input name="display_name"></label>
      </div>
      <label class="field">密碼（至少 8 字元）<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
      <button class="btn" type="submit"><?= icon('user-plus') ?>新增管理員</button>
    </form>
  </section>
</div>
