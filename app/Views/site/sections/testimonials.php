<?php /** @var App\Services\SiteRenderer $r @var array $d */ $slider = $d['layout'] === 'slider'; ?>
<div class="<?= $r->wrapClass() ?>">
  <?= $r->head($d) ?>
  <div class="quotes <?= $slider ? 'is-slider' : 'grid cols-3' ?>"<?= $slider ? ' data-slider' : '' ?>>
    <?php foreach ($d['items'] as $i => $it): ?>
      <figure class="quote rv">
        <?php if ((int) $it['rating'] > 0): ?><div class="stars" aria-label="<?= (int) $it['rating'] ?> 顆星"><?= str_repeat('★', (int) $it['rating']) ?></div><?php endif; ?>
        <blockquote<?= $r->ed("items.$i.quote") ?>><?= $r->nl($it['quote']) ?></blockquote>
        <figcaption>
          <?php if ($it['avatar'] !== ''): ?><?= $r->img($it['avatar'], $it['name'], 'quote-avatar') ?><?php else: ?><span class="quote-avatar ph"><?= e(mb_substr($it['name'], 0, 1)) ?></span><?php endif; ?>
          <span><strong<?= $r->ed("items.$i.name") ?>><?= e($it['name']) ?></strong><?php if ($it['role'] !== ''): ?><small<?= $r->ed("items.$i.role") ?>><?= e($it['role']) ?></small><?php endif; ?></span>
        </figcaption>
      </figure>
    <?php endforeach; ?>
  </div>
  <?php if ($slider && count($d['items']) > 1): ?>
    <div class="carousel-nav"><button type="button" data-dir="-1" aria-label="上一則"><?= icon('chevron-left') ?></button><button type="button" data-dir="1" aria-label="下一則"><?= icon('chevron-right') ?></button></div>
  <?php endif; ?>
</div>
