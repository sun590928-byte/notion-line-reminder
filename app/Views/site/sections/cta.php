<?php /** @var App\Services\SiteRenderer $r @var array $d */ ?>
<div class="<?= $r->wrapClass() ?>">
  <div class="cta cta-<?= e($d['variant']) ?> rv">
    <?php if ($d['variant'] === 'glow'): ?><div class="aurora" aria-hidden="true"><span></span><span></span><span></span></div><?php endif; ?>
    <div class="cta-copy">
      <?php if ($d['eyebrow'] !== ''): ?><p class="eyebrow"<?= $r->ed('eyebrow') ?>><?= e($d['eyebrow']) ?></p><?php endif; ?>
      <?php if ($d['heading'] !== ''): ?><h2 class="sec-title"<?= $r->ed('heading') ?>><?= $r->nl($d['heading']) ?></h2><?php endif; ?>
      <?php if ($d['text'] !== ''): ?><p class="sec-intro"<?= $r->ed('text') ?>><?= $r->nl($d['text']) ?></p><?php endif; ?>
    </div>
    <?= $r->buttons($d['buttons']) ?>
  </div>
</div>
