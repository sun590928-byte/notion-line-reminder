<?php /** @var array $initial @var string $uploadLimit @var int $count */ ?>
<section class="card" data-page="media">
  <div class="card-head row">
    <div><h2>媒體庫</h2><p class="muted">共 <span data-media-count><?= (int) $count ?></span> 張圖片・大於 2000px 的照片會自動縮小，並移除手機照片中的定位資訊</p></div>
  </div>
  <label class="dropzone" data-dropzone>
    <input type="file" accept="image/jpeg,image/png,image/gif,image/webp,image/x-icon" multiple hidden>
    <?= icon('cloud-upload') ?>
    <strong>把圖片拖曳到這裡，或點擊選擇檔案</strong>
    <small>支援 JPG、PNG、GIF、WebP，單檔 10 MB 以內（主機上限 <?= e($uploadLimit) ?>）</small>
  </label>
  <div class="upload-queue" data-upload-queue></div>
  <div class="media-grid" data-media-grid data-initial="<?= e(json_encode($initial, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"></div>
  <div class="center"><button class="btn" type="button" data-media-more hidden>載入更多</button></div>
</section>
