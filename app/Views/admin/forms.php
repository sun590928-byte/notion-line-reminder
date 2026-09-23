<?php /** @var array $rows @var string $filter @var int $page @var int $pages @var int $total @var int $unread */ ?>
<section class="card" data-page="forms">
  <div class="tabs-inline">
    <a href="<?= e(url('/admin/forms')) ?>"<?= $filter === 'all' ? ' class="active"' : '' ?>>全部</a>
    <a href="<?= e(url('/admin/forms?filter=unread')) ?>"<?= $filter === 'unread' ? ' class="active"' : '' ?>>未讀 <?= $unread > 0 ? '<em class="nav-count">' . $unread . '</em>' : '' ?></a>
  </div>
  <?php if ($rows): ?>
    <div class="msgs">
      <?php foreach ($rows as $f): $fid = (int) $f['id']; ?>
        <article class="msg<?= (int) $f['is_read'] ? '' : ' unread' ?>" id="f<?= $fid ?>" data-form-id="<?= $fid ?>">
          <header>
            <span class="avatar ph"><?= e(mb_substr((string) $f['name'], 0, 1)) ?></span>
            <div class="msg-who">
              <strong><?= e($f['name']) ?></strong>
              <small><a href="mailto:<?= e($f['email']) ?>"><?= e($f['email']) ?></a><?= $f['phone'] !== '' ? '・<a href="tel:' . e($f['phone']) . '">' . e($f['phone']) . '</a>' : '' ?></small>
            </div>
            <time datetime="<?= e((string) $f['created_at']) ?>" title="<?= e(fmt_date($f['created_at'])) ?>"><?= e(time_ago($f['created_at'])) ?></time>
          </header>
          <p class="msg-body"><?= nl2br(e((string) $f['message'])) ?></p>
          <footer>
            <span class="muted small">來自頁面 <?= e($f['page'] ?: '—') ?><?= (int) $f['member_id'] ? '・<a href="' . e(url('/admin/members/' . (int) $f['member_id'])) . '">會員 #' . (int) $f['member_id'] . '</a>' : '' ?></span>
            <span class="msg-actions">
              <a class="btn btn-sm" href="mailto:<?= e($f['email']) ?>?subject=<?= rawurlencode('回覆：你在 ' . App\Core\Settings::site()['name'] . ' 的留言') ?>"><?= icon('mail') ?>回信</a>
              <button class="btn btn-sm" type="button" data-toggle-read="<?= $fid ?>" data-read="<?= (int) $f['is_read'] ?>"><?= (int) $f['is_read'] ? '標為未讀' : '標為已讀' ?></button>
              <button class="btn btn-sm btn-danger-ghost" type="button" data-delete-form="<?= $fid ?>"><?= icon('trash-2') ?></button>
            </span>
          </footer>
        </article>
      <?php endforeach; ?>
    </div>
    <?php if ($pages > 1): ?>
      <nav class="pager"><?php for ($i = 1; $i <= $pages; $i++): ?><a href="<?= e(url('/admin/forms?' . http_build_query(['filter' => $filter, 'page' => $i]))) ?>"<?= $i === $page ? ' class="active"' : '' ?>><?= $i ?></a><?php endfor; ?></nav>
    <?php endif; ?>
  <?php else: ?>
    <div class="empty"><?= icon('inbox') ?><p><?= $filter === 'unread' ? '沒有未讀的留言。' : '還沒有任何留言。官網的「聯絡表單」區塊收到的訊息會出現在這裡。' ?></p></div>
  <?php endif; ?>
</section>
