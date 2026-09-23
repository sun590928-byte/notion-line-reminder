# 部署到 Plesk 主機（匯智）

以下以 Plesk Obsidian 介面為例（中文介面名稱可能因版本略有不同）。整個流程約 20 分鐘。

## 事前準備

| 項目 | 需求 |
|---|---|
| PHP | 8.1 以上（建議 8.2 或 8.3） |
| 資料庫 | MySQL 5.7+ 或 MariaDB 10.3+（Plesk 內建即可） |
| PHP 擴充 | PDO MySQL、OpenSSL、mbstring（必要）；cURL、fileinfo、GD、DOM（建議，Plesk 預設通常都已啟用） |
| 網域 | 已在 Plesk 建立網站，並能用瀏覽器連到 |

## 步驟 1：切換 PHP 版本

**網站與網域 → 你的網域 → PHP 設定**

1. 「PHP 版本」選 **8.2** 或 **8.3**（處理常式選「FPM application served by Apache」或「FPM served by nginx」都可以）。
2. 建議同時把下列數值調高（在同一頁的「效能和安全性設定」）：
   - `upload_max_filesize`：`16M`
   - `post_max_size`：`20M`
   - `memory_limit`：`256M`
3. 按「確定」。

## 步驟 2：建立資料庫

**網站與網域 → 資料庫 → 新增資料庫**

1. 資料庫名稱：例如 `aftermoonf`
2. 勾選「建立資料庫使用者」，填使用者名稱與密碼（請記下來）
3. 建立後，在資料庫列表可以看到**主機名稱**（通常是 `localhost` 或 `localhost:3306`）

## 步驟 3：上傳程式

### 方法 A：用 Plesk「Git」自動部署（推薦，之後更新最方便）

1. **網站與網域 → Git → 新增儲存庫**
2. 選「遠端 Git 託管」，儲存庫網址：
   - 公開儲存庫：`https://github.com/sun590928-byte/aftermoonF.git`
   - **私人儲存庫**：改用 SSH 網址 `git@github.com:sun590928-byte/aftermoonF.git`，Plesk 會顯示一組 **SSH 公鑰**，把它貼到 GitHub 儲存庫的 **Settings → Deploy keys → Add deploy key**（只需要讀取權限）
3. 分支選 `main`，部署模式選「自動」，部署路徑填 `/httpdocs`
4. 按「確定」，Plesk 會把程式抓到 `httpdocs`

之後在 GitHub 更新程式，Plesk 就會自動（或按「部署」）更新網站。`config/config.php` 與上傳的圖片不在 Git 裡，更新不會被覆蓋。

> 如果 GitHub 儲存庫還沒改名，網址中的 `aftermoonF` 請先用 `notion-line-reminder`。

### 方法 B：手動上傳

1. 在 GitHub 儲存庫頁面按 **Code → Download ZIP**
2. Plesk **檔案** → 進入 `httpdocs` → 刪除 Plesk 預設的 `index.html` 等檔案 → **上傳** ZIP → 在 ZIP 上按「解壓縮」
3. 解壓縮後若多了一層資料夾（例如 `aftermoonF-main/`），把裡面的所有檔案移到 `httpdocs` 底下

## 步驟 4：設定文件根目錄與 HTTPS

**網站與網域 → 主機設定（Hosting Settings）**

1. **文件根目錄（Document root）**改為 `httpdocs/public`，儲存。
   - 這樣網站程式、設定檔與資料都不會被外部直接讀取，是最安全的做法。
   - 若無法修改，保持 `httpdocs` 也能運作：根目錄的 `.htaccess` 會自動把請求轉給 `public/`。
2. **SSL/TLS 憑證** → 用 **Let's Encrypt** 免費憑證保護網域（勾選 www 子網域）。
3. 回到「主機設定」：
   - 勾選「**永久 SEO 安全 301 重新導向：從 HTTP 導向 HTTPS**」
   - 「**偏好網域**」選擇有 www 或沒有 www（之後 LINE／Google 回呼網址要用同一個）

> **LINE 與 Google 登入都要求 https**，請務必完成這一步再設定登入。

## 步驟 5：執行安裝程式

1. 用瀏覽器開啟 `https://你的網域/install`
2. 檢查環境：若有標示「必要」的紅點，回到 PHP 設定處理
3. 資料庫選 **MySQL / MariaDB**，填入步驟 2 的資料庫名稱、使用者、密碼、主機
4. 網站網址：確認是 `https://` 開頭、與「偏好網域」一致的正式網址
5. 設定管理員帳號與密碼（至少 8 個字元）
6. 按「開始安裝」→ 完成後自動登入後台 `/admin`

安裝完成後：
- 首頁會顯示預設的**連結頁**（暮光主題）
- **官方網站**已建立 4 個頁面的範本，但是**未發布、隱藏中**
- 安裝程式會自動鎖定；`/install` 之後只會轉到後台

若出現「無法自動寫入設定檔」，代表 `config/` 資料夾沒有寫入權限：照畫面指示在 Plesk「檔案」建立 `config/config.php` 並貼上內容即可。

## 步驟 6：開始設定

1. 後台 **連結頁**：換成你的大頭貼、簡介與連結 → 按「發布」
2. **LINE 整合**：依 [line-setup.md](line-setup.md) 設定 LINE Login 與官方帳號
3. **網站設定 → 會員登入**：（選用）依 [google-setup.md](google-setup.md) 設定 Google 登入
4. **官方網站**：慢慢編輯內容，準備好後按「發布」並選擇「同時公開官網」

---

## 備份

- **Plesk → 備份管理員**：建議設定每日自動備份（同時備份檔案與資料庫）。
- 最重要的資料：資料庫、`config/config.php`（內含加密金鑰，遺失會導致已存的 LINE／Google 金鑰無法解密）、`public/uploads/`。

## 更新程式

- 使用 Git 部署：在 GitHub 合併新版本後，Plesk 會自動部署（手動模式則在 Plesk「Git」按「部署」）。
- 手動上傳：覆蓋 `app/`、`public/assets/`、`public/index.php` 等程式檔即可，不要覆蓋 `config/config.php`、`storage/`、`public/uploads/`。

## 特殊主機環境

### 只有 nginx（沒有 Apache）

在 **主機設定 → Apache 與 nginx 設定 → 其他 nginx 指令** 加入：

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
location ~ ^/(app|config|storage|tools|docs)/ {
    deny all;
}
```

### Windows 主機（IIS）

程式已附上 `web.config`（需要 IIS URL Rewrite 模組，Plesk Windows 預設已安裝），其他步驟相同。

### 放在子目錄

例如 `https://example.com/site/`：把整個專案放在 `httpdocs/site/`，網址會自動加上 `/site`，不需要額外設定。安裝時「網站網址」請填 `https://example.com/site`。

## 疑難排解

| 狀況 | 解決方式 |
|---|---|
| 開啟網站出現 500 錯誤 | 查看 `storage/logs/` 的錯誤紀錄；或暫時把 `config/config.php` 的 `'debug' => false` 改成 `true` 顯示詳細錯誤（查完記得改回）。 |
| 首頁正常，但 `/admin` 顯示 404 | 網址重寫沒有生效：確認文件根目錄設定、`.htaccess` 有上傳（隱藏檔），nginx-only 主機請加上面的指令。暫時可用 `/index.php/admin` 進入。 |
| 圖片上傳失敗 | 調高 PHP 的 `upload_max_filesize`、`post_max_size`；確認 `public/uploads/` 可寫入。 |
| LINE 登入顯示 `redirect_uri` 錯誤 | LINE Developers 的 Callback URL 必須和後台「LINE 整合」顯示的完全相同（https、www 都要一致）。 |
| 換網域後登入失效 | 到 **網站設定 → 正式網址** 改成新網址，並更新 LINE／Google 後台的回呼網址。 |
| 忘記管理員密碼 | 在 Plesk「資料庫 → phpMyAdmin」執行：`UPDATE amf_admins SET password_hash = '<新雜湊>' WHERE username = 'admin';`，雜湊可在 Plesk「PHP 設定」旁的終端機執行 `php -r "echo password_hash('新密碼', PASSWORD_DEFAULT);"` 產生。 |
