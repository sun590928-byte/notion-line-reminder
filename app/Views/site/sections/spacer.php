<?php /** @var App\Services\SiteRenderer $r @var array $d */ ?>
<div class="spacer sp-<?= e($d['size']) ?>">
  <?php if ($d['divider'] === 'line'): ?><hr class="divider rv">
  <?php elseif ($d['divider'] === 'dots'): ?><div class="divider-dots rv" aria-hidden="true"><i></i><i></i><i></i></div>
  <?php elseif ($d['divider'] === 'stars'): ?><div class="divider-stars rv" aria-hidden="true"><span>✦</span><span class="moon"></span><span>✦</span></div>
  <?php elseif ($d['divider'] === 'wave'): ?><svg class="divider-wave rv" viewBox="0 0 1200 40" preserveAspectRatio="none" aria-hidden="true"><path d="M0 20 Q 75 0 150 20 T 300 20 T 450 20 T 600 20 T 750 20 T 900 20 T 1050 20 T 1200 20" fill="none" stroke="currentColor" stroke-width="2"/></svg>
  <?php endif; ?>
</div>
