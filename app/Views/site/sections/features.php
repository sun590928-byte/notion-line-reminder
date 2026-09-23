<?php /** @var App\Services\SiteRenderer $r @var array $d */ ?>
<div class="<?= $r->wrapClass() ?>">
  <?= $r->head($d) ?>
  <div class="grid cols-<?= e($d['columns']) ?> features style-<?= e($d['cardStyle']) ?>">
    <?php foreach ($d['items'] as $i => $it): $tag = $it['url'] !== '' ? 'a' : 'div'; ?>
      <<?= $tag ?> class="feature rv"<?= $tag === 'a' ? ' ' . $r->href($it['url']) : '' ?>>
        <?php if ($it['icon'] !== ''): ?><span class="feature-icon"><?= $r->icon($it['icon']) ?></span><?php endif; ?>
        <?php if ($it['title'] !== ''): ?><h3<?= $r->ed("items.$i.title") ?>><?= e($it['title']) ?></h3><?php endif; ?>
        <?php if ($it['text'] !== ''): ?><p<?= $r->ed("items.$i.text") ?>><?= $r->nl($it['text']) ?></p><?php endif; ?>
      </<?= $tag ?>>
    <?php endforeach; ?>
  </div>
</div>
