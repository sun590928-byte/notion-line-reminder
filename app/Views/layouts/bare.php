<!doctype html>
<html lang="zh-Hant-TW">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex">
<title><?= e($title ?? 'aftermoonF') ?></title>
<link rel="icon" href="<?= e(url('/assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('css/bare.css')) ?>">
</head>
<body class="bare <?= e($bodyClass ?? '') ?>">
<div class="bare-sky" aria-hidden="true"></div>
<main class="bare-main">
<?= $content ?>
</main>
</body>
</html>
