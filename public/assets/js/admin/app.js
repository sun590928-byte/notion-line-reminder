// 後台一般頁面：表單送出、首頁模式、統計圖、媒體庫、會員、表單訊息、LINE 工具
import { $, $$, api, confirmDialog, copyText, el, escapeHtml, icon, mediaUrl, toast } from './core.js';
import { imageField, openMediaPicker, uploadFiles } from './fields.js';

/* ---------- 側欄（手機） ---------- */
document.addEventListener('click', (e) => {
  if (e.target.closest('[data-open-side]')) document.body.classList.add('side-open');
  if (e.target.closest('[data-close-side]')) document.body.classList.remove('side-open');
});

/* ---------- 複製按鈕 ---------- */
document.addEventListener('click', (e) => {
  const btn = e.target.closest('[data-copy]');
  if (btn) copyText(btn.dataset.copy);
});

/* ---------- 可點擊的表格列 ---------- */
document.addEventListener('click', (e) => {
  const row = e.target.closest('tr.row-link');
  if (row && !e.target.closest('a, button, input')) location.href = row.dataset.href;
});

/* ---------- 圖片欄位（設定頁、LINE 傳送） ---------- */
$$('[data-media-input]').forEach((box) => {
  const hidden = el('input', { type: 'hidden', name: box.dataset.name, value: box.dataset.value || '' });
  box.append(hidden, imageField(box.dataset.value || '', (v) => { hidden.value = v; }));
  box.closest('form')?.addEventListener('reset', () => {
    hidden.value = '';
    box.querySelector('.img-field')?.replaceWith(imageField('', (v) => { hidden.value = v; }));
  });
});

/* ---------- 以 API 送出的表單：<form data-api="..."> ---------- */
function formData(form) {
  const data = {};
  for (const input of form.elements) {
    if (!input.name || input.disabled) continue;
    if (input.type === 'checkbox') {
      if (input.dataset.on !== undefined) data[input.name] = input.checked ? input.dataset.on : input.dataset.off;
      else data[input.name] = input.checked;
    } else if (input.type === 'radio') {
      if (input.checked) data[input.name] = input.value;
    } else {
      data[input.name] = input.value;
    }
  }
  return data;
}

$$('form[data-api]').forEach((form) => {
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!form.reportValidity()) return;
    if (form.dataset.confirm && !await confirmDialog(form.dataset.confirm, { okLabel: '確定送出' })) return;
    const btn = form.querySelector('button[type=submit]');
    btn?.classList.add('is-loading');
    try {
      const res = await api(form.dataset.api, { data: formData(form) });
      toast(res.message || '已儲存');
      if (form.hasAttribute('data-reset')) form.reset();
      if (form.hasAttribute('data-reload')) setTimeout(() => location.reload(), 700);
    } catch (err) {
      toast(err.message, 'err', 5000);
    } finally {
      btn?.classList.remove('is-loading');
    }
  });
});

/* ---------- 頁籤（網站設定） ---------- */
$$('[data-tabs]').forEach((nav) => {
  const show = (id) => {
    const target = document.getElementById(id);
    if (!target) return;
    $$('a', nav).forEach((a) => a.classList.toggle('active', a.getAttribute('href') === '#' + id));
    $$('.tab-panel').forEach((p) => { p.hidden = p !== target; });
  };
  nav.addEventListener('click', (e) => {
    const a = e.target.closest('a[href^="#"]');
    if (!a) return;
    e.preventDefault();
    history.replaceState(null, '', a.getAttribute('href'));
    show(a.getAttribute('href').slice(1));
  });
  if (location.hash) show(location.hash.slice(1));
});

/* ---------- 首頁模式切換 ---------- */
$$('[data-mode-switch]').forEach((box) => {
  box.addEventListener('change', async (e) => {
    const radio = e.target.closest('input[name=mode]');
    if (!radio) return;
    const previous = box.dataset.current || $$('input[name=mode]', box).find((r) => r.defaultChecked)?.value;
    box.classList.add('is-busy');
    try {
      const res = await api('/admin/api/mode', { data: { mode: radio.value } });
      box.dataset.current = res.mode;
      toast({ links: '首頁已切換為「連結頁」，官網隱藏中', website: '首頁已切換為「官方網站」，官網已公開', maintenance: '已切換為「即將推出」頁面' }[res.mode]);
      setTimeout(() => location.reload(), 900);
    } catch (err) {
      toast(err.message, 'err', 6000);
      const prev = $$('input[name=mode]', box).find((r) => r.value === previous);
      if (prev) prev.checked = true;
    } finally {
      box.classList.remove('is-busy');
    }
  });
});

/* ---------- 每日長條圖（單一數列，滑鼠移上顯示數值） ---------- */
function niceMax(v) {
  if (v <= 5) return 5;
  const pow = 10 ** Math.floor(Math.log10(v));
  const n = v / pow;
  return (n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10) * pow;
}

function drawChart(box) {
  const data = JSON.parse(box.dataset.chart || '[]');
  const unit = box.dataset.unit || '';
  const width = box.clientWidth;
  const height = box.clientHeight;
  if (!width) return;
  box.innerHTML = '';
  const total = data.reduce((s, d) => s + d.hits, 0);
  const max = niceMax(Math.max(1, ...data.map((d) => d.hits)));
  const pad = { l: 34, r: 6, t: 18, b: 24 };
  const w = width - pad.l - pad.r;
  const h = height - pad.t - pad.b;
  const slot = w / data.length;
  const barW = Math.max(2, Math.min(24, slot - 2));
  const ns = 'http://www.w3.org/2000/svg';
  const svg = document.createElementNS(ns, 'svg');
  svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
  const add = (tag, attrs, parent = svg) => {
    const node = document.createElementNS(ns, tag);
    for (const [k, v] of Object.entries(attrs)) node.setAttribute(k, v);
    parent.append(node);
    return node;
  };
  const steps = Number.isInteger(max / 2) ? 2 : 1;
  for (let i = 0; i <= steps; i++) {
    const v = (max / steps) * i;
    const y = pad.t + h - (v / max) * h;
    add('line', { class: i === 0 ? 'base-line' : 'grid-line', x1: pad.l, x2: width - pad.r, y1: y, y2: y });
    add('text', { class: 'tick', x: pad.l - 8, y: y + 4, 'text-anchor': 'end' }).textContent = v.toLocaleString('en-US');
  }
  const tip = el('div', { class: 'chart-tip', hidden: true });
  const peakIndex = data.reduce((best, d, i) => (d.hits > data[best].hits ? i : best), 0);
  data.forEach((d, i) => {
    const x = pad.l + i * slot + (slot - barW) / 2;
    const bh = (d.hits / max) * h;
    const y = pad.t + h - bh;
    const label = `${d.day.slice(5).replace('-', '/')}`;
    const hit = add('rect', { class: 'hit', x: pad.l + i * slot, y: pad.t, width: slot, height: h, tabindex: '0', 'aria-label': `${label}：${d.hits} 次${unit}` });
    if (bh > 0) {
      const r = Math.min(4, barW / 2, bh);
      add('path', { class: 'bar', d: `M${x},${y + bh} V${y + r} Q${x},${y} ${x + r},${y} H${x + barW - r} Q${x + barW},${y} ${x + barW},${y + r} V${y + bh} Z` });
    } else {
      add('rect', { class: 'bar', x, y: pad.t + h - 1, width: barW, height: 0 });
    }
    const show = () => {
      tip.hidden = false;
      tip.innerHTML = '';
      tip.append(el('strong', { text: `${d.hits.toLocaleString('en-US')} 次` }), el('span', { text: `${label} ${unit}` }));
      tip.style.left = `${x + barW / 2}px`;
      tip.style.top = `${Math.max(pad.t, y)}px`;
      hit.nextSibling?.classList.add('is-hover');
    };
    const hide = () => { tip.hidden = true; hit.nextSibling?.classList.remove('is-hover'); };
    hit.addEventListener('pointerenter', show);
    hit.addEventListener('pointerleave', hide);
    hit.addEventListener('focus', show);
    hit.addEventListener('blur', hide);
    if (i % 7 === (data.length - 1) % 7) {
      add('text', { class: 'tick', x: x + barW / 2, y: height - 6, 'text-anchor': 'middle' }).textContent = label;
    }
  });
  if (total > 0 && data[peakIndex].hits > 0) {
    const d = data[peakIndex];
    const x = pad.l + peakIndex * slot + slot / 2;
    const y = pad.t + h - (d.hits / max) * h - 6;
    add('text', { class: 'peak', x, y, 'text-anchor': 'middle' }).textContent = d.hits.toLocaleString('en-US');
  }
  box.append(svg, tip);
  if (total === 0) box.append(el('div', { class: 'chart-empty', text: '近 30 天還沒有資料' }));
}

const charts = $$('.chart[data-chart]');
charts.forEach(drawChart);
if (charts.length) {
  let t;
  window.addEventListener('resize', () => { clearTimeout(t); t = setTimeout(() => charts.forEach(drawChart), 150); });
}

/* ---------- 媒體庫 ---------- */
const mediaGrid = $('[data-media-grid]');
if (mediaGrid) {
  const initial = JSON.parse(mediaGrid.dataset.initial || '{"items":[]}');
  const more = $('[data-media-more]');
  const queue = $('[data-upload-queue]');
  const counter = $('[data-media-count]');
  let page = 1;
  const card = (item) => {
    const fig = el('figure', { class: 'media-item', dataset: { id: item.id } },
      el('img', { class: 'thumb', src: mediaUrl(item.url), alt: item.alt || '', loading: 'lazy' }),
      el('figcaption', { title: item.name, text: `${item.width}×${item.height}・${Math.max(1, Math.round(item.size / 1024))} KB` }),
      el('div', { class: 'media-actions' },
        el('button', { type: 'button', title: '複製網址', 'aria-label': '複製網址', html: icon('copy'), onclick: () => copyText(location.origin + mediaUrl(item.url)) }),
        el('button', { type: 'button', title: '替代文字', 'aria-label': '替代文字', html: icon('type'), onclick: async () => {
          const alt = window.prompt('圖片的替代文字（給看不到圖片的人與搜尋引擎）', item.alt || '');
          if (alt === null) return;
          try { await api(`/admin/api/media/${item.id}/update`, { data: { alt } }); item.alt = alt; toast('已更新'); } catch (e) { toast(e.message, 'err'); }
        } }),
        el('button', { type: 'button', title: '刪除', 'aria-label': '刪除', html: icon('trash-2'), onclick: async () => {
          if (!await confirmDialog('刪除後，網站上使用這張圖片的地方會無法顯示。確定刪除？', { title: '刪除圖片', okLabel: '刪除', danger: true })) return;
          try {
            await api(`/admin/api/media/${item.id}/delete`, { data: {} });
            fig.remove();
            if (counter) counter.textContent = Math.max(0, Number(counter.textContent) - 1);
            toast('已刪除');
          } catch (e) { toast(e.message, 'err'); }
        } }),
      ),
    );
    return fig;
  };
  const render = (items, prepend = false) => items.forEach((it) => (prepend ? mediaGrid.prepend(card(it)) : mediaGrid.append(card(it))));
  render(initial.items);
  more.hidden = !initial.hasMore;
  more.addEventListener('click', async () => {
    page++;
    try {
      const res = await api('/admin/api/media?page=' + page, { method: 'GET' });
      render(res.items);
      more.hidden = !res.hasMore;
    } catch (e) { toast(e.message, 'err'); }
  });
  const drop = $('[data-dropzone]');
  const input = $('input[type=file]', drop);
  const upload = (files) => uploadFiles(files, {
    onProgress: (file) => { queue.innerHTML = `<div class="uq"><span class="spinner"></span>上傳中：${escapeHtml(file.name)}</div>`; },
    onDone: (item) => {
      queue.innerHTML = '';
      render([item], true);
      if (counter) counter.textContent = Number(counter.textContent) + 1;
      toast('已上傳 ' + item.name);
    },
    onError: () => { queue.innerHTML = ''; },
  });
  input.addEventListener('change', () => { upload(Array.from(input.files)); input.value = ''; });
  drop.addEventListener('dragover', (e) => { e.preventDefault(); drop.classList.add('is-over'); });
  drop.addEventListener('dragleave', () => drop.classList.remove('is-over'));
  drop.addEventListener('drop', (e) => { e.preventDefault(); drop.classList.remove('is-over'); upload(Array.from(e.dataTransfer.files)); });
}

/* ---------- 會員 ---------- */
document.addEventListener('click', async (e) => {
  const del = e.target.closest('[data-delete-member]');
  if (!del) return;
  if (!await confirmDialog('會員資料、登入綁定都會刪除（不會刪除 LINE 好友關係）。確定刪除？', { title: '刪除會員', okLabel: '刪除', danger: true })) return;
  try {
    await api(`/admin/api/members/${del.dataset.deleteMember}/delete`, { data: {} });
    toast('已刪除會員');
    setTimeout(() => { location.href = document.querySelector('meta[name="base-url"]').content + '/admin/members'; }, 600);
  } catch (err) { toast(err.message, 'err'); }
});

/* ---------- 表單訊息 ---------- */
document.addEventListener('click', async (e) => {
  const toggle = e.target.closest('[data-toggle-read]');
  const del = e.target.closest('[data-delete-form]');
  if (toggle) {
    const read = toggle.dataset.read !== '1';
    try {
      await api(`/admin/api/forms/${toggle.dataset.toggleRead}/read`, { data: { read } });
      toggle.dataset.read = read ? '1' : '0';
      toggle.textContent = read ? '標為未讀' : '標為已讀';
      toggle.closest('.msg')?.classList.toggle('unread', !read);
    } catch (err) { toast(err.message, 'err'); }
  }
  if (del) {
    if (!await confirmDialog('確定刪除這則留言？', { title: '刪除留言', okLabel: '刪除', danger: true })) return;
    try {
      await api(`/admin/api/forms/${del.dataset.deleteForm}/delete`, { data: {} });
      del.closest('.msg')?.remove();
      toast('已刪除');
    } catch (err) { toast(err.message, 'err'); }
  }
});
// 開啟留言頁時，自動把畫面上的未讀留言標為已讀（停留 2 秒後）
if ($('[data-page="forms"]')) {
  setTimeout(() => {
    $$('.msg.unread').forEach((msg) => {
      const id = msg.dataset.formId;
      api(`/admin/api/forms/${id}/read`, { data: { read: true } }).then(() => {
        msg.classList.remove('unread');
        const btn = $('[data-toggle-read]', msg);
        if (btn) { btn.dataset.read = '1'; btn.textContent = '標為未讀'; }
      }).catch(() => {});
    });
  }, 2000);
}

/* ---------- LINE 整合 ---------- */
const lineTest = $('[data-line-test]');
if (lineTest) {
  lineTest.addEventListener('click', async () => {
    const box = $('[data-bot-info]');
    lineTest.classList.add('is-loading');
    try {
      const res = await api('/admin/api/line/test', { data: {} });
      const q = res.quota;
      box.className = 'bot-info';
      box.innerHTML = '';
      if (res.bot.picture) box.append(el('img', { src: res.bot.picture, alt: '' }));
      box.append(el('div', {},
        el('strong', { text: `連線成功：${res.bot.name}` }),
        el('div', { class: 'small', text: `${res.bot.basicId}${q ? `・本月已傳送 ${q.used ?? '—'} / ${q.type === 'limited' ? q.limit : '無上限'} 則` : ''}` })));
      box.hidden = false;
    } catch (err) {
      box.className = 'bot-info err';
      box.textContent = err.message;
      box.hidden = false;
    } finally {
      lineTest.classList.remove('is-loading');
    }
  });
}
$('[data-bind-code]')?.addEventListener('click', async (e) => {
  try {
    const res = await api('/admin/api/line/bind-code', { data: {} });
    const box = $('[data-bind-box]');
    box.innerHTML = '';
    box.append(el('p', { text: `請用手機 LINE 傳送以下文字給官方帳號（${res.expires} 前有效），綁定後重新整理此頁：` }), el('div', { class: 'bind-code', text: `綁定 ${res.code}` }));
    e.target.closest('button').textContent = '重新產生綁定碼';
  } catch (err) { toast(err.message, 'err'); }
});
$('[data-notify-test]')?.addEventListener('click', async (e) => {
  const btn = e.target.closest('button');
  btn.classList.add('is-loading');
  try { await api('/admin/api/line/notify-test', { data: {} }); toast('已送出測試通知，請查看 LINE'); } catch (err) { toast(err.message, 'err', 6000); } finally { btn.classList.remove('is-loading'); }
});
document.addEventListener('click', async (e) => {
  const rm = e.target.closest('[data-remove-receiver]');
  if (!rm) return;
  if (!await confirmDialog('之後不會再傳送管理通知給這個 LINE 帳號。', { title: '移除通知對象', okLabel: '移除', danger: true })) return;
  try {
    await api(`/admin/api/line/receivers/${encodeURIComponent(rm.dataset.removeReceiver)}/remove`, { data: {} });
    rm.closest('li')?.remove();
    toast('已移除');
  } catch (err) { toast(err.message, 'err'); }
});
$$('textarea[data-count]').forEach((ta) => {
  const out = el('small', { class: 'muted', style: { alignSelf: 'flex-end' } });
  const update = () => { out.textContent = `${ta.value.length} / ${ta.maxLength > 0 ? ta.maxLength : '∞'}`; };
  ta.after(out);
  ta.addEventListener('input', update);
  update();
});

/* ---------- 管理員帳號 ---------- */
document.addEventListener('click', async (e) => {
  const del = e.target.closest('[data-delete-admin]');
  if (!del) return;
  if (!await confirmDialog('確定刪除這位管理員？', { title: '刪除管理員', okLabel: '刪除', danger: true })) return;
  try {
    await api(`/admin/api/account/admins/${del.dataset.deleteAdmin}/delete`, { data: {} });
    del.closest('li')?.remove();
    toast('已刪除');
  } catch (err) { toast(err.message, 'err'); }
});

export { openMediaPicker };
