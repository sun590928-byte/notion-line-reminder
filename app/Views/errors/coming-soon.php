<?php /** @var array $site @var array $socials */ ?>
<div class="soon">
  <div class="moon" aria-hidden="true"></div>
  <h1><?= e($site['name'] ?? 'aftermoonF') ?></h1>
  <p><?= e(($site['tagline'] ?? '') !== '' ? $site['tagline'] : '網站即將與你見面，敬請期待。') ?></p>
  <?php if (!empty($socials)): ?>
    <div class="socials">
      <?php foreach ($socials as $s): ?>
        <a href="<?= e($s['url']) ?>" target="_blank" rel="noopener" aria-label="<?= e(App\Services\Icons::label($s['icon'])) ?>"><?= icon($s['icon']) ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
