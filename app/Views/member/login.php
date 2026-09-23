<?php /** @var array $providers @var string $return @var array $flashes */ $site = App\Core\Settings::site(); ?>
<div class="m-card m-login">
  <div class="m-moon" aria-hidden="true"></div>
  <h1>會員登入／註冊</h1>
  <p class="m-muted">使用 LINE 或 Google 帳號一鍵登入，不用另外記密碼。</p>
  <?= App\Core\View::capture('member/flashes', ['flashes' => $flashes]) ?>
  <?php if ($providers): ?>
    <div class="login-buttons m-stack">
      <?php foreach ($providers as $p): ?><?= App\Core\View::capture('member/login-button', ['provider' => $p, 'return' => $return]) ?><?php endforeach; ?>
    </div>
    <p class="m-note">登入即表示你同意 <?= e($site['name']) ?> 使用你在 LINE／Google 的公開資料（名稱、大頭貼、Email）建立會員帳號。<?php if (in_array('line', $providers, true)): ?>使用 LINE 登入並加入官方帳號好友，就能收到最新消息通知。<?php endif; ?></p>
  <?php else: ?>
    <div class="m-alert m-err">會員系統尚未開放，請稍後再來。</div>
  <?php endif; ?>
  <a class="m-back" href="<?= e(url('/')) ?>">← 回到首頁</a>
</div>
