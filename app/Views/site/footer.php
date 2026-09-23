<?php
/** @var App\Services\SiteRenderer $r @var array $site */
$f = $r->doc['footer'];
$socials = $f['showSocial'] ? $r->socials() : [];
?>
<footer class="site-footer">
  <div class="wrap w-wide footer-grid">
    <div class="footer-brand">
      <p class="footer-name"><?= e($site['name']) ?></p>
      <?php if ($f['about'] !== ''): ?><p class="footer-about"><?= $r->nl($f['about']) ?></p><?php endif; ?>
      <?php if ($socials): ?>
        <div class="socials">
          <?php foreach ($socials as $so): ?><a href="<?= e($so['url']) ?>" target="_blank" rel="noopener" aria-label="<?= e(App\Services\Icons::label($so['icon'])) ?>"><?= $r->icon($so['icon']) ?></a><?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <?php if ($f['links']): ?>
      <nav class="footer-links" aria-label="頁尾連結">
        <?php foreach ($f['links'] as $l): if ($l['label'] === '') continue; ?><a <?= $r->href($l['url']) ?>><?= e($l['label']) ?></a><?php endforeach; ?>
      </nav>
    <?php endif; ?>
  </div>
  <div class="wrap w-wide footer-bottom">
    <small><?= e(str_replace('{year}', date('Y'), $f['copyright'])) ?></small>
  </div>
</footer>
