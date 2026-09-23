<?php /** @var string $title @var string $name @var array $config @var string $content */ ?>
<!doctype html>
<html lang="zh-Hant-TW">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?>｜<?= e(App\Core\Settings::site()['name']) ?></title>
<link rel="icon" href="<?= e(url('/assets/img/favicon.svg')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="adm ed-body">
<?= $content ?>
<div class="toasts" id="toasts" aria-live="polite"></div>
<script type="application/json" id="editor-config"><?= json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script type="module" src="<?= e(asset('js/admin/' . ($name === 'site' ? 'site-editor.js' : 'links-editor.js'))) ?>"></script>
</body>
</html>
