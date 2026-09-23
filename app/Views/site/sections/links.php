<?php
/** @var App\Services\SiteRenderer $r @var array $d */
$doc = $r->linksDoc();
$links = App\Services\LinkPage::visibleLinks($doc);
?>
<div class="<?= $r->wrapClass() ?>">
  <?= $r->head($d) ?>
  <?php if ($d['showSocials'] && $r->socials()): ?>
    <div class="socials center rv"><?php foreach ($r->socials() as $so): ?><a href="<?= e($so['url']) ?>" target="_blank" rel="noopener" aria-label="<?= e(App\Services\Icons::label($so['icon'])) ?>"><?= $r->icon($so['icon']) ?></a><?php endforeach; ?></div>
  <?php endif; ?>
  <div class="link-grid cols-<?= e($d['columns']) ?>">
    <?php foreach ($links as $l): ?>
      <?php if ($l['type'] === 'header'): ?>
        <h3 class="link-grid-title rv"><?= e($l['title']) ?></h3>
      <?php else: ?>
        <a class="link-btn rv" href="<?= e($r->url($l['url'])) ?>" data-link="<?= e($l['id']) ?>"<?= $l['newTab'] && App\Services\Url::isExternal($l['url']) ? ' target="_blank" rel="noopener"' : '' ?>>
          <?php if ($l['thumb'] !== ''): ?><?= $r->img($l['thumb'], '', 'link-thumb') ?><?php elseif ($l['icon'] !== ''): ?><?= $r->icon($l['icon'], 'link-icon') ?><?php endif; ?>
          <span><?= e($l['title']) ?><?php if ($l['desc'] !== ''): ?><small><?= e($l['desc']) ?></small><?php endif; ?></span>
          <?= icon('arrow-right', 'link-arrow') ?>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</div>
