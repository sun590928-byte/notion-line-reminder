<?php
/** @var array $member @var array $identities @var array $providers @var ?array $contact @var string $addFriendUrl @var array $flashes */
$hasLine = isset($identities['line']);
$isFriend = $contact && (int) $contact['is_friend'] === 1;
?>
<div class="m-card m-center">
  <div class="m-head">
    <?php if (!empty($member['avatar_url'])): ?><img class="m-avatar" src="<?= e($member['avatar_url']) ?>" alt="" referrerpolicy="no-referrer"><?php else: ?><span class="m-avatar m-avatar-ph"><?= e(mb_substr($member['display_name'], 0, 1)) ?></span><?php endif; ?>
    <div>
      <h1><?= e($member['display_name']) ?></h1>
      <p class="m-muted">會員編號 #<?= (int) $member['id'] ?>・<?= e(fmt_date($member['created_at'], 'Y/m/d')) ?> 加入</p>
    </div>
  </div>
  <?= App\Core\View::capture('member/flashes', ['flashes' => $flashes]) ?>

  <section class="m-sec">
    <h2>個人資料</h2>
    <form method="post" action="<?= e(url('/member/profile')) ?>" class="m-form">
      <?= csrf_field() ?>
      <label>顯示名稱<input name="display_name" value="<?= e($member['display_name']) ?>" maxlength="50" required></label>
      <label>Email<input type="email" name="email" value="<?= e($member['email'] ?? '') ?>" maxlength="191" placeholder="選填"></label>
      <?php if ($hasLine): ?>
        <label class="m-switch"><input type="checkbox" name="notify_line" value="1" <?= (int) $member['notify_line'] === 1 ? 'checked' : '' ?>><span></span>透過 LINE 接收最新消息與優惠通知</label>
      <?php else: ?>
        <input type="hidden" name="notify_line" value="<?= (int) $member['notify_line'] ?>">
      <?php endif; ?>
      <button class="m-btn" type="submit">儲存</button>
    </form>
  </section>

  <section class="m-sec">
    <h2>登入方式</h2>
    <ul class="m-ids">
      <?php foreach (App\Services\OAuth::LABELS as $key => $label): $id = $identities[$key] ?? null; ?>
        <li>
          <span class="m-id-icon m-id-<?= e($key) ?>"><?= icon($key === 'line' ? 'line' : 'google') ?></span>
          <span class="m-id-text"><strong><?= e($label) ?></strong><small><?= $id ? '已綁定・' . e($id['email'] ?: $id['display_name'] ?: '') : '尚未綁定' ?></small></span>
          <?php if ($id && count($identities) > 1): ?>
            <form method="post" action="<?= e(url('/member/unlink')) ?>" onsubmit="return confirm('確定要解除綁定 <?= e($label) ?> 嗎？')">
              <?= csrf_field() ?><input type="hidden" name="provider" value="<?= e($key) ?>">
              <button class="m-link" type="submit">解除綁定</button>
            </form>
          <?php elseif (!$id && in_array($key, $providers, true)): ?>
            <a class="m-link" href="<?= e(url('/auth/' . $key . '?link=1&return=/member')) ?>">綁定</a>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <?php if ($hasLine): ?>
    <section class="m-sec">
      <h2>LINE 通知</h2>
      <?php if ($isFriend): ?>
        <p class="m-ok-line"><?= icon('circle-check') ?> 你已加入官方帳號好友，最新消息會透過 LINE 通知你。</p>
      <?php else: ?>
        <p class="m-muted">加入 LINE 官方帳號好友，就能收到會員專屬通知。</p>
        <?php if ($addFriendUrl !== ''): ?><a class="login-btn login-line" href="<?= e($addFriendUrl) ?>" target="_blank" rel="noopener"><?= icon('line') ?><span>加入 LINE 好友</span></a><?php endif; ?>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <form method="post" action="<?= e(url('/logout')) ?>" class="m-logout">
    <?= csrf_field() ?>
    <a class="m-back" href="<?= e(url('/')) ?>">← 回到首頁</a>
    <button class="m-link" type="submit"><?= icon('log-out') ?> 登出</button>
  </form>
</div>
