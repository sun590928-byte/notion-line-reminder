<?php /** @var array $flashes */ foreach ($flashes ?? [] as $f): ?>
  <div class="m-alert m-<?= $f['type'] === 'ok' ? 'ok' : 'err' ?>" role="status"><?= e($f['message']) ?></div>
<?php endforeach; ?>
