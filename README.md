# aftermoonF 官方網站系統

不使用 WordPress、專為 **Plesk 主機（匯智）** 打造的輕量網站系統。
介面參考 Wix 的「區塊式所見即所得編輯」，搭配 Linktree 風格連結頁、LINE／Google 會員登入與 LINE 官方帳號通知。

- 只需要 **PHP 8.1 以上**＋**MySQL／MariaDB**（或 SQLite），不用 Node.js、不用安裝套件
- 上傳後打開 `/install` 就能完成安裝，後台在 `/admin`
- 目前預設：**首頁顯示連結頁、官方網站以未發布草稿保存並隱藏**

---

## 功能總覽

### 公開網站
| 功能 | 說明 |
|---|---|
| 連結頁（Linktree 風格） | 大頭貼、簡介、社群圖示、連結按鈕、分類標題、排程上下架、醒目效果（脈動／搖晃／彈跳／光澤）、分享按鈕、點擊統計。8 種主題（預設「暮光」星空背景）。 |
| 官方網站 | 21 種區塊：主視覺、圖文、特色、卡片、相簿、數字、好評、FAQ、行動呼籲、聯絡表單、影片、時間軸、跑馬燈、合作夥伴、方案價格、團隊、連結按鈕、會員專區、自訂 HTML、間隔線。 |
| 捲動與懸停效果 | 捲動進場（8 種）、標題逐字浮現、視差背景、數字跳動、時間軸進度線、跑馬燈、3D 傾斜卡片、按鈕光澤、滑鼠光暈、燈箱、閱讀進度條。自動支援「減少動態效果」設定。 |
| 首頁模式 | 連結頁（官網隱藏）／官方網站／即將推出，後台一鍵切換。 |
| 會員 | LINE Login、Google 登入，一個會員可同時綁定兩種；會員中心可改資料、綁定／解除、開關 LINE 通知；可設定「會員限定頁面」。 |
| SEO | 每頁標題／描述／分享圖、sitemap.xml、robots.txt、GA4。 |

### 後台 `/admin`
| 頁面 | 說明 |
|---|---|
| 儀表板 | 首頁模式切換、發布狀態、瀏覽與點擊統計圖、熱門連結、設定清單、最新會員與留言。 |
| 連結頁編輯器 | 手機框即時預覽，拖曳排序、圖示挑選（47 個社群品牌＋90 個一般圖示＋表情符號＋自訂圖片）。 |
| 官網編輯器 | Wix 式：左側區塊清單／右側即時預覽；**點預覽畫面選區塊、雙擊文字直接修改**、拖曳排序、復原／重做、電腦／平板／手機預覽、頁面管理、主題配色。 |
| 草稿與發布 | 編輯內容自動存成草稿，按「發布」才會公開；保留 30 個發布版本可還原。 |
| 媒體庫 | 拖曳上傳，大圖自動縮小、手機照片自動轉正並移除 GPS 資訊。 |
| 會員／表單訊息 | 會員列表與詳情（可停權、備註、傳 LINE 訊息）；聯絡表單收件匣。 |
| LINE 整合 | LINE Login、Messaging API、Webhook、管理員通知綁定、群發訊息、訊息紀錄與本月用量。 |
| 網站設定 | 網站名稱、正式網址、Logo、Google 登入、SEO、自訂程式碼、系統資訊。 |

---

## 快速開始（Plesk）

完整步驟請看 **[docs/deploy-plesk.md](docs/deploy-plesk.md)**，摘要如下：

1. Plesk「PHP 設定」選 PHP 8.2 或 8.3。
2. Plesk「資料庫」新增一個 MySQL／MariaDB 資料庫與使用者。
3. 用 Plesk「Git」連接這個 GitHub 儲存庫（或下載 ZIP 上傳到 `httpdocs`）。
4. 「主機設定」把文件根目錄改成 `httpdocs/public`，並用 Let's Encrypt 啟用 HTTPS。
5. 開啟 `https://你的網域/install`，填資料庫與管理員帳號。
6. 到 `https://你的網域/admin` 開始編輯。

接著設定 LINE：**[docs/line-setup.md](docs/line-setup.md)**；Google 登入：**[docs/google-setup.md](docs/google-setup.md)**；後台操作：**[docs/admin-guide.md](docs/admin-guide.md)**。

---

## 目錄結構

```
├── public/                 ← 網站文件根目錄（Plesk 請指向這裡）
│   ├── index.php           前端控制器
│   ├── .htaccess / web.config   Apache／IIS 網址重寫
│   ├── assets/             CSS、JS、圖示、預設圖片
│   └── uploads/            媒體庫上傳檔（不進 Git）
├── app/
│   ├── Core/               路由、資料庫、Session、加密、HTTP、流量限制
│   ├── Services/           連結頁、官網文件、區塊、渲染、OAuth、LINE、會員、媒體
│   ├── Controllers/        公開頁、會員登入、API、LINE Webhook、安裝、Admin/*
│   ├── Views/              PHP 版型（site 區塊、links、member、admin）
│   ├── Data/               區塊定義、主題、字體、預設內容、圖示資料
│   └── routes.php          所有網址
├── config/config.php       安裝後產生（資料庫、加密金鑰；不進 Git）
├── storage/                SQLite、Session、錯誤紀錄（不進 Git）
├── docs/                   部署與設定文件
└── tools/                  本機開發伺服器、圖示產生器
```

專案根目錄也放了 `.htaccess`／`web.config`／`index.php` 作為備援：即使文件根目錄沒改成 `public`，網站仍可運作，且會擋掉 `app/`、`config/`、`storage/` 的直接存取。

## 本機開發

```bash
php -S localhost:8000 -t public tools/dev-server.php
# 開啟 http://localhost:8000/install，資料庫選 SQLite 即可
```

修改圖示清單後重新產生圖示資料（需要 Node.js，正式主機不需要）：

```bash
npm install && npm run icons
```

## 安全設計

- 後台所有寫入操作都需要登入＋CSRF 權杖；登入失敗次數限制、12 小時閒置自動登出（可選 14 天記住我）、改密碼後其他裝置自動登出。
- LINE／Google 登入使用 state＋nonce＋PKCE；登入後的返回網址只允許站內路徑。
- LINE Webhook 驗證 `X-Line-Signature`，重送事件不重複處理。
- Channel secret、Access token、Google 用戶端密鑰以 AES-256-GCM 加密後存入資料庫，後台只顯示末四碼。
- 富文字內容經白名單過濾；上傳只接受圖片（依實際檔案內容判斷）、隨機檔名，`uploads/` 禁止執行程式。
- 聯絡表單有蜜罐欄位、簽章時間權杖與 IP 流量限制。

## 從 notion-line-reminder 到 aftermoonF

這個儲存庫原本是「Notion → LINE 每日任務提醒」（Python＋GitHub Actions）。已依需求清除原有檔案，改為 aftermoonF 官網系統。
儲存庫名稱請到 GitHub → **Settings → General → Repository name** 改為 `aftermoonF`（GitHub 會自動把舊網址轉到新名稱）。
原本放在 **Settings → Secrets and variables → Actions** 的 `NOTION_TOKEN`、`NOTION_DATABASE_ID`、`LINE_CHANNEL_TOKEN`、`LINE_USER_ID` 已不再使用，可一併刪除。

## 授權與素材

- 品牌圖示：[Simple Icons](https://simpleicons.org/)（CC0 1.0）；一般圖示：[Lucide](https://lucide.dev/)（ISC License）。各品牌標誌屬於其所有者，使用時請遵守品牌規範。
- 字體由 Google Fonts 提供（思源黑體、思源宋體、霞鶩文楷、粉圓體等，皆為開源授權）。
