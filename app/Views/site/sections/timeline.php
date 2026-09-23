<?php /** @var App\Services\SiteRenderer $r @var array $d */ ?>
<div class="<?= $r->wrapClass() ?>">
  <?= $r->head($d) ?>
  <ol class="timeline" data-timeline>
    <?php foreach ($d['items'] as $i => $it): ?>
      <li class="tl-item rv">
        <span class="tl-dot" aria-hidden="true"></span>
        <?php if ($it['date'] !== ''): ?><span class="tl-date"<?= $r->ed("items.$i.date") ?>><?= e($it['date']) ?></span><?php endif; ?>
        <?php if ($it['title'] !== ''): ?><h3<?= $r->ed("items.$i.title") ?>><?= e($it['title']) ?></h3><?php endif; ?>
        <?php if ($it['text'] !== ''): ?><p<?= $r->ed("items.$i.text") ?>><?= $r->nl($it['text']) ?></p><?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ol>
</div>
