<div class="card" style="text-align:center">
  <p class="err-code"><?= e((string) ($code ?? 400)) ?></p>
  <h1><?= e($message ?? '發生錯誤') ?></h1>
  <p style="margin-top:22px"><a class="btn" href="javascript:history.back()">返回上一頁</a></p>
</div>
