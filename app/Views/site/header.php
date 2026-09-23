<?php
/** @var App\Services\SiteRenderer $r @var array $page @var array $site @var ?array $member */
$h = $r->doc['header'];
$heroMode = $r->heroMode($page);
$home = App\Services\SiteDoc::homePage($r->doc);
$providers = App\Services\OAuth::enabled();
$memberUrl = $member ? url('/member') : url('/login?return=' . rawurlencode(App\Core\Request::path()));
?>
<header class="site-header<?= $heroMode !== '' ? ' over-hero is-' . $heroMode : '' ?>"<?= $h['hideOnScroll'] ? ' data-autohide' : '' ?>>
  <div class="wrap w-wide header-inner">
    <a class="logo" href="<?= e($home ? $r->pageUrl($home) : url('/')) ?>">
      <?php if ($h['logo'] !== ''): ?><img src="<?= e(media_url($h['logo'])) ?>" alt="<?= e($site['name']) ?>"><?php endif; ?>
      <?php if ($h['showName'] || $h['logo'] === ''): ?><span><?= e($site['name']) ?></span><?php endif; ?>
    </a>
    <nav class="main-nav" id="main-nav" aria-label="主選單">
      <ul>
        <?php foreach ($r->nav() as $item): ?>
          <li><a href="<?= e($item['href']) ?>"<?= $item['id'] === $page['id'] ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <div class="nav-actions">
        <?php if ($h['showMember'] && $providers): ?>
          <?php if ($member): ?>
            <a class="member-chip" href="<?= e($memberUrl) ?>">
              <?php if (!empty($member['avatar_url'])): ?><img src="<?= e($member['avatar_url']) ?>" alt="" referrerpolicy="no-referrer"><?php else: ?><?= icon('user') ?><?php endif; ?>
              <span>會員中心</span>
            </a>
          <?php else: ?>
            <a class="btn btn-outline btn-sm" href="<?= e($memberUrl) ?>"><?= icon('log-in') ?><span>會員登入</span></a>
          <?php endif; ?>
        <?php endif; ?>
        <?php if ($h['ctaLabel'] !== ''): ?><a class="btn btn-primary btn-sm" <?= $r->href($h['ctaUrl']) ?>><?= e($h['ctaLabel']) ?></a><?php endif; ?>
      </div>
    </nav>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-nav" aria-label="開啟選單"><span></span><span></span><span></span></button>
  </div>
</header>
