<?php /** @var App\Services\SiteRenderer $r @var array $page */ $providers = App\Services\OAuth::enabled(); ?>
<section class="sec bg-alt pad-xl locked-page">
  <div class="wrap w-narrow">
    <div class="member-cta">
      <span class="lock-icon"><?= icon('lock') ?></span>
      <h1 class="sec-title"><?= e($page['title']) ?></h1>
      <p class="sec-intro">這是會員限定頁面，登入後即可瀏覽。</p>
      <?php if ($providers): ?>
        <div class="login-buttons">
          <?php foreach ($providers as $p): ?><?= App\Core\View::capture('member/login-button', ['provider' => $p, 'return' => App\Core\Request::path()]) ?><?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
