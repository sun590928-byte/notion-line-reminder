<?php
/** @var App\Services\SiteRenderer $r @var array $d @var array $s */
$socials = $d['social'] ? $r->socials() : [];
$map = $d['map'] !== '' ? App\Services\Url::mapEmbed($d['map']) : '';
$hasInfo = $d['address'] !== '' || $d['phone'] !== '' || $d['email'] !== '' || $d['hours'] !== '' || $socials;
?>
<div class="<?= $r->wrapClass() ?>">
  <?= $r->head($d) ?>
  <div class="contact<?= $d['showForm'] && $hasInfo ? ' has-both' : '' ?>">
    <?php if ($hasInfo): ?>
      <div class="contact-info rv">
        <?php if ($d['address'] !== ''): ?><div class="ci"><?= icon('map-pin') ?><div><strong>地址</strong><p<?= $r->ed('address') ?>><?= $r->nl($d['address']) ?></p></div></div><?php endif; ?>
        <?php if ($d['phone'] !== ''): ?><div class="ci"><?= icon('phone') ?><div><strong>電話</strong><p><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $d['phone'])) ?>"<?= $r->ed('phone') ?>><?= e($d['phone']) ?></a></p></div></div><?php endif; ?>
        <?php if ($d['email'] !== ''): ?><div class="ci"><?= icon('mail') ?><div><strong>Email</strong><p><a href="mailto:<?= e($d['email']) ?>"<?= $r->ed('email') ?>><?= e($d['email']) ?></a></p></div></div><?php endif; ?>
        <?php if ($d['hours'] !== ''): ?><div class="ci"><?= icon('clock') ?><div><strong>營業時間</strong><p<?= $r->ed('hours') ?>><?= $r->nl($d['hours']) ?></p></div></div><?php endif; ?>
        <?php if ($socials): ?>
          <div class="socials">
            <?php foreach ($socials as $so): ?><a href="<?= e($so['url']) ?>" target="_blank" rel="noopener" aria-label="<?= e(App\Services\Icons::label($so['icon'])) ?>"><?= $r->icon($so['icon']) ?></a><?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <?php if ($d['showForm']): ?>
      <form class="contact-form rv" method="post" action="<?= e(url('/api/contact')) ?>" data-contact novalidate>
        <input type="hidden" name="token" value="<?= e($r->formToken()) ?>">
        <input type="hidden" name="page" value="<?= e(App\Core\Request::path()) ?>">
        <div class="hp" aria-hidden="true"><label>網站<input name="website" tabindex="-1" autocomplete="off"></label></div>
        <div class="f-row">
          <label class="f"><span>姓名 *</span><input name="name" required maxlength="100" autocomplete="name"></label>
          <label class="f"><span>Email *</span><input name="email" type="email" required maxlength="191" autocomplete="email"></label>
        </div>
        <?php if ($d['askPhone']): ?><label class="f"><span>電話</span><input name="phone" type="tel" maxlength="50" autocomplete="tel"></label><?php endif; ?>
        <label class="f"><span>訊息 *</span><textarea name="message" rows="5" required maxlength="5000"></textarea></label>
        <button class="btn btn-primary" type="submit"><span<?= $r->ed('submitLabel') ?>><?= e($d['submitLabel']) ?></span><?= icon('send') ?></button>
        <p class="form-status" role="status" data-success="<?= e($d['successMessage']) ?>"></p>
      </form>
    <?php endif; ?>
  </div>
  <?php if ($map !== ''): ?>
    <div class="map rv"><iframe src="<?= e($map) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Google 地圖" allowfullscreen></iframe></div>
  <?php endif; ?>
</div>
