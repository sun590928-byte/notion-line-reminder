<?php /** @var App\Services\SiteRenderer $r @var array $d */ ?>
<div class="<?= $r->wrapClass() ?>">
  <?= $r->head($d) ?>
  <?php ob_start(); foreach ($d['items'] as $i => $it): $tag = $it['url'] !== '' ? 'a' : 'div'; ?>
    <<?= $tag ?> class="logo-item"<?= $tag === 'a' ? ' ' . $r->href($it['url']) : '' ?> title="<?= e($it['name']) ?>">
      <?php if ($it['image'] !== ''): ?><?= $r->img($it['image'], $it['name']) ?><?php else: ?><span class="logo-text"><?= e($it['name']) ?></span><?php endif; ?>
    </<?= $tag ?>>
  <?php endforeach; $logos = ob_get_clean(); ?>
  <?php if ($d['scroll']): ?>
    <div class="logos is-scroll<?= $d['grayscale'] ? ' is-gray' : '' ?> rv"><div class="mq-track"><div class="mq-run"><?= $logos ?></div><div class="mq-run" aria-hidden="true"><?= $logos ?></div></div></div>
  <?php else: ?>
    <div class="logos<?= $d['grayscale'] ? ' is-gray' : '' ?> rv"><?= $logos ?></div>
  <?php endif; ?>
</div>
