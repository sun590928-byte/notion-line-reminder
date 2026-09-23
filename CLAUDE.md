# aftermoonF — 開發說明（給 Claude／開發者）

aftermoonF 官網系統：純 PHP 8.1+（無框架、無 Composer 相依）、部署在匯智 Plesk 主機。使用者介面與文件一律使用**繁體中文（台灣用語）**。

## 架構重點

- 前端控制器 `public/index.php` → `app/bootstrap.php` → `App\Core\App::run()` → `app/routes.php`。
- 自動載入：`App\Foo\Bar` 對應 `app/Foo/Bar.php`。
- 資料庫：`App\Core\DB`（PDO），SQL 內用 `{table}` 表示資料表（會加上前綴 `amf_`）。同時支援 MySQL／MariaDB 與 SQLite，**新增 SQL 時兩者都要能執行**（避免方言；upsert 用 `DB::upsert()`；計數用 `DB::bumpStat()`）。資料表定義在 `app/Core/Schema.php`（一份定義產生兩種 DDL），升級時在 `Schema::migrate()` 加遷移並提高 `VERSION`。
- 設定：`config/config.php`（安裝程式產生，不進 Git）；網站設定存在 `settings` 資料表（`App\Core\Settings`）。LINE／Google 金鑰用 `App\Core\Crypto::encrypt()` 加密存放。
- **草稿／發布**：`documents` 資料表有 `links`（連結頁）與 `site`（官網）兩份文件，各有 `draft` 與 `published` JSON。後台只改草稿（`/admin/api/doc/{name}/save`，樂觀鎖定 rev），按發布才複製到 published 並寫入 `revisions`。
- **首頁模式** `Settings::mode()`：`links`（預設，官網隱藏）／`website`／`maintenance`，邏輯在 `PublicController`。
- **欄位定義驅動**：官網區塊定義在 `app/Data/sections.php`；`App\Services\Fields::normalize()` 依定義清理資料（伺服器端），`public/assets/js/admin/fields.js` 依同一份定義產生表單（前端）。新增區塊類型 = 在 `sections.php` 加定義 + 新增 `app/Views/site/sections/{type}.php` + 在 `site.css` 加樣式。
- 區塊版型使用 `SiteRenderer`（變數 `$r`）：`$r->ed('path')` 輸出後台即時編輯用的 `data-edit`、`$r->href()`／`$r->url()` 解析 `page:頁面ID#錨點` 連結、`$r->head($d)` 輸出區塊標題。要做捲動進場動畫的元素加上 class `rv`。
- 配色：`Palette::tone()` 依背景亮度計算文字／卡片／按鈕色，CSS 變數 `--t-{default|alt|primary|dark|accent|image}-*` → 區塊內 `--s-*`。
- 後台前端：原生 ES modules（`public/assets/js/admin/`），無打包工具。`core.js`（API、對話框）、`fields.js`（表單產生器、拖曳排序、媒體庫、圖示挑選）、`editor-core.js`（自動儲存、復原、雙 iframe 預覽）、`site-editor.js`、`links-editor.js`、`app.js`（一般頁面）。
- 會員登入：`App\Services\OAuth`（LINE Login v2.1、Google OIDC，state＋nonce＋PKCE）；LINE 通知：`App\Services\LineBot`、`Notifier`；Webhook：`LineWebhookController`（簽章驗證、`綁定 123456` 管理員綁定指令）。

## 安全慣例

- 所有 PHP 輸出用 `e()`；富文字經 `Sanitizer::html()`；網址經 `Url::clean()`／`Url::image()`。
- 後台 API 控制器繼承 `Admin\AdminBase`，寫入動作呼叫 `$this->api()`（CSRF）。會員端表單用 `csrf_field()`＋`verify_csrf()`。
- 轉址用 `safe_return()` 避免開放式轉址。
- 前端 JS 插入不受信任文字時用 `textContent` 或 `escapeHtml()`。

## 本機開發與測試

```bash
php -S 127.0.0.1:8000 -t public tools/dev-server.php   # 開 /install，資料庫選 SQLite；安裝碼在 storage/setup-code.txt
for f in $(find app public tools -name '*.php'); do php -l $f >/dev/null || echo $f; done
node --experimental-default-type=module --check public/assets/js/admin/site-editor.js
```

`tools/dev-server.php` 支援環境變數 `AMF_FAKE_HTTP=/path/to/mock.php`（設定 `App\Core\Http::$mock`）來模擬 LINE／Google API，僅限本機測試。

圖示資料由 `npm run icons`（`tools/build-icons.mjs`）產生 `app/Data/icons.php` 與 `public/assets/js/admin/icons.js`，不要手動修改這兩個檔案。
