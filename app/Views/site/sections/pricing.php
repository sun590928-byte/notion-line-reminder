<?php /** @var App\Services\SiteRenderer $r @var array $d */ ?>
<div class="<?= $r->wrapClass() ?>">
  <?= $r->head($d) ?>
  <div class="pricing">
    <?php foreach ($d['items'] as $i => $it): ?>
      <div class="plan rv<?= $it['highlight'] ? ' is-hl' : '' ?>" data-tilt>
        <?php if ($it['badge'] !== ''): ?><span class="plan-badge"<?= $r->ed("items.$i.badge") ?>><?= e($it['badge']) ?></span><?php endif; ?>
        <h3<?= $r->ed("items.$i.name") ?>><?= e($it['name']) ?></h3>
        <div class="plan-price"><strong<?= $r->ed("items.$i.price") ?>><?= e($it['price']) ?></strong><span<?= $r->ed("items.$i.period") ?>><?= e($it['period']) ?></span></div>
        <?php if ($it['desc'] !== ''): ?><p class="plan-desc"<?= $r->ed("items.$i.desc") ?>><?= $r->nl($it['desc']) ?></p><?php endif; ?>
        <?php $features = array_filter(array_map('trim', explode("\n", $it['features']))); if ($features): ?>
          <ul class="plan-features"><?php foreach ($features as $f): ?><li><?= icon('check') ?><?= e($f) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
        <?php if ($it['buttonLabel'] !== ''): ?><a class="btn <?= $it['highlight'] ? 'btn-primary' : 'btn-outline' ?>" <?= $r->href($it['buttonUrl'] !== '' ? $it['buttonUrl'] : '#') ?>><span<?= $r->ed("items.$i.buttonLabel") ?>><?= e($it['buttonLabel']) ?></span></a><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
