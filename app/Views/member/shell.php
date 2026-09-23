<?php
/** 連結頁模式下的會員頁外框 @var string $title @var string $content @var array $doc */
$t = $doc['theme'];
$fonts = App\Services\Fonts::googleUrl([$t['font']]);
?>
<!doctype html>
<html lang="zh-Hant-TW" class="no-js">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex">
<title><?= e($title) ?></title>
<link rel="icon" href="<?= e(url('/assets/img/favicon.svg')) ?>">
<?php if ($fonts !== ''): ?><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link rel="stylesheet" href="<?= e($fonts) ?>"><?php endif; ?>
<link rel="stylesheet" href="<?= e(asset('css/links.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/member.css')) ?>">
<style><?= App\Services\LinkPage::cssVars($t) ?></style>
</head>
<body class="lt member-page bg-<?= e($t['bgType']) ?> fx-<?= e($t['bgEffect']) ?> <?= App\Services\LinkPage::isDark($t) ? 'is-dark' : 'is-light' ?>">
<div class="lt-bg" aria-hidden="true">
  <?php if ($t['bgType'] === 'image' && $t['bgImage'] !== ''): ?><div class="lt-bg-img" style="background-image:url('<?= e(media_url($t['bgImage'])) ?>')"></div><div class="lt-bg-overlay"></div><?php endif; ?>
  <?php if ($t['bgEffect'] === 'stars'): ?><div class="lt-stars"></div><div class="lt-stars lt-stars-2"></div><?php endif; ?>
  <?php if ($t['bgEffect'] === 'aurora'): ?><div class="lt-aurora"><span></span><span></span><span></span></div><?php endif; ?>
</div>
<main class="auth-wrap"><?= $content ?></main>
</body>
</html>
