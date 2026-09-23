<?php /** @var string $next @var array $flashes */ ?>
<div class="card">
  <div class="brand">
    <img src="<?= e(url('/assets/img/favicon.svg')) ?>" alt="" width="44" height="44">
    <div>
      <h1><?= e(App\Core\Settings::site()['name']) ?> 後台</h1>
      <p class="muted">請登入管理員帳號</p>
    </div>
  </div>
  <?php foreach ($flashes as $f): ?>
    <div class="alert <?= $f['type'] === 'ok' ? 'alert-ok' : 'alert-error' ?>"><?= e($f['message']) ?></div>
  <?php endforeach; ?>
  <form method="post" action="<?= e(url('/admin/login')) ?>" class="form">
    <?= csrf_field() ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <label>帳號<input name="username" required autocomplete="username" autofocus></label>
    <label>密碼<input type="password" name="password" required autocomplete="current-password"></label>
    <label class="check"><input type="checkbox" name="remember" value="1"> 在這台裝置保持登入 14 天</label>
    <button class="btn btn-block" type="submit">登入</button>
  </form>
  <p class="hint" style="text-align:center;margin:18px 0 0"><a href="<?= e(url('/')) ?>" style="color:inherit">← 回到網站</a></p>
</div>
