# LINE 設定：會員登入＋官方帳號通知

aftermoonF 會用到 LINE 的兩種服務：

| 服務 | 用途 | 在後台哪裡設定 |
|---|---|---|
| **LINE Login** | 訪客用 LINE 一鍵登入成為會員 | LINE 整合 → ① LINE Login |
| **Messaging API**（LINE 官方帳號） | 同步好友、傳通知給管理員與會員、群發訊息 | LINE 整合 → ② Messaging API |

> ⚠️ **最重要的一點**：這兩個 Channel 必須建立在 LINE Developers 的**同一個 Provider** 底下。
> LINE 的使用者 ID（userId）是「每個 Provider 各自一組」，不同 Provider 的 ID 對不起來，網站就無法把通知傳給用 LINE 登入的會員。

> 💡 LINE Notify 已於 2025 年 3 月 31 日停止服務，本系統改用 Messaging API 推播通知。

---

## 一、建立（或確認）LINE 官方帳號並啟用 Messaging API

1. 到 [LINE Official Account Manager](https://manager.line.biz/) 登入。沒有官方帳號的話，按「建立帳號」建立一個 aftermoonF 的官方帳號。
2. 進入官方帳號 → 右上角 **設定** → **Messaging API** → **啟用 Messaging API**。
3. 選擇 Provider：
   - 第一次使用可以建立新的 Provider，例如 `aftermoonF`。
   - **之後建立 LINE Login Channel 時要選同一個 Provider。**
4. 回到 **設定 → 回應設定**：
   - 「Webhook」請**開啟**。
   - 「自動回應訊息」、「加入好友的歡迎訊息」可依需求開關，不影響網站運作。

> 以前的「Notion 任務提醒」與 Make 使用的是另一個機器人。一個官方帳號只能設定一個 Webhook 網址，**建議 aftermoonF 使用新的官方帳號**，避免影響原本的機器人。

## 二、取得 Messaging API 金鑰並設定 Webhook

到 [LINE Developers Console](https://developers.line.biz/console/) → 選你的 Provider → 點剛剛啟用的 **Messaging API Channel**。

1. **Basic settings** 分頁：複製 **Channel secret**。
2. **Messaging API** 分頁：
   - 最下方 **Channel access token (long-lived)** → 按 **Issue** → 複製。
   - 同一頁上方可以看到官方帳號的 **Bot basic ID**（例如 `@123abcde`）。
   - **Webhook URL** 填入：`https://你的網域/api/line/webhook`（後台「LINE 整合」頁面可以直接複製）→ **Update**。
   - 開啟 **Use webhook**。
3. 回到網站後台 **LINE 整合 → ② Messaging API**，貼上 Channel secret、Channel access token、官方帳號 ID → 儲存 → 按「**測試連線**」，看到官方帳號名稱就成功了。
4. 回到 LINE Developers 按 Webhook URL 旁的 **Verify**，應該顯示 Success。

## 三、建立 LINE Login Channel

1. LINE Developers Console → **同一個 Provider** → **Create a new channel** → 選 **LINE Login**。
2. 填寫：
   - Region：Taiwan
   - Channel name：例如「aftermoonF 會員登入」（使用者登入時會看到）
   - Channel icon、description：可用品牌 Logo 與簡介
   - App types：勾選 **Web app**
   - 其餘欄位依畫面填寫後建立。
3. **LINE Login** 分頁 → **Callback URL** 填入：`https://你的網域/auth/line/callback`（後台可直接複製）。
4. **Basic settings** 分頁：
   - 複製 **Channel ID** 與 **Channel secret**。
   - **Linked LINE Official Account**：選擇你的官方帳號（這樣登入時才會引導加入好友，網站也能知道會員是否為好友）。
5. 右上角把 Channel 狀態從 **Developing** 改成 **Published**。
   **沒有發布的話，只有 Channel 的管理員能登入，一般訪客會看到錯誤。**
6. 回到網站後台 **LINE 整合 → ① LINE Login**：
   - 開啟「啟用 LINE 登入」
   - 貼上 Channel ID 與 Channel secret
   - 「登入後引導加入官方帳號好友」建議選「登入後另開畫面詢問」
   - 儲存

### （選用）取得會員 Email

LINE 預設不提供 Email。需要的話：LINE Login Channel → **Basic settings → OpenID Connect → Email address permission → Apply**，依指示上傳隱私權政策同意畫面的截圖。通過審核後，再到網站後台開啟「取得會員 Email」。

## 四、綁定管理員通知（新會員、新留言通知到你的 LINE）

1. 用手機 LINE 加入你的官方帳號為好友（可以掃官方帳號的 QR Code，或開啟 `https://line.me/R/ti/p/@你的官方帳號ID`）。
2. 網站後台 **LINE 整合 → ③ 管理員 LINE 通知** → 按「**產生綁定碼**」。
3. 在 LINE 對官方帳號傳送畫面上的文字，例如：`綁定 123456`（10 分鐘內有效）。
4. 官方帳號回覆「✅ 綁定成功」後，重新整理後台頁面，就能看到你的名字。
5. 按「**傳送測試通知**」確認可以收到。

- 可以綁定多位管理員（每位都重新產生一次綁定碼）。
- 想停止通知：在後台按「移除」，或在 LINE 傳送「`解除綁定`」。
- 可選擇要通知的事件：新會員加入、聯絡表單新留言、官方帳號新增好友。
- 為了避免垃圾留言把官方帳號的訊息額度用完，同一種通知每小時最多送 20 則；超過時只會再送一則「暫停通知」提醒，留言仍會完整保存在後台「表單訊息」。

## 五、傳送訊息給會員

後台 **LINE 整合 → ④ 傳送訊息**：

- **同意通知的會員**：用 LINE 登入、已加官方帳號好友、且在會員中心開啟「接收 LINE 通知」的會員。
- **官方帳號所有好友**：包含沒有註冊會員的好友。
- 也可以在 **會員 → 會員詳情** 單獨傳訊息給某位會員。

> 推播、群發都會計入官方帳號方案的**每月免費訊息則數**，超過需要升級方案。後台「測試連線」會顯示本月已使用的則數。

## 常見問題

| 問題 | 原因與解法 |
|---|---|
| 登入時出現 `400 Bad Request`／`redirect_uri` 不符 | Callback URL 要和後台顯示的完全一樣（https、www、結尾都要相同）。 |
| 只有自己能登入，別人不行 | LINE Login Channel 還在 **Developing**，請改成 **Published**。 |
| 會員已加好友，但後台顯示「未加好友」 | 確認 LINE Login Channel 有連結官方帳號（Linked LINE Official Account），且兩個 Channel 在同一個 Provider。 |
| Webhook Verify 失敗 | 網站需要 https；確認後台填的 Channel secret 是 **Messaging API Channel** 的（不是 LINE Login 的）。 |
| 綁定碼傳了沒反應 | 確認「Use webhook」已開啟、官方帳號「回應設定」的 Webhook 已開啟；綁定碼 10 分鐘後失效，請重新產生。 |
| 傳送失敗 `The monthly limit has been exceeded` | 本月免費訊息則數已用完，需升級官方帳號方案或等下個月。 |
