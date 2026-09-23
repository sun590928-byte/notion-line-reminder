<?php
/** @var App\Services\SiteRenderer $r @var array $d */
$member = App\Core\Auth::member();
$providers = App\Services\OAuth::enabled();
?>
<div class="<?= $r->wrapClass() ?>">
  <div class="member-cta rv">
    <?php if ($d['eyebrow'] !== ''): ?><p class="eyebrow"<?= $r->ed('eyebrow') ?>><?= e($d['eyebrow']) ?></p><?php endif; ?>
    <?php if ($member && !$r->isEditor()): ?>
      <?php if (!empty($member['avatar_url'])): ?><img class="member-cta-avatar" src="<?= e($member['avatar_url']) ?>" alt="" referrerpolicy="no-referrer"><?php endif; ?>
      <h2 class="sec-title"><?= e(str_replace('{name}', $member['display_name'], $d['welcome'])) ?></h2>
      <?php if ($d['welcomeText'] !== ''): ?><p class="sec-intro"><?= $r->nl($d['welcomeText']) ?></p><?php endif; ?>
      <div class="btn-row"><a class="btn btn-primary" href="<?= e(url('/member')) ?>"><?= e($d['buttonLabel']) ?></a></div>
    <?php else: ?>
      <h2 class="sec-title"<?= $r->ed('heading') ?>><?= e($d['heading']) ?></h2>
      <?php if ($d['text'] !== ''): ?><p class="sec-intro"<?= $r->ed('text') ?>><?= $r->nl($d['text']) ?></p><?php endif; ?>
      <?php if ($providers): ?>
        <div class="login-buttons">
          <?php foreach ($providers as $p): ?><?= App\Core\View::capture('member/login-button', ['provider' => $p, 'return' => App\Core\Request::path()]) ?><?php endforeach; ?>
        </div>
      <?php elseif ($r->ctx['preview']): ?>
        <p class="sec-intro small">（僅管理員可見：尚未設定會員登入。請到後台「LINE 整合」或「網站設定 → 會員登入」完成設定，登入按鈕就會出現在這裡。）</p>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
