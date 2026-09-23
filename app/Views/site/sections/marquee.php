<?php
/** @var App\Services\SiteRenderer $r @var array $d */
$items = array_values(array_filter($d['items'], static fn ($it) => $it['text'] !== ''));
$sep = $d['separator'] !== '' ? '<span class="mq-sep" aria-hidden="true">' . e($d['separator']) . '</span>' : '';
$run = '';
foreach ($items as $i => $it) {
    $run .= '<span class="mq-item' . ($d['outline'] && $i % 2 === 1 ? ' is-outline' : '') . '">' . e($it['text']) . '</span>' . $sep;
}
?>
<div class="marquee mq-<?= e($d['speed']) ?> mq-<?= e($d['size']) ?><?= $d['reverse'] ? ' is-reverse' : '' ?> rv" aria-label="<?= e(implode('、', array_column($items, 'text'))) ?>">
  <div class="mq-track" aria-hidden="true">
    <div class="mq-run"><?= $run ?></div><div class="mq-run"><?= $run ?></div><div class="mq-run"><?= $run ?></div><div class="mq-run"><?= $run ?></div>
  </div>
</div>
