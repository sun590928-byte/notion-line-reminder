# Google 登入設定

讓會員可以用 Google 帳號登入（也可以在會員中心同時綁定 LINE 與 Google）。

## 1. 建立 Google Cloud 專案

1. 到 [Google Cloud Console](https://console.cloud.google.com/)，用你的 Google 帳號登入。
2. 左上角專案選單 → **新增專案**，名稱例如 `aftermoonF`。

## 2. 設定 OAuth 同意畫面

左側選單 **API 和服務 → OAuth 同意畫面**（新版介面叫 **Google Auth Platform**）：

1. **品牌（Branding）**：應用程式名稱填「aftermoonF」、使用者支援電子郵件、上傳 Logo（選填）、首頁網址與隱私權政策網址（可先填官網網址）、**授權網域**加入你的網域（例如 `aftermoonf.com`）。
2. **目標對象（Audience）**：使用者類型選 **外部（External）**。
3. **資料存取（Data access）**：本系統只使用 `openid`、`email`、`profile` 三個基本範圍，不需要額外審查。
4. 最後把發布狀態改為 **正式版（In production）**。
   **維持「測試中」的話，只有加入測試使用者名單的帳號能登入。**

## 3. 建立 OAuth 用戶端 ID

**API 和服務 → 憑證 → 建立憑證 → OAuth 用戶端 ID**：

1. 應用程式類型：**網頁應用程式**
2. 名稱：例如「aftermoonF 網站」
3. **已授權的 JavaScript 來源**：`https://你的網域`
4. **已授權的重新導向 URI**：`https://你的網域/auth/google/callback`
   （網站後台「網站設定 → 會員登入」可以直接複製）
5. 建立後複製 **用戶端 ID** 與 **用戶端密鑰**。

## 4. 填入網站後台

後台 **網站設定 → 會員登入**：

1. 開啟「啟用 Google 登入」
2. 貼上用戶端 ID 與用戶端密鑰（密鑰會加密儲存，之後只顯示末四碼）
3. 儲存

完成後，登入頁、連結頁與官網的「會員專區」都會出現「使用 Google 登入」按鈕。

## 常見問題

| 問題 | 解法 |
|---|---|
| `Error 400: redirect_uri_mismatch` | 重新導向 URI 必須和後台顯示的完全相同（https、www 都要一致）。 |
| `Access blocked: This app's request is invalid` | OAuth 同意畫面還沒填完整，或授權網域沒有加入你的網域。 |
| 只有自己能登入 | 同意畫面還在「測試中」，請改成「正式版」。 |
