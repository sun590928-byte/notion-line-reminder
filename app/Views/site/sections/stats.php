<?php /** @var App\Services\SiteRenderer $r @var array $d */ ?>
<div class="<?= $r->wrapClass() ?>">
  <?= $r->head($d) ?>
  <div class="stats">
    <?php foreach ($d['items'] as $i => $it):
      $num = preg_replace('/[^0-9.]/', '', $it['value']);
      $count = $d['countUp'] && $num !== '' && is_numeric($num); ?>
      <div class="stat rv">
        <div class="stat-value"><span><?= e($it['prefix']) ?></span><span<?= $count ? ' data-count="' . e($num) . '"' : '' ?><?= $r->ed("items.$i.value") ?>><?= e($it['value']) ?></span><span><?= e($it['suffix']) ?></span></div>
        <div class="stat-label"<?= $r->ed("items.$i.label") ?>><?= e($it['label']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
