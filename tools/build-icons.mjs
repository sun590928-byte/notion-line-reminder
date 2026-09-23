// 產生圖示資料檔：app/Data/icons.php（後端用）與 public/assets/js/icons.js（後台編輯器用）
// 使用方式：npm install && npm run icons
// 品牌圖示來源：Simple Icons（CC0）；一般圖示來源：Lucide（ISC）
import { readFileSync, writeFileSync, existsSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const siDir = join(root, 'node_modules/simple-icons/icons');
const luDir = join(root, 'node_modules/lucide-static/icons');

// [slug, 顯示名稱, 搜尋關鍵字, 品牌色（留空用 Simple Icons 預設）]
const BRANDS = [
  ['line', 'LINE', 'line 賴 官方帳號 好友', '#06C755'],
  ['instagram', 'Instagram', 'ig 哀居 限動', '#FF0069'],
  ['facebook', 'Facebook', 'fb 臉書 粉專 粉絲專頁'],
  ['messenger', 'Messenger', 'fb 訊息 私訊'],
  ['threads', 'Threads', '脆 串文'],
  ['x', 'X (Twitter)', 'twitter 推特'],
  ['youtube', 'YouTube', 'yt 影片 頻道'],
  ['tiktok', 'TikTok', '抖音 短影音'],
  ['spotify', 'Spotify', '音樂 podcast 播客'],
  ['applemusic', 'Apple Music', '音樂'],
  ['applepodcasts', 'Apple Podcasts', 'podcast 播客'],
  ['soundcloud', 'SoundCloud', '音樂'],
  ['telegram', 'Telegram', '訊息'],
  ['whatsapp', 'WhatsApp', '訊息'],
  ['wechat', 'WeChat', '微信'],
  ['discord', 'Discord', '社群 伺服器'],
  ['pinterest', 'Pinterest', '靈感'],
  ['xiaohongshu', '小紅書', 'xiaohongshu rednote'],
  ['sinaweibo', '微博', 'weibo'],
  ['bilibili', 'Bilibili', 'b站'],
  ['shopee', '蝦皮購物', 'shopee 商店 購物'],
  ['etsy', 'Etsy', '商店 手作'],
  ['googlemaps', 'Google 地圖', '地圖 maps 導航 地址'],
  ['gmail', 'Gmail', 'email 信箱'],
  ['google', 'Google', 'google 評論'],
  ['googlecalendar', 'Google 日曆', '日曆 行事曆 預約'],
  ['calendly', 'Calendly', '預約'],
  ['zoom', 'Zoom', '會議 線上'],
  ['github', 'GitHub', '程式'],
  ['medium', 'Medium', '部落格 文章'],
  ['substack', 'Substack', '電子報'],
  ['notion', 'Notion', '筆記 文件'],
  ['patreon', 'Patreon', '贊助 訂閱'],
  ['buymeacoffee', 'Buy Me a Coffee', '贊助 咖啡 斗內'],
  ['kofi', 'Ko-fi', '贊助 斗內'],
  ['twitch', 'Twitch', '直播'],
  ['snapchat', 'Snapchat', ''],
  ['vimeo', 'Vimeo', '影片'],
  ['reddit', 'Reddit', '論壇'],
  ['bluesky', 'Bluesky', ''],
  ['mastodon', 'Mastodon', ''],
  ['tumblr', 'Tumblr', ''],
  ['behance', 'Behance', '作品集'],
  ['dribbble', 'Dribbble', '作品集 設計'],
  ['kakaotalk', 'KakaoTalk', ''],
  ['naver', 'Naver', ''],
  ['linktree', 'Linktree', ''],
];

// [lucide 名稱, 顯示名稱, 搜尋關鍵字]（可用於連結頁的一般圖示）
const GENERAL = [
  ['globe', '網站', 'website 官網 網址'],
  ['link', '連結', 'link'],
  ['external-link', '外部連結', ''],
  ['mail', 'Email', '信箱 郵件 mail'],
  ['phone', '電話', '撥打 聯絡'],
  ['message-circle', '聊天', '訊息 客服'],
  ['message-square', '留言', '訊息'],
  ['send', '傳送', ''],
  ['map-pin', '地點', '地址 位置 門市'],
  ['map', '地圖', ''],
  ['calendar', '行事曆', '預約 活動 日期'],
  ['clock', '時間', '營業時間'],
  ['shopping-bag', '購物', '商店 商品 選購'],
  ['shopping-cart', '購物車', ''],
  ['store', '門市', '店家 商店'],
  ['gift', '禮物', '優惠 贈品'],
  ['ticket', '票券', '活動 報名'],
  ['tag', '標籤', '優惠 價格'],
  ['percent', '折扣', '優惠'],
  ['credit-card', '付款', '刷卡'],
  ['wallet', '錢包', ''],
  ['receipt', '收據', '發票'],
  ['truck', '運送', '物流 宅配'],
  ['package', '包裹', '商品'],
  ['star', '星星', '推薦 評價'],
  ['heart', '愛心', '喜歡'],
  ['sparkles', '閃耀', '新品 特色'],
  ['moon', '月亮', '夜晚'],
  ['sun', '太陽', ''],
  ['sunset', '夕陽', '午後'],
  ['coffee', '咖啡', '咖啡廳'],
  ['utensils', '餐廳', '美食 菜單'],
  ['cake-slice', '甜點', '蛋糕'],
  ['wine', '酒', '酒吧'],
  ['cup-soda', '飲料', '手搖'],
  ['leaf', '葉子', '自然 植物'],
  ['flower-2', '花', '花店'],
  ['book-open', '閱讀', '書籍 部落格 文章'],
  ['newspaper', '最新消息', '新聞 公告'],
  ['file-text', '文件', 'pdf 型錄'],
  ['download', '下載', ''],
  ['music', '音樂', ''],
  ['headphones', '耳機', 'podcast 收聽'],
  ['mic', '麥克風', '講座'],
  ['podcast', 'Podcast', '播客'],
  ['radio', '廣播', ''],
  ['video', '影片', ''],
  ['film', '電影', ''],
  ['clapperboard', '影音', ''],
  ['camera', '相機', '攝影'],
  ['image', '圖片', '作品 相簿'],
  ['megaphone', '公告', '宣傳'],
  ['bell', '通知', ''],
  ['user', '個人', '關於我'],
  ['users', '社群', '團隊 會員'],
  ['house', '首頁', '家'],
  ['building-2', '公司', '企業'],
  ['briefcase', '合作', '商務 工作'],
  ['graduation-cap', '課程', '教學 學習'],
  ['award', '獎項', '榮譽'],
  ['crown', 'VIP', '會員 皇冠'],
  ['gem', '精品', '寶石'],
  ['badge-check', '認證', ''],
  ['thumbs-up', '讚', ''],
  ['smile', '笑臉', ''],
  ['hand-heart', '公益', '愛心'],
  ['paw-print', '寵物', ''],
  ['dumbbell', '健身', '運動'],
  ['plane', '旅行', '飛機'],
  ['car', '交通', '停車'],
  ['compass', '探索', ''],
  ['mountain', '戶外', '山'],
  ['waves', '海', ''],
  ['tent', '露營', ''],
  ['hotel', '住宿', '飯店 民宿'],
  ['scissors', '美髮', '剪刀'],
  ['brush', '美術', '繪畫'],
  ['pen-tool', '設計', ''],
  ['palette', '色彩', '藝術'],
  ['gamepad-2', '遊戲', ''],
  ['rocket', '新計畫', '火箭'],
  ['zap', '快速', '閃電'],
  ['party-popper', '慶祝', '活動'],
  ['qr-code', 'QR Code', ''],
  ['info', '資訊', ''],
  ['circle-help', '常見問題', 'faq 問答'],
  ['at-sign', '@', ''],
  ['hash', 'Hashtag', ''],
  ['lock', '會員限定', '鎖'],
  ['shield-check', '安全', ''],
];

// 後台介面用（不會出現在連結頁的圖示挑選器中）
const UI = [
  'plus', 'trash-2', 'copy', 'eye', 'eye-off', 'grip-vertical', 'chevron-up', 'chevron-down', 'chevron-left',
  'chevron-right', 'settings', 'layout-template', 'layout-dashboard', 'file', 'monitor', 'tablet', 'smartphone',
  'undo-2', 'redo-2', 'upload', 'x', 'check', 'pencil', 'log-out', 'log-in', 'chart-column', 'inbox', 'history',
  'save', 'type', 'layers', 'square-stack', 'columns-3', 'list-ordered', 'list', 'quote', 'images', 'play', 'menu',
  'ellipsis', 'ellipsis-vertical', 'search', 'refresh-cw', 'wand-sparkles', 'rotate-ccw', 'key', 'heading',
  'heading-2', 'heading-3', 'bold', 'italic', 'underline', 'link-2', 'remove-formatting', 'align-left',
  'align-center', 'gallery-horizontal', 'share-2', 'arrow-up', 'arrow-right', 'arrow-left', 'code',
  'separator-horizontal', 'panels-top-left', 'circle-check', 'circle-alert', 'triangle-alert', 'user-plus',
  'bot', 'webhook', 'plug', 'cloud-upload', 'mouse-pointer-click', 'calendar-clock', 'badge-dollar-sign',
  'id-card', 'panel-left', 'move-vertical', 'text-quote', 'scan-line', 'blocks', 'activity', 'smartphone-nfc',
];

const errors = [];
const icons = {};

for (const [slug, label, kw, color] of BRANDS) {
  const f = join(siDir, `${slug}.svg`);
  if (!existsSync(f)) { errors.push(`simple-icons 缺少 ${slug}`); continue; }
  const svg = readFileSync(f, 'utf8');
  const d = svg.match(/<path d="([^"]+)"/);
  if (!d) { errors.push(`無法解析 ${slug}`); continue; }
  let hex = color;
  if (!hex) {
    const data = JSON.parse(readFileSync(join(root, 'node_modules/simple-icons/data/simple-icons.json'), 'utf8'));
    const list = Array.isArray(data) ? data : data.icons;
    const found = list.find((i) => i.slug === slug);
    hex = found ? `#${found.hex}` : '#111111';
  }
  icons[slug] = [label, 'brand', hex, 'f', `<path d="${d[1]}"/>`, kw];
}

function lucideInner(name) {
  const f = join(luDir, `${name}.svg`);
  if (!existsSync(f)) { errors.push(`lucide 缺少 ${name}`); return null; }
  const svg = readFileSync(f, 'utf8').replace(/<!--[\s\S]*?-->/g, '');
  const inner = svg.replace(/^[\s\S]*?<svg[^>]*>/, '').replace(/<\/svg>[\s\S]*$/, '');
  return inner.replace(/\s*\n\s*/g, '').replace(/\s+\/>/g, '/>').trim();
}

for (const [name, label, kw] of GENERAL) {
  const inner = lucideInner(name);
  if (inner) icons[name] = [label, 'link', '', 's', inner, kw];
}
for (const name of UI) {
  if (icons[name]) continue;
  const inner = lucideInner(name);
  if (inner) icons[name] = [name, 'ui', '', 's', inner, ''];
}

if (errors.length) {
  console.error(errors.join('\n'));
  process.exit(1);
}

const phpStr = (s) => `'${String(s).replace(/\\/g, '\\\\').replace(/'/g, "\\'")}'`;
let php = "<?php\n// 自動產生：npm run icons（tools/build-icons.mjs），請勿手動修改。\n";
php += '// 格式：[名稱, 分類(brand|link|ui), 品牌色, 繪製方式(f=填色,s=線條), SVG 內容, 搜尋關鍵字]\n';
php += '// 品牌圖示：Simple Icons (CC0 1.0)；一般圖示：Lucide (ISC License)\n';
php += 'return [\n';
for (const [k, v] of Object.entries(icons)) {
  php += `    ${phpStr(k)} => [${v.map(phpStr).join(', ')}],\n`;
}
php += '];\n';
writeFileSync(join(root, 'app/Data/icons.php'), php);

const js = '// 自動產生：npm run icons（tools/build-icons.mjs），請勿手動修改。\n'
  + '// 品牌圖示：Simple Icons (CC0 1.0)；一般圖示：Lucide (ISC License)\n'
  + `export const ICONS = ${JSON.stringify(icons)};\n`;
writeFileSync(join(root, 'public/assets/js/admin/icons.js'), js);

console.log(`已產生 ${Object.keys(icons).length} 個圖示`);
