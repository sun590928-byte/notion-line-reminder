<?php
/**
 * @var array $bot @var array $login @var array $secretMask @var bool $configured @var string $webhookUrl @var string $callbackUrl
 * @var ?string $webhookLast @var array $receivers @var ?array $bind @var int $friends @var int $memberRecipients @var array $messages @var string $addFriendUrl
 */
use App\Core\View;
?>
<div data-page="line">
<section class="status-row">
  <div class="status-chip <?= $login['enabled'] ? 'ok' : '' ?>"><?= icon('log-in') ?><span>LINE Login<b><?= $login['enabled'] ? '已啟用' : '未啟用' ?></b></span></div>
  <div class="status-chip <?= $configured ? 'ok' : '' ?>"><?= icon('bot') ?><span>Messaging API<b><?= $configured ? '已設定' : '未設定' ?></b></span></div>
  <div class="status-chip <?= $webhookLast ? 'ok' : '' ?>"><?= icon('webhook') ?><span>Webhook<b><?= $webhookLast ? '最後收到 ' . e(time_ago($webhookLast)) : '尚未收到事件' ?></b></span></div>
  <div class="status-chip"><?= icon('users') ?><span>官方帳號好友<b><?= (int) $friends ?> 人（會員 <?= (int) $memberRecipients ?> 人可通知）</b></span></div>
</section>

<div class="note-box">
  <?= icon('info') ?>
  <div>
    <strong>建議的 LINE 設定方式</strong>
    <p>在 <a href="https://developers.line.biz/console/" target="_blank" rel="noopener">LINE Developers</a> 的<b>同一個 Provider</b>底下，分別建立「LINE Login」與「Messaging API」兩個 Channel，並把官方帳號連結到 LINE Login Channel。這樣會員用 LINE 登入時就能順便加入好友，網站也能用同一個 userId 傳通知給他。詳細步驟請看專案內的 <code>docs/line-setup.md</code>。</p>
  </div>
</div>

<div class="grid-2 align-start">
  <section class="card" id="login">
    <div class="card-head"><span class="step">1</span><div><h2>LINE Login（會員登入）</h2><p class="muted">讓訪客用 LINE 一鍵登入成為會員</p></div></div>
    <?= View::capture('admin/_copy', ['label' => 'Callback URL', 'value' => $callbackUrl]) ?>
    <form class="form" data-api="<?= e(url('/admin/api/line/settings')) ?>" data-reload>
      <input type="hidden" name="section" value="login">
      <label class="switch-row"><span>啟用 LINE 登入</span><input type="checkbox" class="switch" name="enabled" value="1" <?= $login['enabled'] ? 'checked' : '' ?>></label>
      <label class="field">Channel ID<input name="channelId" value="<?= e($login['channelId']) ?>" inputmode="numeric" placeholder="例如 2001234567"></label>
      <label class="field">Channel secret<input name="channelSecret" type="password" autocomplete="off" placeholder="<?= $secretMask['login'] !== '' ? e($secretMask['login']) . '（留空表示不變更）' : '貼上 Channel secret' ?>"></label>
      <label class="field">登入後引導加入官方帳號好友
        <select name="botPrompt">
          <option value="aggressive"<?= $login['botPrompt'] === 'aggressive' ? ' selected' : '' ?>>登入後另開畫面詢問（建議）</option>
          <option value="normal"<?= $login['botPrompt'] === 'normal' ? ' selected' : '' ?>>在同意畫面中顯示加好友選項</option>
          <option value="none"<?= $login['botPrompt'] === 'none' ? ' selected' : '' ?>>不引導</option>
        </select>
        <small>需先在 LINE Login Channel 的「Linked LINE Official Account」選擇你的官方帳號。</small>
      </label>
      <label class="switch-row"><span>取得會員 Email<small>需先在 LINE Developers 申請 Email 權限並通過審核</small></span><input type="checkbox" class="switch" name="requestEmail" value="1" <?= $login['requestEmail'] ? 'checked' : '' ?>></label>
      <button class="btn btn-primary" type="submit">儲存 LINE Login 設定</button>
    </form>
  </section>

  <section class="card" id="bot">
    <div class="card-head"><span class="step">2</span><div><h2>Messaging API（官方帳號）</h2><p class="muted">同步好友、傳送通知與訊息</p></div></div>
    <?= View::capture('admin/_copy', ['label' => 'Webhook URL', 'value' => $webhookUrl]) ?>
    <p class="muted small">貼到 Messaging API Channel 的 Webhook URL，開啟「Use webhook」後按「Verify」。</p>
    <form class="form" data-api="<?= e(url('/admin/api/line/settings')) ?>" data-reload>
      <input type="hidden" name="section" value="bot">
      <label class="field">Channel secret<input name="channelSecret" type="password" autocomplete="off" placeholder="<?= $secretMask['bot'] !== '' ? e($secretMask['bot']) . '（留空表示不變更）' : '貼上 Channel secret' ?>"></label>
      <label class="field">Channel access token（長期）<input name="accessToken" type="password" autocomplete="off" placeholder="<?= $secretMask['token'] !== '' ? e($secretMask['token']) . '（留空表示不變更）' : '在 Messaging API 分頁按 Issue 產生' ?>"></label>
      <label class="field">官方帳號 ID<input name="basicId" value="<?= e($bot['basicId']) ?>" placeholder="@123abcde"><small>用來產生「加入好友」連結<?= $addFriendUrl !== '' ? '：' . e($addFriendUrl) : '' ?></small></label>
      <div class="btn-row">
        <button class="btn btn-primary" type="submit">儲存</button>
        <button class="btn" type="button" data-line-test><?= icon('plug') ?>測試連線</button>
      </div>
    </form>
    <div class="bot-info" data-bot-info hidden></div>
  </section>
</div>

<div class="grid-2 align-start">
  <section class="card" id="notify">
    <div class="card-head"><span class="step">3</span><div><h2>管理員 LINE 通知</h2><p class="muted">新會員加入、收到留言時，通知到你的 LINE</p></div></div>
    <?php if ($receivers): ?>
      <ul class="list">
        <?php foreach ($receivers as $rc): ?>
          <li><div class="list-row">
            <?php if ($rc['picture_url']): ?><img class="avatar" src="<?= e($rc['picture_url']) ?>" alt="" referrerpolicy="no-referrer"><?php else: ?><span class="avatar ph"><?= icon('user') ?></span><?php endif; ?>
            <span class="list-main"><strong><?= e($rc['display_name'] ?: 'LINE 使用者') ?></strong><small><?= (int) $rc['is_friend'] ? '已加好友' : '<b class="danger">已封鎖官方帳號，無法通知</b>' ?></small></span>
            <button class="btn btn-sm btn-danger-ghost" type="button" data-remove-receiver="<?= e($rc['user_id']) ?>">移除</button>
          </div></li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="muted">還沒有綁定任何管理員。</p>
    <?php endif; ?>
    <div class="bind-box" data-bind-box>
      <?php if ($bind): ?>
        <p>用手機 LINE 傳送以下文字給官方帳號（<?= e(date('H:i', (int) $bind['expires'])) ?> 前有效）：</p><div class="bind-code">綁定 <?= e($bind['code']) ?></div>
      <?php endif; ?>
    </div>
    <div class="btn-row">
      <button class="btn" type="button" data-bind-code <?= $configured ? '' : 'disabled' ?>><?= icon('key') ?>產生綁定碼</button>
      <button class="btn" type="button" data-notify-test <?= $receivers ? '' : 'disabled' ?>><?= icon('bell') ?>傳送測試通知</button>
    </div>
    <form class="form compact-form" data-api="<?= e(url('/admin/api/line/settings')) ?>">
      <input type="hidden" name="section" value="notify">
      <label class="switch-row"><span>新會員加入</span><input type="checkbox" class="switch" name="notifyNewMember" value="1" <?= $bot['notifyNewMember'] ? 'checked' : '' ?>></label>
      <label class="switch-row"><span>聯絡表單新留言</span><input type="checkbox" class="switch" name="notifyContact" value="1" <?= $bot['notifyContact'] ? 'checked' : '' ?>></label>
      <label class="switch-row"><span>官方帳號新增好友</span><input type="checkbox" class="switch" name="notifyNewFriend" value="1" <?= $bot['notifyNewFriend'] ? 'checked' : '' ?>></label>
      <button class="btn btn-primary" type="submit">儲存通知設定</button>
    </form>
  </section>

  <section class="card" id="send">
    <div class="card-head"><span class="step">4</span><div><h2>傳送訊息</h2><p class="muted">推播／群發會計入官方帳號方案的每月訊息則數</p></div></div>
    <?php if (!$configured): ?>
      <div class="empty small"><?= icon('plug') ?><p>完成步驟 2 後即可傳送訊息。</p></div>
    <?php else: ?>
      <form class="form" data-api="<?= e(url('/admin/api/line/send')) ?>" data-confirm="確定要送出這則 LINE 訊息嗎？送出後無法收回。" data-reset data-reload>
        <div class="seg-radio">
          <label><input type="radio" name="target" value="members" checked><span>同意通知的會員（<?= (int) $memberRecipients ?> 人）</span></label>
          <label><input type="radio" name="target" value="all"><span>官方帳號所有好友</span></label>
        </div>
        <label class="field">訊息內容<textarea name="text" rows="5" maxlength="5000" data-count placeholder="例如：🌙 本週末限定活動開跑！詳情 https://…"></textarea></label>
        <div class="field">圖片（選填）<div data-media-input data-name="image"></div></div>
        <button class="btn btn-line" type="submit"><?= icon('send') ?>送出訊息</button>
      </form>
    <?php endif; ?>
  </section>
</div>

<section class="card" id="log">
  <div class="card-head"><h2>訊息紀錄</h2><p class="muted">最近 40 筆：好友傳來的訊息與網站送出的通知</p></div>
  <?php if ($messages): ?>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>時間</th><th>方向</th><th>對象</th><th>內容</th><th>狀態</th></tr></thead>
        <tbody>
          <?php foreach ($messages as $m): ?>
            <tr>
              <td class="nowrap"><?= e(fmt_date($m['created_at'], 'm/d H:i')) ?></td>
              <td><?= $m['direction'] === 'in' ? '<span class="badge muted">收到</span>' : '<span class="badge line">送出</span>' ?></td>
              <td><?= e($m['display_name'] ?? ($m['target'] === 'broadcast' ? '所有好友' : ($m['target'] === 'multicast' ? '多位會員' : '—'))) ?></td>
              <td class="wrap"><?= e(str_limit((string) $m['text'], 120)) ?></td>
              <td><?= $m['status'] === 'failed' ? '<span class="badge danger" title="' . e((string) $m['error']) . '">失敗</span>' : ($m['status'] === 'sent' ? '<span class="badge ok">成功</span>' : '<span class="badge muted">' . e($m['msg_type']) . '</span>') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="empty small"><?= icon('message-square') ?><p>還沒有訊息紀錄。</p></div>
  <?php endif; ?>
</section>
</div>
