<?php /** @var App\Services\SiteRenderer $r @var array $d */ ?>
<div class="<?= $r->wrapClass() ?>">
  <?= $r->head($d) ?>
  <div class="grid cols-<?= e($d['columns']) ?> cards hover-<?= e($d['hover']) ?>">
    <?php foreach ($d['items'] as $i => $it): $link = $it['url'] !== ''; ?>
      <article class="card rv"<?= $d['hover'] === 'tilt' ? ' data-tilt' : '' ?>>
        <?php if ($it['image'] !== ''): ?><div class="card-media"><?= $r->img($it['image'], $it['title']) ?><?php if ($it['tag'] !== ''): ?><span class="card-tag"<?= $r->ed("items.$i.tag") ?>><?= e($it['tag']) ?></span><?php endif; ?></div><?php endif; ?>
        <div class="card-body">
          <?php if ($it['image'] === '' && $it['tag'] !== ''): ?><span class="card-tag inline"<?= $r->ed("items.$i.tag") ?>><?= e($it['tag']) ?></span><?php endif; ?>
          <?php if ($it['title'] !== ''): ?><h3<?= $r->ed("items.$i.title") ?>><?= e($it['title']) ?></h3><?php endif; ?>
          <?php if ($it['text'] !== ''): ?><p<?= $r->ed("items.$i.text") ?>><?= $r->nl($it['text']) ?></p><?php endif; ?>
          <?php if ($link): ?><a class="card-link stretched" <?= $r->href($it['url']) ?>><span<?= $r->ed("items.$i.buttonLabel") ?>><?= e($it['buttonLabel'] !== '' ? $it['buttonLabel'] : '查看更多') ?></span><?= $r->icon('arrow-right') ?></a><?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</div>
