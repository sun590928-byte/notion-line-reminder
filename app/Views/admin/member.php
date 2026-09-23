<?php /** @var array $member @var array $identities @var ?array $contact @var array $messages @var array $forms @var bool $lineReady */ $id = (int) $member['id']; ?>
<div class="page-back"><a href="<?= e(url('/admin/members')) ?>"><?= icon('chevron-left') ?>會員列表</a></div>
<div class="grid-side" data-page="member" data-member="<?= $id ?>">
  <div class="stack">
    <section class="card profile-card">
      <?php if ($member['avatar_url']): ?><img class="avatar xl" src="<?= e($member['avatar_url']) ?>" alt="" referrerpolicy="no-referrer"><?php else: ?><span class="avatar xl ph"><?= e(mb_substr($member['display_name'], 0, 1)) ?></span><?php endif; ?>
      <h2><?= e($member['display_name']) ?></h2>
      <p class="muted"><?= e($member['email'] ?: '沒有 Email') ?></p>
      <dl class="meta">
        <dt>會員編號</dt><dd>#<?= $id ?></dd>
        <dt>加入時間</dt><dd><?= e(fmt_date($member['created_at'])) ?></dd>
        <dt>最後登入</dt><dd><?= e(fmt_date($member['last_login_at'])) ?></dd>
        <dt>登入次數</dt><dd><?= (int) $member['login_count'] ?></dd>
      </dl>
    </section>
    <section class="card">
      <h3>登入方式</h3>
      <ul class="plain">
        <?php foreach (['line' => 'LINE', 'google' => 'Google'] as $k => $label): $idn = $identities[$k] ?? null; ?>
          <li class="id-row"><span class="badge <?= $k === 'line' ? 'line' : 'muted' ?>"><?= $label ?></span><?= $idn ? e($idn['display_name'] ?: $idn['email'] ?: '已綁定') . '<small class="muted"> ・' . e(fmt_date($idn['created_at'], 'Y/m/d')) . ' 綁定</small>' : '<span class="muted">未綁定</span>' ?></li>
        <?php endforeach; ?>
      </ul>
      <?php if (isset($identities['line'])): ?>
        <p class="muted small">LINE userId：<code><?= e($identities['line']['provider_uid']) ?></code></p>
        <p><?= $contact && (int) $contact['is_friend'] === 1 ? '<span class="badge ok">已加入官方帳號好友</span>' : '<span class="badge muted">尚未加入官方帳號好友</span>' ?></p>
      <?php endif; ?>
    </section>
    <section class="card">
      <h3>管理</h3>
      <form class="form" data-api="<?= e(url('/admin/api/members/' . $id)) ?>">
        <label class="switch-row"><span>允許登入</span><input type="checkbox" class="switch" name="status" value="active" data-on="active" data-off="blocked" <?= $member['status'] === 'active' ? 'checked' : '' ?>></label>
        <label class="switch-row"><span>接收 LINE 通知</span><input type="checkbox" class="switch" name="notify_line" value="1" <?= (int) $member['notify_line'] === 1 ? 'checked' : '' ?>></label>
        <label class="field">管理員備註<textarea name="note" rows="3" placeholder="只有管理員看得到"><?= e($member['note'] ?? '') ?></textarea></label>
        <button class="btn btn-primary" type="submit">儲存</button>
      </form>
      <hr>
      <button class="btn btn-danger-ghost" type="button" data-delete-member="<?= $id ?>"><?= icon('trash-2') ?>刪除會員</button>
    </section>
  </div>
  <div class="stack">
    <section class="card">
      <div class="card-head"><h2>傳送 LINE 訊息</h2><p class="muted">一對一推播會計入官方帳號每月免費訊息則數</p></div>
      <?php if (!isset($identities['line'])): ?>
        <div class="empty small"><?= icon('line') ?><p>這位會員沒有綁定 LINE。</p></div>
      <?php elseif (!$lineReady): ?>
        <div class="empty small"><?= icon('plug') ?><p>尚未設定 Messaging API，請到 <a href="<?= e(url('/admin/line#bot')) ?>">LINE 整合</a> 完成設定。</p></div>
      <?php else: ?>
        <form class="form" data-api="<?= e(url('/admin/api/members/' . $id . '/message')) ?>" data-reset data-reload>
          <label class="field"><textarea name="text" rows="4" maxlength="5000" required placeholder="輸入要傳給 <?= e($member['display_name']) ?> 的訊息"></textarea></label>
          <button class="btn btn-line" type="submit"><?= icon('send') ?>傳送</button>
        </form>
      <?php endif; ?>
      <?php if ($messages): ?>
        <ul class="chat">
          <?php foreach ($messages as $msg): ?>
            <li class="<?= $msg['direction'] === 'out' ? 'out' : 'in' ?>"><span class="bubble"><?= nl2br(e((string) $msg['text'])) ?></span><small><?= e(fmt_date($msg['created_at'], 'm/d H:i')) ?><?= $msg['status'] === 'failed' ? '・<b class="danger">失敗</b>' : '' ?></small></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
    <section class="card">
      <h3>表單留言</h3>
      <?php if ($forms): ?>
        <ul class="list">
          <?php foreach ($forms as $f): ?><li><a href="<?= e(url('/admin/forms#f' . (int) $f['id'])) ?>"><span class="list-main"><strong><?= e(str_limit((string) $f['message'], 80)) ?></strong><small><?= e($f['page']) ?></small></span><span class="list-meta"><?= e(time_ago($f['created_at'])) ?></span></a></li><?php endforeach; ?>
        </ul>
      <?php else: ?><p class="muted">沒有留言紀錄。</p><?php endif; ?>
    </section>
  </div>
</div>
