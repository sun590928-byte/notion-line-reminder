<?php
/** @var App\Services\SiteRenderer $r @var array $d */
$tag = $r->heroTag();
$split = $d['layout'] === 'split';
$hasMedia = $d['image'] !== '' && !$split;
$effect = $d['titleEffect'];
$titleClass = 'hero-title' . ($effect === 'chars' ? ' split-text' : ($effect === 'fade' ? ' rv' : ''));
?>
<div class="hero hero-<?= e($d['layout']) ?> h-<?= e($d['height']) ?><?= $hasMedia ? ' has-media' : '' ?>">
  <?php if ($hasMedia): ?>
    <div class="hero-media"<?= $d['parallax'] ? ' data-parallax="0.3"' : '' ?>><?= $r->img($d['image'], '', 'hero-img', $r->sectionIndex() === 0) ?></div>
    <div class="hero-overlay" style="opacity:<?= round($d['overlay'] / 100, 2) ?>"></div>
  <?php else: ?>
    <div class="aurora" aria-hidden="true"><span></span><span></span><span></span></div>
  <?php endif; ?>
  <div class="<?= $r->wrapClass() ?> hero-inner">
    <div class="hero-copy">
      <?php if ($d['eyebrow'] !== ''): ?><p class="eyebrow rv"<?= $r->ed('eyebrow') ?>><?= e($d['eyebrow']) ?></p><?php endif; ?>
      <?php if ($d['title'] !== ''): ?><<?= $tag ?> class="<?= $titleClass ?>"<?= $r->ed('title') ?>><?= $r->nl($d['title']) ?></<?= $tag ?>><?php endif; ?>
      <?php if ($d['subtitle'] !== ''): ?><p class="hero-sub rv"<?= $r->ed('subtitle') ?>><?= $r->nl($d['subtitle']) ?></p><?php endif; ?>
      <?php if ($d['buttons']): ?><div class="rv"><?= $r->buttons($d['buttons']) ?></div><?php endif; ?>
    </div>
    <?php if ($split && $d['image'] !== ''): ?>
      <div class="hero-figure rv"<?= $d['parallax'] ? ' data-parallax="-0.08"' : '' ?>><?= $r->img($d['image'], '', '', $r->sectionIndex() === 0) ?></div>
    <?php endif; ?>
  </div>
  <?php if ($d['scrollHint']): ?>
    <a class="scroll-hint" href="#after-<?= e($r->currentSection()['id']) ?>" aria-label="向下捲動"><span></span></a>
  <?php endif; ?>
</div>
<?php if ($d['scrollHint']): ?><span id="after-<?= e($r->currentSection()['id']) ?>" class="anchor-point"></span><?php endif; ?>
