<?php /** @var App\Services\SiteRenderer $r @var array $d */ $items = array_values(array_filter($d['items'], static fn ($it) => $it['image'] !== '')); ?>
<div class="<?= $r->wrapClass() ?>">
  <?= $r->head($d) ?>
  <div class="gallery g-<?= e($d['layout']) ?> cols-<?= e($d['columns']) ?>"<?= $d['lightbox'] ? ' data-lightbox' : '' ?>>
    <?php foreach ($items as $i => $it): ?>
      <figure class="g-item rv">
        <a href="<?= e(media_url($it['image'])) ?>" class="g-link"<?= $it['caption'] !== '' ? ' data-caption="' . e($it['caption']) . '"' : '' ?>>
          <?= $r->img($it['image'], $it['caption']) ?>
        </a>
        <?php if ($it['caption'] !== ''): ?><figcaption><?= e($it['caption']) ?></figcaption><?php endif; ?>
      </figure>
    <?php endforeach; ?>
  </div>
  <?php if ($d['layout'] === 'carousel' && count($items) > 1): ?>
    <div class="carousel-nav"><button type="button" data-dir="-1" aria-label="上一張"><?= icon('chevron-left') ?></button><button type="button" data-dir="1" aria-label="下一張"><?= icon('chevron-right') ?></button></div>
  <?php endif; ?>
</div>
