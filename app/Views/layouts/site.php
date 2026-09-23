<?php
/** @var App\Services\SiteRenderer $r @var array $page @var array $site @var ?array $member @var string $content */
$t = $r->doc['theme'];
$preview = $r->ctx['preview'];
$editor = $r->ctx['editor'];
$isHome = $page['id'] === ($r->doc['homeId'] ?? '');
$metaTitle = ($page['seoTitle'] ?? '') !== '' ? $page['seoTitle']
    : ($isHome ? $site['name'] . (($site['tagline'] ?? '') !== '' ? '｜' . $site['tagline'] : '') : $page['title'] . '｜' . $site['name']);
$metaDesc = ($page['seoDescription'] ?? '') !== '' ? $page['seoDescription'] : ($site['seoDescription'] ?? '');
$ogImage = absolute_url(media_url(($page['seoImage'] ?? '') ?: (($site['ogImage'] ?? '') ?: '/assets/img/og-default.jpg')));
$canonical = $page['id'] !== '_' ? absolute_url($isHome ? '/' : '/' . ($page['slug'] ?? '')) : '';
$fonts = $r->fontsUrl();
$favicon = ($site['favicon'] ?? '') !== '' ? media_url($site['favicon']) : url('/assets/img/favicon.svg');
$classes = trim('site anim-' . $t['animation'] . ' ' . ($bodyClass ?? '') . ($editor ? ' is-editor' : '') . ($r->ctx['instant'] || $t['animation'] === 'none' ? ' no-anim' : '') . ($t['cursorGlow'] ? ' has-glow' : '') . ($r->heroMode($page) !== '' ? ' has-over-hero' : ''));
?>
<!doctype html>
<html lang="zh-Hant-TW" class="no-js">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($metaTitle) ?></title>
<?php if ($metaDesc !== ''): ?><meta name="description" content="<?= e($metaDesc) ?>"><?php endif; ?>
<?php if ($preview || $page['id'] === '_'): ?><meta name="robots" content="noindex, nofollow"><?php elseif ($canonical !== ''): ?><link rel="canonical" href="<?= e($canonical) ?>"><?php endif; ?>
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e($site['name']) ?>">
<meta property="og:title" content="<?= e($metaTitle) ?>">
<?php if ($metaDesc !== ''): ?><meta property="og:description" content="<?= e($metaDesc) ?>"><?php endif; ?>
<meta property="og:image" content="<?= e($ogImage) ?>">
<?php if ($canonical !== ''): ?><meta property="og:url" content="<?= e($canonical) ?>"><?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="<?= e($t['bg']) ?>">
<link rel="icon" href="<?= e($favicon) ?>">
<link rel="apple-touch-icon" href="<?= e(url('/assets/img/apple-touch-icon.png')) ?>">
<?php if ($fonts !== ''): ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="<?= e($fonts) ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
<?php if (str_contains($bodyClass ?? '', 'member-page')): ?><link rel="stylesheet" href="<?= e(asset('css/member.css')) ?>"><?php endif; ?>
<style><?= $r->themeCss() ?></style>
<script>document.documentElement.className=document.documentElement.className.replace('no-js','js');</script>
<?php if (!$preview): ?>
<?php if (($site['gaId'] ?? '') !== '' && preg_match('/^G-[A-Z0-9]+$/', $site['gaId'])): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($site['gaId']) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= e($site['gaId']) ?>');</script>
<?php endif; ?>
<?= $site['headCode'] ?? '' ?>
<?php endif; ?>
</head>
<body class="<?= e($classes) ?>" data-base="<?= e(App\Core\Request::base()) ?>">
<a class="skip-link" href="#main">跳到主要內容</a>
<?php if ($t['scrollProgress']): ?><div class="progress" aria-hidden="true"><span></span></div><?php endif; ?>
<?php if ($preview && !$editor): ?><div class="preview-ribbon">預覽模式（草稿內容，訪客看不到）<a href="<?= e(url('/admin/site')) ?>">回到編輯器</a></div><?php endif; ?>
<?= App\Core\View::capture('site/header', ['r' => $r, 'page' => $page, 'site' => $site, 'member' => $member]) ?>
<main id="main"><?= $content ?></main>
<?= App\Core\View::capture('site/footer', ['r' => $r, 'site' => $site]) ?>
<?php if ($t['backToTop']): ?><button class="to-top" type="button" aria-label="回到頂端"><?= icon('arrow-up') ?></button><?php endif; ?>
<script src="<?= e(asset('js/site.js')) ?>" defer></script>
<?php if (!$preview): ?><?= $site['bodyCode'] ?? '' ?><?php endif; ?>
</body>
</html>
