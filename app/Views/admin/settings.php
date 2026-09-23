<?php
/**
 * @var array $site @var string $mode @var bool $sitePublished @var array $auth @var string $googleMask @var string $googleCallback
 * @var string $baseUrl @var string $detectedUrl @var array $server
 */
use App\Core\View;
?>
<div data-page="settings">
<nav class="tabs" data-tabs>
  <a href="#general" class="active">一般</a>
  <a href="#members">會員登入</a>
  <a href="#seo">SEO 與追蹤</a>
  <a href="#code">自訂程式碼</a>
  <a href="#system">系統資訊</a>
</nav>

<section class="tab-panel" id="general">
  <div class="grid-2 align-start">
    <section class="card">
      <div class="card-head"><h2>網站資訊</h2></div>
      <form class="form" data-api="<?= e(url('/admin/api/settings')) ?>">
        <input type="hidden" name="section" value="general">
        <label class="field">網站名稱<input name="name" value="<?= e($site['name']) ?>" maxlength="80" required></label>
        <label class="field">一句話標語<input name="tagline" value="<?= e($site['tagline']) ?>" maxlength="120" placeholder="顯示在瀏覽器標題與即將推出頁"></label>
        <label class="field">正式網址<input name="url" value="<?= e($site['url']) ?>" placeholder="<?= e($detectedUrl) ?>"><small>LINE／Google 登入回呼網址以此為準。更換網域或啟用 https 後請記得修改。目前偵測：<?= e($detectedUrl) ?></small></label>
        <div class="field">Logo<div data-media-input data-name="logo" data-value="<?= e($site['logo']) ?>"></div></div>
        <div class="field">網站小圖示（Favicon，建議 512×512 PNG）<div data-media-input data-name="favicon" data-value="<?= e($site['favicon']) ?>"></div></div>
        <label class="field">聯絡 Email<input type="email" name="contactEmail" value="<?= e($site['contactEmail']) ?>"></label>
        <button class="btn btn-primary" type="submit">儲存</button>
      </form>
    </section>
    <section class="card mode-switch" data-mode-switch>
      <div class="card-head"><h2>首頁模式</h2><p class="muted">官網準備好之前，首頁會顯示連結頁</p></div>
      <div class="mode-options vertical">
        <label class="mode-opt"><input type="radio" name="mode" value="links" <?= $mode === 'links' ? 'checked' : '' ?>><span class="mode-card"><?= icon('link') ?><strong>連結頁</strong><small>官網隱藏，只有管理員能預覽</small></span></label>
        <label class="mode-opt"><input type="radio" name="mode" value="website" <?= $mode === 'website' ? 'checked' : '' ?>><span class="mode-card"><?= icon('globe') ?><strong>官方網站</strong><small><?= $sitePublished ? '公開已發布的官網' : '需先在官網編輯器按「發布」' ?></small></span></label>
        <label class="mode-opt"><input type="radio" name="mode" value="maintenance" <?= $mode === 'maintenance' ? 'checked' : '' ?>><span class="mode-card"><?= icon('moon') ?><strong>即將推出</strong><small>暫時隱藏所有公開頁面</small></span></label>
      </div>
    </section>
  </div>
</section>

<section class="tab-panel" id="members" hidden>
  <div class="grid-2 align-start">
    <section class="card">
      <div class="card-head"><h2>Google 登入</h2><p class="muted">在 Google Cloud Console 建立「OAuth 用戶端 ID（網頁應用程式）」</p></div>
      <?= View::capture('admin/_copy', ['label' => '已授權的重新導向 URI', 'value' => $googleCallback]) ?>
      <form class="form" data-api="<?= e(url('/admin/api/settings')) ?>" data-reload>
        <input type="hidden" name="section" value="members">
        <label class="switch-row"><span>開放新會員註冊<small>關閉後，只有已經是會員的人可以登入</small></span><input type="checkbox" class="switch" name="allowRegistration" value="1" <?= $auth['allowRegistration'] ? 'checked' : '' ?>></label>
        <label class="switch-row"><span>啟用 Google 登入</span><input type="checkbox" class="switch" name="googleEnabled" value="1" <?= $auth['google']['enabled'] ? 'checked' : '' ?>></label>
        <label class="field">用戶端 ID<input name="googleClientId" value="<?= e($auth['google']['clientId']) ?>" placeholder="xxxx.apps.googleusercontent.com"></label>
        <label class="field">用戶端密鑰<input name="googleClientSecret" type="password" autocomplete="off" placeholder="<?= $googleMask !== '' ? e($googleMask) . '（留空表示不變更）' : 'GOCSPX-…' ?>"></label>
        <button class="btn btn-primary" type="submit">儲存</button>
      </form>
    </section>
    <section class="card">
      <div class="card-head"><h2>LINE 登入</h2></div>
      <p><?= $auth['line']['enabled'] ? '<span class="badge ok">已啟用</span>' : '<span class="badge muted">未啟用</span>' ?></p>
      <p class="muted">LINE Login 與官方帳號通知集中在「LINE 整合」頁面設定。</p>
      <a class="btn" href="<?= e(url('/admin/line#login')) ?>"><?= icon('line') ?>前往 LINE 整合</a>
    </section>
  </div>
</section>

<section class="tab-panel" id="seo" hidden>
  <section class="card narrow">
    <div class="card-head"><h2>SEO 與追蹤</h2><p class="muted">頁面沒有另外設定時，會使用這裡的預設值</p></div>
    <form class="form" data-api="<?= e(url('/admin/api/settings')) ?>">
      <input type="hidden" name="section" value="seo">
      <label class="field">預設描述<textarea name="seoDescription" rows="3" maxlength="300"><?= e($site['seoDescription']) ?></textarea><small>顯示在 Google 搜尋結果與社群分享，建議 80–120 字。</small></label>
      <div class="field">預設分享圖片（1200×630）<div data-media-input data-name="ogImage" data-value="<?= e($site['ogImage']) ?>"></div></div>
      <label class="field">Google Analytics 4 評估 ID<input name="gaId" value="<?= e($site['gaId']) ?>" placeholder="G-XXXXXXXXXX"></label>
      <button class="btn btn-primary" type="submit">儲存</button>
    </form>
  </section>
</section>

<section class="tab-panel" id="code" hidden>
  <section class="card narrow">
    <div class="card-head"><h2>自訂程式碼</h2><p class="muted">例如 Meta Pixel、Google Search Console 驗證碼。只在公開頁面載入，預覽模式不會執行。</p></div>
    <div class="note-box warn"><?= icon('triangle-alert') ?><p>請只貼上信任來源提供的程式碼，錯誤的程式碼可能讓網站無法正常顯示。</p></div>
    <form class="form" data-api="<?= e(url('/admin/api/settings')) ?>">
      <input type="hidden" name="section" value="code">
      <label class="field">放在 &lt;head&gt; 內<textarea class="mono" name="headCode" rows="7" spellcheck="false"><?= e($site['headCode']) ?></textarea></label>
      <label class="field">放在 &lt;/body&gt; 前<textarea class="mono" name="bodyCode" rows="7" spellcheck="false"><?= e($site['bodyCode']) ?></textarea></label>
      <button class="btn btn-primary" type="submit">儲存</button>
    </form>
  </section>
</section>

<section class="tab-panel" id="system" hidden>
  <section class="card narrow">
    <div class="card-head"><h2>系統資訊</h2></div>
    <dl class="meta wide">
      <?php foreach ($server as $k => $v): ?><dt><?= e($k) ?></dt><dd><?= e((string) $v) ?></dd><?php endforeach; ?>
      <dt>網站網址</dt><dd><?= e($baseUrl) ?></dd>
    </dl>
  </section>
</section>
</div>
