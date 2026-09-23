<?php /** @var App\Services\SiteRenderer $r @var array $d */ ?>
<div class="<?= $r->wrapClass() ?>">
  <?= $r->head($d) ?>
  <?php if ($d['body'] !== ''): ?><div class="prose rv"<?= $r->ed('body') ?>><?= $d['body'] ?></div><?php endif; ?>
  <?php if ($d['buttons']): ?><div class="rv sec-actions"><?= $r->buttons($d['buttons']) ?></div><?php endif; ?>
</div>
