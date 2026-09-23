<?php
/**
 * 連結頁（Linktree 風格）
 * @var array $doc 連結頁文件 @var array $site 網站設定 @var bool $preview @var bool $editor
 * @var bool $showWebsite 官網是否公開 @var ?array $member @var array $providers @var string $canonical
 */
use App\Services\Fonts;
use App\Services\Icons;
use App\Services\LinkPage;
use App\Services\Url;

$p = $doc['profile'];
$t = $doc['theme'];
$st = $doc['settings'];
$links = LinkPage::visibleLinks($doc, $editor);
$socials = array_values(array_filter($doc['socials'], static fn ($s) => $s['url'] !== ''));
$title = $st['seoTitle'] !== '' ? $st['seoTitle'] : $p['name'];
$desc = $st['seoDescription'] !== '' ? $st['seoDescription'] : preg_replace('/\s+/', ' ', $p['bio']);
$fonts = Fonts::googleUrl([$t['font']]);
$now = date('Y-m-d\TH:i');
$bodyClass = 'lt anim-' . $t['animation'] . ' bg-' . $t['bgType'] . ' fx-' . $t['bgEffect'] . ' btn-' . $t['buttonStyle'] . ' icons-' . $t['iconStyle']
    . (LinkPage::isDark($t) ? ' is-dark' : ' is-light') . ($editor ? ' is-editor' : '');
$i = 0;
$socialHtml = static function () use ($socials): string {
    if (!$socials) {
        return '';
    }
    $html = '<nav class="lt-socials" aria-label="社群連結">';
    foreach ($socials as $s) {
        $html .= '<a href="' . e($s['url']) . '" target="_blank" rel="noopener" aria-label="' . e(Icons::label($s['icon'])) . '"'
            . (Icons::isBrand($s['icon']) ? ' style="--brand:' . e(Icons::color($s['icon'])) . '"' : '') . '>' . Icons::svg($s['icon']) . '</a>';
    }
    return $html . '</nav>';
};
?>
<!doctype html>
<html lang="zh-Hant-TW" class="no-js">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?></title>
<?php if ($desc !== ''): ?><meta name="description" content="<?= e($desc) ?>"><?php endif; ?>
<?php if ($preview): ?><meta name="robots" content="noindex, nofollow"><?php else: ?><link rel="canonical" href="<?= e($canonical) ?>"><?php endif; ?>
<meta property="og:type" content="profile">
<meta property="og:title" content="<?= e($title) ?>">
<?php if ($desc !== ''): ?><meta property="og:description" content="<?= e($desc) ?>"><?php endif; ?>
<?php $ogSrc = ($site['ogImage'] ?? '') ?: ($p['avatar'] !== '' && !str_ends_with(strtolower($p['avatar']), '.svg') ? $p['avatar'] : '/assets/img/og-default.jpg'); ?>
<meta property="og:image" content="<?= e(absolute_url(media_url($ogSrc))) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta name="twitter:card" content="summary">
<meta name="theme-color" content="<?= e($t['bgColor']) ?>">
<link rel="icon" href="<?= e(($site['favicon'] ?? '') !== '' ? media_url($site['favicon']) : url('/assets/img/favicon.svg')) ?>">
<link rel="apple-touch-icon" href="<?= e(url('/assets/img/apple-touch-icon.png')) ?>">
<?php if ($fonts !== ''): ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="<?= e($fonts) ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?= e(asset('css/links.css')) ?>">
<style><?= LinkPage::cssVars($t) ?></style>
<script>document.documentElement.className=document.documentElement.className.replace('no-js','js');</script>
<?php if (!$preview && ($site['gaId'] ?? '') !== '' && preg_match('/^G-[A-Z0-9]+$/', $site['gaId'])): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($site['gaId']) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= e($site['gaId']) ?>');</script>
<?php endif; ?>
<?php if (!$preview): ?><?= $site['headCode'] ?? '' ?><?php endif; ?>
</head>
<body class="<?= e($bodyClass) ?>" data-base="<?= e(App\Core\Request::base()) ?>">
<div class="lt-bg" aria-hidden="true">
  <?php if ($t['bgType'] === 'image' && $t['bgImage'] !== ''): ?><div class="lt-bg-img" style="background-image:url('<?= e(media_url($t['bgImage'])) ?>')"></div><div class="lt-bg-overlay"></div><?php endif; ?>
  <?php if ($t['bgEffect'] === 'stars'): ?><div class="lt-stars"></div><div class="lt-stars lt-stars-2"></div><div class="lt-shooting"></div><?php endif; ?>
  <?php if ($t['bgEffect'] === 'aurora'): ?><div class="lt-aurora"><span></span><span></span><span></span></div><?php endif; ?>
</div>

<?php if ($st['showShare']): ?>
  <button class="lt-share" type="button" aria-label="分享這個頁面" data-title="<?= e($title) ?>"><?= icon('share-2') ?></button>
<?php endif; ?>
<?php if ($preview && !$editor): ?><div class="lt-preview">預覽模式（草稿）<a href="<?= e(url('/admin/links')) ?>">回到編輯器</a></div><?php endif; ?>

<main class="lt-main">
  <?php if ($p['cover'] !== ''): ?><div class="lt-cover lt-in" style="--i:0"><img src="<?= e(media_url($p['cover'])) ?>" alt=""></div><?php endif; ?>
  <header class="lt-profile<?= $p['cover'] !== '' ? ' has-cover' : '' ?>">
    <?php if ($p['avatar'] !== ''): ?>
      <div class="lt-avatar-wrap lt-in" style="--i:<?= $i++ ?>"><img class="lt-avatar shape-<?= e($p['avatarShape']) ?>" src="<?= e(media_url($p['avatar'])) ?>" alt="<?= e($p['name']) ?>" width="104" height="104"></div>
    <?php endif; ?>
    <h1 class="lt-name lt-in" style="--i:<?= $i++ ?>"<?= $editor ? ' data-edit="profile.name"' : '' ?>><?= e($p['name']) ?><?php if ($p['verified']): ?><span class="lt-verified" title="已認證"><?= icon('badge-check') ?></span><?php endif; ?></h1>
    <?php if ($p['bio'] !== ''): ?><p class="lt-bio lt-in" style="--i:<?= $i++ ?>"<?= $editor ? ' data-edit="profile.bio"' : '' ?>><?= nl2br(e($p['bio']), false) ?></p><?php endif; ?>
    <?php if ($st['socialPosition'] === 'top' && $socials): ?><div class="lt-in" style="--i:<?= $i++ ?>"><?= $socialHtml() ?></div><?php endif; ?>
  </header>

  <ul class="lt-links">
    <?php foreach ($links as $l):
      $hidden = $editor && (!$l['enabled'] || ($l['type'] === 'link' && $l['url'] === '') || ($l['start'] !== '' && $now < $l['start']) || ($l['end'] !== '' && $now > $l['end']));
      $badge = !$l['enabled'] ? '已隱藏' : (($l['type'] === 'link' && $l['url'] === '') ? '缺少網址' : '排程中');
    ?>
      <?php if ($l['type'] === 'header'): ?>
        <li class="lt-header lt-in<?= $hidden ? ' is-off' : '' ?>" style="--i:<?= $i++ ?>"<?= $editor ? ' data-lid="' . e($l['id']) . '"' : '' ?>><span><?= e($l['title']) ?></span><?php if ($hidden): ?><em class="lt-badge"><?= e($badge) ?></em><?php endif; ?></li>
      <?php else:
        $href = str_starts_with($l['url'], '/') ? url($l['url']) : $l['url'];
        $external = Url::isExternal($l['url']);
        $brand = $t['iconStyle'] === 'brand' && Icons::isBrand($l['icon']) ? ' style="--brand:' . e(Icons::color($l['icon'])) . '"' : '';
      ?>
        <li class="lt-in<?= $hidden ? ' is-off' : '' ?>" style="--i:<?= $i++ ?>">
          <a class="lt-link hl-<?= e($l['highlight']) ?>" href="<?= e($href) ?>" data-link="<?= e($l['id']) ?>"<?= $editor ? ' data-lid="' . e($l['id']) . '"' : '' ?><?= $l['newTab'] && $external ? ' target="_blank" rel="noopener"' : '' ?>>
            <span class="lt-link-icon"<?= $brand ?>>
              <?php if ($l['thumb'] !== ''): ?><img src="<?= e(media_url($l['thumb'])) ?>" alt="" loading="lazy"><?php elseif ($l['icon'] !== ''): ?><?= Icons::svg($l['icon']) ?><?php endif; ?>
            </span>
            <span class="lt-link-text"><?= e($l['title']) ?><?php if ($l['desc'] !== ''): ?><small><?= e($l['desc']) ?></small><?php endif; ?></span>
            <span class="lt-link-end" aria-hidden="true"><?= icon('arrow-right') ?></span>
          </a>
          <?php if ($hidden): ?><em class="lt-badge"><?= e($badge) ?></em><?php endif; ?>
        </li>
      <?php endif; ?>
    <?php endforeach; ?>
  </ul>

  <?php if ($showWebsite && $st['showWebsite']): ?>
    <a class="lt-website lt-in" style="--i:<?= $i++ ?>" href="<?= e(url('/')) ?>"><?= icon('globe') ?><span><?= e($st['websiteLabel']) ?></span><?= icon('arrow-right') ?></a>
  <?php endif; ?>

  <?php if ($st['socialPosition'] === 'bottom' && $socials): ?><div class="lt-in" style="--i:<?= $i++ ?>"><?= $socialHtml() ?></div><?php endif; ?>

  <footer class="lt-footer lt-in" style="--i:<?= $i++ ?>">
    <?php if ($st['showLogin'] && $providers): ?>
      <?php if ($member): ?>
        <a class="lt-member" href="<?= e(url('/member')) ?>">
          <?php if (!empty($member['avatar_url'])): ?><img src="<?= e($member['avatar_url']) ?>" alt="" referrerpolicy="no-referrer"><?php else: ?><?= icon('user') ?><?php endif; ?>
          <span><?= e($member['display_name']) ?>・會員中心</span>
        </a>
      <?php else: ?>
        <a class="lt-member" href="<?= e(url('/login?return=' . rawurlencode(App\Core\Request::path()))) ?>"><?= icon('user') ?><span>會員登入 / 註冊</span></a>
      <?php endif; ?>
    <?php endif; ?>
    <?php if ($st['footer'] !== ''): ?><p><?= e(str_replace('{year}', date('Y'), $st['footer'])) ?></p><?php endif; ?>
  </footer>
</main>
<div class="lt-toast" role="status" aria-live="polite"></div>
<script src="<?= e(asset('js/links.js')) ?>" defer></script>
<?php if (!$preview): ?><?= $site['bodyCode'] ?? '' ?><?php endif; ?>
</body>
</html>
