<?php
/** @var array $checks @var array $errors @var array $input @var ?string $manualConfig @var bool $canInstall */
?>
<div class="card card-wide">
  <div class="brand">
    <img src="<?= e(url('/assets/img/favicon.svg')) ?>" alt="" width="44" height="44">
    <div>
      <h1>安裝 aftermoonF</h1>
      <p class="muted">只需要一次。完成後會自動登入後台 <code>/admin</code>。</p>
    </div>
  </div>

<?php if ($manualConfig !== null): ?>
  <div class="alert alert-warn">
    <strong>資料庫已建立完成，但無法自動寫入設定檔。</strong><br>
    請在 Plesk「檔案」中建立 <code>config/config.php</code>，貼上以下內容後重新整理此頁：
  </div>
  <textarea class="code" rows="16" readonly onclick="this.select()"><?= e($manualConfig) ?></textarea>
  <p><a class="btn" href="<?= e(url('/admin')) ?>">已建立，前往後台</a></p>
<?php else: ?>

  <h2>1. 主機環境檢查</h2>
  <ul class="checks">
    <?php foreach ($checks as $c): ?>
      <li class="<?= $c['ok'] ? 'ok' : ($c['required'] ? 'fail' : 'warn') ?>">
        <span class="dot"></span><?= e($c['label']) ?>
        <?php if (!$c['ok']): ?><em><?= $c['required'] ? '必要' : '建議' ?></em><?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>

  <?php if ($errors): ?>
    <div class="alert alert-error"><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
  <?php endif; ?>

  <form method="post" action="<?= e(url('/install')) ?>" class="form" autocomplete="off">
    <h2>2. 資料庫</h2>
    <div class="seg">
      <label><input type="radio" name="db_driver" value="mysql" <?= $input['db_driver'] === 'mysql' ? 'checked' : '' ?> <?= extension_loaded('pdo_mysql') ? '' : 'disabled' ?>> MySQL / MariaDB（Plesk 建議）</label>
      <label><input type="radio" name="db_driver" value="sqlite" <?= $input['db_driver'] === 'sqlite' ? 'checked' : '' ?> <?= extension_loaded('pdo_sqlite') ? '' : 'disabled' ?>> SQLite（免設定）</label>
    </div>
    <div class="mysql-only">
      <p class="hint">在 Plesk →「資料庫」→「新增資料庫」建立資料庫與使用者後，填入下方欄位。</p>
      <div class="row">
        <label>主機<input name="db_host" value="<?= e($input['db_host']) ?>" placeholder="localhost"></label>
        <label class="sm">連接埠<input name="db_port" value="<?= e($input['db_port']) ?>" inputmode="numeric"></label>
      </div>
      <label>資料庫名稱<input name="db_name" value="<?= e($input['db_name']) ?>"></label>
      <div class="row">
        <label>使用者名稱<input name="db_user" value="<?= e($input['db_user']) ?>"></label>
        <label>密碼<input type="password" name="db_pass" autocomplete="new-password"></label>
      </div>
    </div>
    <label>資料表前綴<input name="db_prefix" value="<?= e($input['db_prefix']) ?>"></label>

    <h2>3. 網站</h2>
    <label>網站名稱<input name="site_name" value="<?= e($input['site_name']) ?>" required></label>
    <label>網站網址<input name="site_url" value="<?= e($input['site_url']) ?>" required>
      <small>LINE／Google 登入的回呼網址會以此為準，請使用正式網域與 https。</small></label>

    <h2>4. 管理員帳號</h2>
    <div class="row">
      <label>帳號<input name="admin_user" value="<?= e($input['admin_user']) ?>" required autocomplete="username"></label>
      <label>顯示名稱<input name="admin_name" value="<?= e($input['admin_name']) ?>"></label>
    </div>
    <div class="row">
      <label>密碼（至少 8 字元）<input type="password" name="admin_pass" required minlength="8" autocomplete="new-password"></label>
      <label>再輸入一次<input type="password" name="admin_pass2" required minlength="8" autocomplete="new-password"></label>
    </div>

    <button class="btn btn-block" type="submit" <?= $canInstall ? '' : 'disabled' ?>>開始安裝</button>
    <?php if (!$canInstall): ?><p class="hint">請先處理上方標示「必要」的項目（Plesk →「PHP 設定」可切換版本與啟用擴充）。</p><?php endif; ?>
  </form>
  <script>
    (function () {
      var radios = document.querySelectorAll('input[name=db_driver]');
      var box = document.querySelector('.mysql-only');
      function sync() { var v = document.querySelector('input[name=db_driver]:checked'); box.style.display = v && v.value === 'mysql' ? '' : 'none'; }
      radios.forEach(function (r) { r.addEventListener('change', sync); });
      sync();
    })();
  </script>
<?php endif; ?>
</div>
