/**
 * LINE 群組翻譯機器人 —— Cloudflare Worker
 *
 * 功能：把機器人邀請進 LINE 群組後，群組裡有人發話，
 *       自動偵測語言並翻成設定好的多國語言，回覆到群組。
 *
 * 需要的環境變數（在 Cloudflare 後台 Settings → Variables 設定）：
 *   LINE_CHANNEL_TOKEN   （必填）LINE Messaging API 的 Channel access token
 *   LINE_CHANNEL_SECRET  （選填）填了就會驗證 webhook 簽章，比較安全
 *   TARGET_LANGS         （選填）要翻成哪些語言，逗號分隔，預設 "en,ja,zh-TW"
 *
 * 翻譯用 Google 翻譯的免費端點，不需要 API key、不用綁信用卡。
 */

// 語言代碼 → 顯示用的標籤（旗幟 + 名稱）。要加語言就往這裡補。
const LANG_LABELS = {
  "en": "🇺🇸 English",
  "ja": "🇯🇵 日本語",
  "ko": "🇰🇷 한국어",
  "zh-TW": "🇹🇼 繁體中文",
  "zh-CN": "🇨🇳 简体中文",
  "th": "🇹🇭 ไทย",
  "vi": "🇻🇳 Tiếng Việt",
  "id": "🇮🇩 Bahasa Indonesia",
  "es": "🇪🇸 Español",
  "fr": "🇫🇷 Français",
  "de": "🇩🇪 Deutsch",
};

export default {
  async fetch(request, env) {
    if (request.method !== "POST") {
      // 給瀏覽器打開時看的健康檢查頁
      return new Response("LINE translate bot is running.", { status: 200 });
    }

    const bodyText = await request.text();

    // 有設 CHANNEL_SECRET 就驗簽章，擋掉偽造請求
    if (env.LINE_CHANNEL_SECRET) {
      const signature = request.headers.get("x-line-signature");
      const valid = await verifySignature(bodyText, signature, env.LINE_CHANNEL_SECRET);
      if (!valid) {
        return new Response("Bad signature", { status: 401 });
      }
    }

    let payload;
    try {
      payload = JSON.parse(bodyText);
    } catch {
      return new Response("Bad JSON", { status: 400 });
    }

    const targetLangs = (env.TARGET_LANGS || "en,ja,zh-TW")
      .split(",")
      .map((s) => s.trim())
      .filter(Boolean);

    // 逐一處理每個事件（一次 webhook 可能夾帶多個）
    const events = payload.events || [];
    await Promise.all(
      events.map((event) => handleEvent(event, targetLangs, env))
    );

    // LINE 只要 200 就好
    return new Response("OK", { status: 200 });
  },
};

async function handleEvent(event, targetLangs, env) {
  // 只處理「文字訊息」事件
  if (event.type !== "message" || !event.message || event.message.type !== "text") {
    return;
  }

  const text = event.message.text.trim();
  if (!text) return;

  // 翻成每個目標語言（來源語言本身會自動略過）
  const results = await translateToLangs(text, targetLangs);
  if (results.length === 0) {
    // 沒有需要翻的（例如原文就等於唯一目標語言），不回覆
    return;
  }

  const replyText = results
    .map((r) => `${LANG_LABELS[r.lang] || r.lang}\n${r.text}`)
    .join("\n\n");

  await replyToLine(event.replyToken, replyText, env.LINE_CHANNEL_TOKEN);
}

/**
 * 把文字翻成多個目標語言。
 * 若翻出來的結果跟原文一模一樣，代表原文本來就是這個語言，就跳過不收
 * ——這比信任端點回報的「偵測語言」欄位可靠（那個免費端點常回報錯誤）。
 */
async function translateToLangs(text, targetLangs) {
  const out = [];

  for (const lang of targetLangs) {
    try {
      const translated = await googleTranslate(text, lang);
      if (!translated) continue;
      // 翻出來等於原文 → 原文就是這個語言，不用列
      if (normalize(translated) === normalize(text)) continue;
      out.push({ lang, text: translated });
    } catch (e) {
      // 單一語言失敗不影響其他語言
      console.log(`translate to ${lang} failed: ${e}`);
    }
  }
  return out;
}

/**
 * Google 翻譯免費端點。sl=auto 讓它自動偵測來源語言。
 * 回傳格式是巢狀陣列：data[0] 是分段翻譯結果。
 */
async function googleTranslate(text, targetLang) {
  const url =
    "https://translate.googleapis.com/translate_a/single" +
    "?client=gtx&sl=auto&dt=t" +
    "&tl=" + encodeURIComponent(targetLang) +
    "&q=" + encodeURIComponent(text);

  const resp = await fetch(url, {
    headers: { "User-Agent": "Mozilla/5.0" },
  });
  if (!resp.ok) {
    throw new Error(`google translate HTTP ${resp.status}`);
  }
  const data = await resp.json();

  return (data[0] || [])
    .map((seg) => (seg && seg[0]) || "")
    .join("");
}

// 比較用：去掉空白、轉小寫，判斷「翻譯結果是否等於原文」
function normalize(s) {
  return s.replace(/\s+/g, "").toLowerCase();
}

async function replyToLine(replyToken, text, channelToken) {
  const resp = await fetch("https://api.line.me/v2/bot/message/reply", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "Authorization": `Bearer ${channelToken}`,
    },
    body: JSON.stringify({
      replyToken,
      messages: [{ type: "text", text: text.slice(0, 5000) }], // LINE 單則上限 5000 字
    }),
  });
  if (!resp.ok) {
    console.log(`LINE reply failed ${resp.status}: ${await resp.text()}`);
  }
}

// 用 Web Crypto 做 HMAC-SHA256 + base64，驗證 LINE webhook 簽章
async function verifySignature(body, signature, secret) {
  if (!signature) return false;
  const enc = new TextEncoder();
  const key = await crypto.subtle.importKey(
    "raw",
    enc.encode(secret),
    { name: "HMAC", hash: "SHA-256" },
    false,
    ["sign"]
  );
  const mac = await crypto.subtle.sign("HMAC", key, enc.encode(body));
  const expected = btoa(String.fromCharCode(...new Uint8Array(mac)));
  return timingSafeEqual(expected, signature);
}

function timingSafeEqual(a, b) {
  if (a.length !== b.length) return false;
  let diff = 0;
  for (let i = 0; i < a.length; i++) {
    diff |= a.charCodeAt(i) ^ b.charCodeAt(i);
  }
  return diff === 0;
}
