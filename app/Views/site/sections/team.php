<?php /** @var App\Services\SiteRenderer $r @var array $d */ ?>
<div class="<?= $r->wrapClass() ?>">
  <?= $r->head($d) ?>
  <div class="grid cols-<?= e($d['columns']) ?> team">
    <?php foreach ($d['items'] as $i => $it): ?>
      <div class="member rv">
        <div class="member-photo"><?php if ($it['photo'] !== ''): ?><?= $r->img($it['photo'], $it['name']) ?><?php else: ?><span><?= e(mb_substr($it['name'], 0, 1)) ?></span><?php endif; ?></div>
        <h3<?= $r->ed("items.$i.name") ?>><?php if ($it['url'] !== ''): ?><a <?= $r->href($it['url']) ?>><?= e($it['name']) ?></a><?php else: ?><?= e($it['name']) ?><?php endif; ?></h3>
        <?php if ($it['role'] !== ''): ?><p class="member-role"<?= $r->ed("items.$i.role") ?>><?= e($it['role']) ?></p><?php endif; ?>
        <?php if ($it['bio'] !== ''): ?><p class="member-bio"<?= $r->ed("items.$i.bio") ?>><?= $r->nl($it['bio']) ?></p><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
