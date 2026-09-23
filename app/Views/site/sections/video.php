<?php /** @var App\Services\SiteRenderer $r @var array $d */ $embed = App\Services\Url::videoEmbed($d['url']); ?>
<div class="<?= $r->wrapClass() ?>">
  <?= $r->head($d) ?>
  <div class="video ratio-<?= e($d['aspect']) ?> rv">
    <?php if ($embed !== ''): ?>
      <iframe src="<?= e($embed) ?>" title="<?= e($d['heading'] ?: '影片') ?>" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
    <?php else: ?>
      <div class="video-empty"><?= icon('play') ?><span>貼上 YouTube 或 Vimeo 影片網址</span></div>
    <?php endif; ?>
  </div>
</div>
