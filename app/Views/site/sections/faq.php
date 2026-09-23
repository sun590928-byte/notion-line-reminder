<?php /** @var App\Services\SiteRenderer $r @var array $d */ ?>
<div class="<?= $r->wrapClass() ?>">
  <?= $r->head($d) ?>
  <div class="faq">
    <?php foreach ($d['items'] as $i => $it): ?>
      <details class="faq-item rv"<?= $i === 0 && $d['openFirst'] ? ' open' : '' ?>>
        <summary><span<?= $r->ed("items.$i.q") ?>><?= e($it['q']) ?></span><i class="faq-mark" aria-hidden="true"></i></summary>
        <div class="faq-a"><p<?= $r->ed("items.$i.a") ?>><?= $r->nl($it['a']) ?></p></div>
      </details>
    <?php endforeach; ?>
  </div>
</div>
