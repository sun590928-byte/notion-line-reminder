# Notion → LINE 任務提醒

每天自動檢查 Notion「任務」資料庫，把快到期（未來 2 天內）或已逾期、且狀態不是「完成/已封存」的任務整理成一則訊息推到你的 LINE。

## 設定步驟

### 1. 建一個新的 GitHub Repo
私有 repo 即可（因為裡面會跑你的密鑰），把這個資料夾的內容整個上傳上去，結構長這樣：

```
your-repo/
├── check_reminders.py
├── README.md
└── .github/
    └── workflows/
        └── daily-reminder.yml
```

### 2. 取得 Notion Integration Token
1. 前往 https://www.notion.so/my-integrations
2. 建立一個新的 Internal Integration，命名例如「任務提醒機器人」
3. 複製產生的 Token（開頭是 `secret_` 或 `ntn_`）
4. 回到你的「任務」資料庫頁面，右上角 `...` → `連結` → 把剛剛建立的 Integration 加進去（**這一步不做的話，Token 沒有權限讀取資料庫**）

### 3. 取得資料庫 ID
打開「任務」資料庫，網址長這樣：
```
https://www.notion.so/xxxxxxxx任務-14a779b15176 8057 9dfe e4be3622f6b4?v=...
```
把網址裡 32 碼的那串（去掉 `?v=` 後面的部分）取出來即可，就是資料庫 ID。

### 4. 取得 LINE Messaging API 的 Channel Access Token
如果你已經有 LINE Bot（之前 Make 用的那個 attendance 機器人的 channel），可以直接沿用：
1. 前往 LINE Developers Console → 選你的 Provider → 選對應的 Messaging API Channel
2. 在「Messaging API」分頁裡找到 Channel access token，若沒有就按 Issue 產生一個

### 5. 取得你自己的 LINE User ID
最簡單的方式：讓你的 LINE Bot 收到你發的任何一則訊息，在 Webhook 收到的 event 裡的 `source.userId` 就是。如果你之前 Make scenario 已經處理過 webhook，那邊應該已經記錄過你的 user ID，直接沿用即可。

### 6. 把 4 個值加進 GitHub Repo 的 Secrets
Repo 頁面 → Settings → Secrets and variables → Actions → New repository secret，新增：

| Secret 名稱 | 值 |
|---|---|
| `NOTION_TOKEN` | 步驟 2 拿到的 Token |
| `NOTION_DATABASE_ID` | 步驟 3 拿到的資料庫 ID |
| `LINE_CHANNEL_TOKEN` | 步驟 4 拿到的 Channel Access Token |
| `LINE_USER_ID` | 步驟 5 拿到的 User ID |

### 7. 測試
Repo 頁面 → Actions → 選「Daily Notion Task Reminder to LINE」→ 右邊 `Run workflow` 手動觸發一次，看 LINE 有沒有收到訊息。沒問題的話，之後就會照 cron（每天台北時間早上 8 點）自動跑。

## 之後想調整

- 想改推播時間：改 `.github/workflows/daily-reminder.yml` 裡的 `cron` 那一行（用 UTC 時間，台北是 UTC+8）
- 想改「幾天內算快到期」：改 `check_reminders.py` 裡的 `LOOKAHEAD_DAYS`
- 想同時推給多人：`LINE_USER_ID` 可以改用 LINE 群組 ID，或是把腳本改成推播給多個 user id 的清單
