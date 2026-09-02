# LINE 群組翻譯機器人

把機器人邀請進 LINE 群組，**群組裡有人發話就自動翻成多國語言並回覆**。

- 部署平台：**Cloudflare Workers**（免費、常駐不休眠、免顧機器）
- 翻譯：**Google 翻譯免費端點**（不用 API key、不用綁信用卡）
- 自動偵測來源語言，翻成你設定的多個目標語言（預設 英文 / 日文 / 繁中）

> 跟同 repo 的 `check_reminders.py`（每天定時推播）不同，翻譯 bot 是「即時回應」，
> 需要一個隨時在線、能接收 webhook 的伺服器——所以用 Cloudflare Workers，不能用 GitHub Actions。

---

## 運作流程

```
群組有人講話 → LINE 把訊息 webhook 送到 Cloudflare Worker
            → Worker 呼叫 Google 翻譯（自動偵測語言）
            → 翻成各目標語言 → 回覆到群組
```

---

## 部署步驟

### 1. 安裝工具並登入 Cloudflare
先裝好 [Node.js](https://nodejs.org/)，然後在 `line-translate-bot/` 資料夾裡：

```bash
cd line-translate-bot
npx wrangler login        # 會開瀏覽器登入你的 Cloudflare 帳號（免費註冊即可）
```

### 2. 設定 LINE 的 Channel access token（機密）
用你現有的 Messaging API channel 就行（跟提醒機器人同一個也可以）。
到 LINE Developers Console → 你的 channel → **Messaging API** 分頁 → 複製 **Channel access token**，然後：

```bash
npx wrangler secret put LINE_CHANNEL_TOKEN
# 貼上 token 後按 Enter
```

（選填但建議）順便設簽章驗證，擋掉偽造請求。到 channel 的 **Basic settings** 複製 **Channel secret**：

```bash
npx wrangler secret put LINE_CHANNEL_SECRET
```

### 3. （選填）改要翻成哪些語言
編輯 `wrangler.toml` 的 `TARGET_LANGS`，逗號分隔。可用代碼看 `worker.js` 裡的 `LANG_LABELS`
（en 英、ja 日、ko 韓、zh-TW 繁中、zh-CN 簡中、th 泰、vi 越、id 印尼、es 西、fr 法、de 德）。

```toml
TARGET_LANGS = "en,ja,zh-TW"
```

### 4. 部署
```bash
npx wrangler deploy
```
部署完成後會印出你的 Worker 網址，長得像：
```
https://line-translate-bot.你的帳號.workers.dev
```

### 5. 把網址填回 LINE Webhook
LINE Developers Console → 你的 channel → **Messaging API** 分頁：
1. **Webhook URL** 填上一步的網址（結尾不用加路徑）
2. 按 **Verify** 測試，應該顯示 Success
3. 打開 **Use webhook**

### 6. 開放群組聊天 + 邀請進群組
到 [LINE Official Account Manager](https://manager.line.biz/) → 你的帳號 → **設定 → 回應設定**：
- **允許加入群組／多人聊天室**：打開
- 把「Webhook」打開（若有「自動回應訊息」建議關掉，免得洗版）

然後在 LINE 裡把這個官方帳號邀請進你的群組即可。

> ⚠️ **關於群組收訊**：LINE 官方帳號在群組裡，預設能收到群組成員的文字訊息 webhook。
> 但不同方案／設定行為可能不同——若發現 bot 收不到別人的訊息，先確認上面「回應設定」都對，
> 並在群組裡先跟 bot 說一句話測試。

---

## 測試
在群組（或 1 對 1）對 bot 講一句中文，它應該回覆英文、日文等翻譯。
若沒反應，到 Cloudflare 後台看 Worker 的 **Logs**（或本機跑 `npx wrangler tail` 看即時 log）。

---

## 常見調整

| 想做的事 | 改哪裡 |
|---|---|
| 增減翻譯語言 | `wrangler.toml` 的 `TARGET_LANGS`；沒對照到的語言到 `worker.js` 的 `LANG_LABELS` 補一行 |
| 換翻譯引擎（改用 DeepL / Claude） | 改 `worker.js` 的 `googleTranslate()` 函式 |
| 不想翻某些訊息（例如指令、貼圖） | 在 `handleEvent()` 加過濾條件 |

---

## 費用
- Cloudflare Workers 免費方案：每天 10 萬次請求，一般群組用量綽綽有餘。
- Google 翻譯免費端點：無需付費，但屬非官方端點、量太大可能被限流；若要正式穩定可改接官方 API 或 DeepL。
