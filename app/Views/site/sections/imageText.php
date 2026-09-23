<?php /** @var App\Services\SiteRenderer $r @var array $d */ ?>
<div class="<?= $r->wrapClass() ?>">
  <div class="split img-<?= e($d['imagePosition']) ?>">
    <figure class="split-media shape-<?= e($d['imageShape']) ?> hover-<?= e($d['hover']) ?> rv"<?= $d['hover'] === 'tilt' ? ' data-tilt' : '' ?>>
      <?= $r->img($d['image'], $d['heading']) ?>
    </figure>
    <div class="split-copy">
      <?php if ($d['eyebrow'] !== ''): ?><p class="eyebrow rv"<?= $r->ed('eyebrow') ?>><?= e($d['eyebrow']) ?></p><?php endif; ?>
      <?php if ($d['heading'] !== ''): ?><h2 class="sec-title rv"<?= $r->ed('heading') ?>><?= $r->nl($d['heading']) ?></h2><?php endif; ?>
      <?php if ($d['body'] !== ''): ?><div class="prose rv"<?= $r->ed('body') ?>><?= $d['body'] ?></div><?php endif; ?>
      <?php if ($d['buttons']): ?><div class="rv"><?= $r->buttons($d['buttons']) ?></div><?php endif; ?>
    </div>
  </div>
</div>
