<?php /** 可複製的網址欄位 @var string $value @var string $label */ ?>
<div class="copy-field"><span class="copy-label"><?= e($label) ?></span><code><?= e($value) ?></code><button class="btn btn-sm" type="button" data-copy="<?= e($value) ?>"><?= icon('copy') ?>複製</button></div>
