// 依欄位定義產生編輯表單（官網區塊、連結頁、主題設定共用），以及拖曳排序、媒體庫、圖示挑選器
import { ICONS } from './icons.js';
import { $$, api, el, escapeHtml, icon, mediaUrl, modal, moveItem, toast } from './core.js';

/* ---------------------------------------------------------------------------
 * 拖曳排序（滑鼠與觸控皆可）
 * ------------------------------------------------------------------------- */
export function makeSortable(container, { handle = '.ed-grip', item, onEnd }) {
  if (container.dataset.sortable) return;
  container.dataset.sortable = '1';
  const items = () => Array.from(container.children).filter((c) => c.matches(item));
  container.addEventListener('pointerdown', (e) => {
    const grip = e.target.closest(handle);
    if (!grip || e.button > 0) return;
    const node = grip.closest(item);
    if (!node || node.parentElement !== container) return;
    e.preventDefault();
    e.stopPropagation();
    const from = items().indexOf(node);
    const rect = node.getBoundingClientRect();
    const ghost = node.cloneNode(true);
    ghost.classList.add('ed-ghost');
    Object.assign(ghost.style, { left: rect.left + 'px', top: rect.top + 'px', width: rect.width + 'px', margin: '0' });
    document.body.append(ghost);
    node.classList.add('dragging');
    const offsetY = e.clientY - rect.top;
    const scroller = container.closest('.ed-panel-body, .modal-body') || document.scrollingElement;
    grip.setPointerCapture?.(e.pointerId);

    const move = (ev) => {
      ghost.style.top = ev.clientY - offsetY + 'px';
      const siblings = items().filter((x) => x !== node);
      const before = siblings.find((s) => { const r = s.getBoundingClientRect(); return ev.clientY < r.top + r.height / 2; });
      if (before) {
        if (node.nextElementSibling !== before) container.insertBefore(node, before);
      } else if (siblings.length) {
        siblings[siblings.length - 1].after(node);
      }
      const sr = scroller.getBoundingClientRect ? scroller.getBoundingClientRect() : { top: 0, bottom: innerHeight };
      if (ev.clientY < sr.top + 48) scroller.scrollTop -= 14;
      else if (ev.clientY > sr.bottom - 48) scroller.scrollTop += 14;
    };
    const up = () => {
      grip.removeEventListener('pointermove', move);
      grip.removeEventListener('pointerup', up);
      grip.removeEventListener('pointercancel', up);
      ghost.remove();
      node.classList.remove('dragging');
      const to = items().indexOf(node);
      if (to !== from && to >= 0) onEnd(from, to);
    };
    grip.addEventListener('pointermove', move);
    grip.addEventListener('pointerup', up);
    grip.addEventListener('pointercancel', up);
  });
}

/* ---------------------------------------------------------------------------
 * 欄位預設值（與 PHP Fields::defaults 相同規則）
 * ------------------------------------------------------------------------- */
export function fieldDefault(f) {
  if (f.default !== undefined) return JSON.parse(JSON.stringify(f.default));
  switch (f.type) {
    case 'toggle': return false;
    case 'number': case 'range': return f.min ?? 0;
    case 'select': return Object.keys(f.options)[0];
    case 'list': return [];
    default: return '';
  }
}

export function defaults(fields) {
  const out = {};
  for (const f of fields) out[f.key] = fieldDefault(f);
  return out;
}

/* ---------------------------------------------------------------------------
 * 表單產生器
 *   onChange(key, value, { coalesce })：欄位變更（資料物件已直接更新）
 *   ctx.pages：可連結的頁面清單（官網編輯器用）
 * ------------------------------------------------------------------------- */
export function buildFields(fields, data, { onChange, ctx = {}, exclude = [] } = {}) {
  const form = el('div', { class: 'ed-form' });
  const rows = [];
  const refresh = () => {
    for (const { f, row } of rows) {
      if (!f.showIf) continue;
      const [key, val] = f.showIf;
      row.hidden = String(data[key]) !== String(val);
    }
  };
  for (const f of fields) {
    if (exclude.includes(f.key)) continue;
    if (data[f.key] === undefined) data[f.key] = fieldDefault(f);
    const set = (value, opts = {}) => {
      data[f.key] = value;
      const coalesce = opts.coalesce === true ? f.key : (opts.coalesce || null);
      onChange?.(f.key, value, { ...opts, coalesce });
      refresh();
    };
    const row = fieldRow(f, data[f.key], set, ctx);
    rows.push({ f, row });
    form.append(row);
  }
  refresh();
  return form;
}

function fieldRow(f, value, set, ctx) {
  const inline = f.type === 'toggle';
  const row = el('div', { class: 'fld' + (inline ? ' fld-inline' : ''), dataset: { key: f.key } });
  const control = fieldControl(f, value, set, ctx);
  if (inline) {
    row.append(el('span', { class: 'fld-label' }, f.label), control);
  } else if (f.label) {
    row.append(el('label', { class: 'fld-label' }, f.label), control);
  } else {
    row.append(control);
  }
  if (f.help) row.append(el('div', { class: 'fld-help', text: f.help }));
  return row;
}

export function fieldControl(f, value, set, ctx = {}) {
  switch (f.type) {
    case 'text':
      return textInput(value, set, f);
    case 'textarea':
      return textArea(value, set, f);
    case 'code': {
      const ta = textArea(value, set, f);
      ta.classList.add('mono');
      ta.rows = 8;
      ta.spellcheck = false;
      return ta;
    }
    case 'richtext':
      return richText(value, set, f);
    case 'url':
      return urlField(value, set, f, ctx);
    case 'image':
      return imageField(value, set);
    case 'color':
      return colorField(value, set, f);
    case 'select':
      return selectField(value, set, f);
    case 'toggle': {
      const input = el('input', { type: 'checkbox', class: 'switch', 'aria-label': f.label });
      input.checked = !!value;
      input.addEventListener('change', () => set(input.checked));
      return input;
    }
    case 'number': {
      const input = el('input', { type: 'number', value: value ?? '', min: f.min, max: f.max, step: f.step || 1 });
      input.addEventListener('input', () => set(input.value === '' ? 0 : Number(input.value), { coalesce: true }));
      return input;
    }
    case 'range':
      return rangeField(value, set, f);
    case 'icon':
      return iconField(value, set, f);
    case 'datetime': {
      const input = el('input', { type: 'datetime-local', value: value || '' });
      input.addEventListener('change', () => set(input.value));
      return input;
    }
    case 'list':
      return listField(f, value, set, ctx);
    default:
      return el('div', { class: 'fld-help', text: `（不支援的欄位：${f.type}）` });
  }
}

function textInput(value, set, f) {
  const input = el('input', { type: 'text', value: value ?? '', placeholder: f.placeholder || '', maxlength: f.maxlength || null });
  input.addEventListener('input', () => set(input.value, { coalesce: true }));
  return input;
}

function textArea(value, set, f) {
  const ta = el('textarea', { rows: f.rows || 3, placeholder: f.placeholder || '', maxlength: f.maxlength || null });
  ta.value = value ?? '';
  ta.addEventListener('input', () => set(ta.value, { coalesce: true }));
  return ta;
}

function selectField(value, set, f) {
  const entries = Object.entries(f.options);
  const short = entries.length <= 4 && entries.every(([, label]) => String(label).length <= 6);
  if (short) {
    const seg = el('div', { class: 'fld-seg', role: 'radiogroup' });
    for (const [key, label] of entries) {
      const btn = el('button', { type: 'button', class: String(value) === key ? 'active' : '', role: 'radio', 'aria-checked': String(String(value) === key) }, label);
      btn.addEventListener('click', () => {
        $$('button', seg).forEach((b) => { b.classList.remove('active'); b.setAttribute('aria-checked', 'false'); });
        btn.classList.add('active');
        btn.setAttribute('aria-checked', 'true');
        set(key);
      });
      seg.append(btn);
    }
    return seg;
  }
  const select = el('select');
  for (const [key, label] of entries) select.append(el('option', { value: key, selected: String(value) === key }, label));
  select.addEventListener('change', () => set(select.value));
  return select;
}

function rangeField(value, set, f) {
  const out = el('output', { text: `${value}${f.unit || ''}` });
  const input = el('input', { type: 'range', min: f.min ?? 0, max: f.max ?? 100, step: f.step ?? 1, value: value ?? f.min ?? 0 });
  input.addEventListener('input', () => {
    out.textContent = `${input.value}${f.unit || ''}`;
    set(Number(input.value), { coalesce: true });
  });
  return el('div', { class: 'fld-range' }, input, out);
}

function colorField(value, set, f) {
  const wrap = el('div', { class: 'fld-color' });
  const picker = el('input', { type: 'color', value: /^#[0-9a-f]{6}$/i.test(value || '') ? value : (f.default || '#000000') });
  const text = el('input', { type: 'text', value: value || '', placeholder: f.default || '#000000', maxlength: 7 });
  picker.addEventListener('input', () => { text.value = picker.value; set(picker.value, { coalesce: true }); });
  text.addEventListener('change', () => {
    let v = text.value.trim().toLowerCase();
    if (v && !v.startsWith('#')) v = '#' + v;
    if (/^#[0-9a-f]{3}$/.test(v)) v = '#' + v.slice(1).split('').map((c) => c + c).join('');
    if (!/^#[0-9a-f]{6}$/.test(v)) { text.value = value || ''; return; }
    text.value = v;
    picker.value = v;
    set(v);
  });
  wrap.append(picker, text);
  return wrap;
}

/* 連結欄位：外部網址、#錨點，或官網內頁（page:ID） */
function urlField(value, set, f, ctx) {
  const wrap = el('div', { class: 'fld-url' });
  const render = () => {
    wrap.innerHTML = '';
    const pageLink = /^page:([A-Za-z0-9_-]+)(#.*)?$/.exec(value || '');
    const page = pageLink && (ctx.pages || []).find((p) => p.id === pageLink[1]);
    if (page) {
      wrap.append(
        el('div', { class: 'page-chip', html: icon('file') }, el('span', { text: page.title + (pageLink[2] || '') })),
        el('button', { type: 'button', class: 'btn btn-icon', title: '改成網址', html: icon('x'), onclick: () => { value = ''; set(''); render(); } }),
      );
      return;
    }
    const input = el('input', { type: 'text', value: value || '', placeholder: f.placeholder || 'https:// 或 #錨點' });
    input.addEventListener('input', () => { value = input.value.trim(); set(value, { coalesce: true }); });
    wrap.append(input);
    if (ctx.pages?.length) {
      const select = el('select', { style: { width: '44px', flex: 'none', paddingRight: '0' }, title: '連結到網站頁面' }, el('option', { value: '' }, '📄'));
      for (const p of ctx.pages) select.append(el('option', { value: p.id }, p.title));
      select.addEventListener('change', () => {
        if (!select.value) return;
        value = 'page:' + select.value;
        set(value);
        render();
      });
      wrap.append(select);
    }
  };
  render();
  return wrap;
}

/* 圖片欄位（開啟媒體庫） */
export function imageField(value, set) {
  const wrap = el('div', { class: 'img-field' });
  const render = () => {
    wrap.innerHTML = '';
    const src = mediaUrl(value);
    wrap.append(src ? el('img', { class: 'img-prev', src, alt: '' }) : el('span', { class: 'img-empty', html: icon('image') }));
    const actions = el('div', { class: 'img-actions' },
      el('button', { type: 'button', class: 'btn btn-sm', html: icon('images') + (src ? '更換' : '選擇圖片'), onclick: async () => {
        const url = await openMediaPicker();
        if (url) { value = url; set(url); render(); }
      } }),
    );
    if (src) actions.append(el('button', { type: 'button', class: 'btn btn-sm btn-danger-ghost', html: icon('trash-2'), title: '移除圖片', onclick: () => { value = ''; set(''); render(); } }));
    wrap.append(actions);
  };
  render();
  return wrap;
}

/* 圖示欄位 */
function iconField(value, set, f) {
  const btn = el('button', { type: 'button', class: 'icon-btn' });
  const render = () => {
    const label = value ? (value.startsWith('emoji:') ? '表情符號' : value.startsWith('img:') ? '自訂圖片' : (ICONS[value]?.[0] || value)) : '選擇圖示';
    btn.innerHTML = `<span class="icon-prev">${value ? icon(value) : icon('plus')}</span><span>${escapeHtml(label)}</span>`;
  };
  btn.addEventListener('click', async () => {
    const picked = await openIconPicker({ value, groups: f.groups });
    if (picked !== null) { value = picked; set(picked); render(); }
  });
  render();
  return el('div', { class: 'fld-icon' }, btn);
}

/* 富文字編輯器（粗體、標題、清單、連結） */
function richText(value, set, f) {
  const area = el('div', { class: 'rt-area', contenteditable: 'true', 'data-placeholder': f.placeholder || '輸入內文…' });
  area.innerHTML = value || '';
  const exec = (cmd, arg = null) => { area.focus(); document.execCommand(cmd, false, arg); emit(); };
  const block = (tag) => {
    const current = String(document.queryCommandValue('formatBlock') || '').toLowerCase().replace(/[<>]/g, '');
    exec('formatBlock', current === tag ? 'p' : tag);
  };
  const emit = () => set(area.innerHTML, { coalesce: true });
  const tools = [
    ['bold', '粗體', () => exec('bold')],
    ['italic', '斜體', () => exec('italic')],
    ['underline', '底線', () => exec('underline')],
    ['heading-2', '標題', () => block('h2')],
    ['heading-3', '小標題', () => block('h3')],
    ['list', '項目清單', () => exec('insertUnorderedList')],
    ['list-ordered', '編號清單', () => exec('insertOrderedList')],
    ['quote', '引言', () => block('blockquote')],
    ['link-2', '加入連結', () => {
      const url = window.prompt('連結網址（https://…）');
      if (url) exec('createLink', url.trim());
    }],
    ['remove-formatting', '清除格式', () => { exec('removeFormat'); exec('unlink'); block('p'); }],
  ];
  const bar = el('div', { class: 'rt-bar' }, tools.map(([ic, label, fn]) => {
    const b = el('button', { type: 'button', title: label, 'aria-label': label, html: icon(ic) });
    b.addEventListener('mousedown', (e) => e.preventDefault());
    b.addEventListener('click', fn);
    return b;
  }));
  area.addEventListener('focus', () => document.execCommand('defaultParagraphSeparator', false, 'p'));
  area.addEventListener('input', emit);
  area.addEventListener('paste', (e) => {
    e.preventDefault();
    const text = (e.clipboardData || window.clipboardData).getData('text/plain');
    document.execCommand('insertText', false, text);
  });
  return el('div', { class: 'rt' }, bar, area);
}

/* 清單欄位（按鈕、特色項目、相簿照片…） */
function listField(f, items, set, ctx) {
  if (!Array.isArray(items)) items = [];
  const wrap = el('div', { class: 'fld-list' });
  const open = new Set();
  const title = (item, i) => {
    const raw = f.itemLabel ? item[f.itemLabel] : '';
    return (raw && String(raw).trim()) || `${f.label} ${i + 1}`;
  };
  const commit = (opts = {}) => set(items, opts);
  const render = () => {
    wrap.innerHTML = '';
    items.forEach((item, i) => {
      const strong = el('strong', { text: title(item, i) });
      const card = el('div', { class: 'fld-item' + (open.has(i) ? ' open' : '') });
      const head = el('div', { class: 'fld-item-head' },
        el('button', { type: 'button', class: 'ed-grip', title: '拖曳排序', html: icon('grip-vertical') }),
        strong,
        el('button', { type: 'button', class: 'ed-mini', title: '複製', html: icon('copy'), onclick: (e) => { e.stopPropagation(); if (f.max && items.length >= f.max) { toast(`最多 ${f.max} 個`, 'err'); return; } items.splice(i + 1, 0, JSON.parse(JSON.stringify(item))); commit(); render(); } }),
        el('button', { type: 'button', class: 'ed-mini', title: '刪除', html: icon('trash-2'), onclick: (e) => { e.stopPropagation(); items.splice(i, 1); open.delete(i); commit(); render(); } }),
        el('span', { class: 'chev', html: icon('chevron-right') }),
      );
      head.addEventListener('click', (e) => {
        if (e.target.closest('button')) return;
        card.classList.toggle('open');
        if (card.classList.contains('open')) open.add(i); else open.delete(i);
      });
      const body = el('div', { class: 'fld-item-body' });
      body.append(buildFields(f.fields, item, {
        ctx,
        onChange: (key, value, opts) => {
          if (key === f.itemLabel) strong.textContent = title(item, i);
          commit({ coalesce: opts.coalesce ? `${f.key}.${i}.${key}` : null, raw: true });
        },
      }));
      card.append(head, body);
      wrap.append(card);
    });
    if (!f.max || items.length < f.max) {
      wrap.append(el('button', { type: 'button', class: 'btn btn-sm fld-list-add', html: icon('plus') + '新增' + (f.addLabel || ''), onclick: () => {
        items.push(defaults(f.fields));
        open.add(items.length - 1);
        commit();
        render();
      } }));
    }
  };
  makeSortable(wrap, { item: '.fld-item', onEnd: (from, to) => { moveItem(items, from, to); open.clear(); commit(); render(); } });
  render();
  return wrap;
}

/* ---------------------------------------------------------------------------
 * 媒體庫挑選器
 * ------------------------------------------------------------------------- */
export async function uploadFiles(files, { onDone, onError, onProgress } = {}) {
  for (const file of files) {
    onProgress?.(file);
    const form = new FormData();
    form.append('file', file);
    try {
      const res = await api('/admin/api/media/upload', { form });
      onDone?.(res.item, file);
    } catch (e) {
      onError?.(e, file);
      toast(`${file.name}：${e.message}`, 'err', 5000);
    }
  }
}

export function openMediaPicker() {
  return new Promise((resolve) => {
    let page = 1;
    let done = false;
    const grid = el('div', { class: 'media-grid' });
    const more = el('button', { type: 'button', class: 'btn', hidden: true }, '載入更多');
    const status = el('div', { class: 'upload-queue' });
    const fileInput = el('input', { type: 'file', accept: 'image/jpeg,image/png,image/gif,image/webp', multiple: true, hidden: true });
    const drop = el('label', { class: 'dropzone', html: icon('cloud-upload') + '<strong>上傳新圖片</strong><small>拖曳到這裡或點擊選擇（大圖會自動縮小）</small>' }, fileInput);
    const urlInput = el('input', { type: 'url', placeholder: '或貼上圖片網址 https://…' });
    const finish = (url) => { if (done) return; done = true; m.close(url); };
    const card = (item) => {
      const fig = el('figure', { class: 'media-item is-selectable', title: item.name, tabindex: '0' },
        el('img', { class: 'thumb', src: mediaUrl(item.url), alt: item.alt || '', loading: 'lazy' }),
        el('figcaption', { text: `${item.width}×${item.height}` }),
      );
      fig.addEventListener('click', () => finish(item.url));
      fig.addEventListener('keydown', (e) => { if (e.key === 'Enter') finish(item.url); });
      return fig;
    };
    const load = async () => {
      try {
        const res = await api('/admin/api/media?page=' + page, { method: 'GET' });
        res.items.forEach((it) => grid.append(card(it)));
        more.hidden = !res.hasMore;
        if (page === 1 && !res.items.length) grid.append(el('p', { class: 'muted', text: '媒體庫還沒有圖片，先上傳一張吧。' }));
      } catch (e) {
        toast(e.message, 'err');
      }
    };
    const upload = (files) => uploadFiles(files, {
      onProgress: (file) => { status.innerHTML = `<div class="uq"><span class="spinner"></span>上傳中：${escapeHtml(file.name)}</div>`; },
      onDone: (item) => { status.innerHTML = ''; grid.querySelector('p.muted')?.remove(); grid.prepend(card(item)); if (files.length === 1) finish(item.url); },
      onError: () => { status.innerHTML = ''; },
    });
    fileInput.addEventListener('change', () => { upload(Array.from(fileInput.files)); fileInput.value = ''; });
    drop.addEventListener('dragover', (e) => { e.preventDefault(); drop.classList.add('is-over'); });
    drop.addEventListener('dragleave', () => drop.classList.remove('is-over'));
    drop.addEventListener('drop', (e) => { e.preventDefault(); drop.classList.remove('is-over'); upload(Array.from(e.dataTransfer.files)); });
    more.addEventListener('click', () => { page++; load(); });
    const body = el('div', {}, drop, status, grid, el('div', { class: 'center', style: { marginTop: '12px' } }, more),
      el('div', { class: 'fld-url', style: { marginTop: '14px' } }, urlInput, el('button', { type: 'button', class: 'btn', onclick: () => {
        const v = urlInput.value.trim();
        if (/^https:\/\//i.test(v)) finish(v); else toast('請輸入 https:// 開頭的圖片網址', 'err');
      } }, '使用網址')));
    const m = modal({ title: '選擇圖片', body, wide: true, onClose: (r) => resolve(r || null) });
    load();
  });
}

/* ---------------------------------------------------------------------------
 * 圖示挑選器：社群品牌／一般圖示／表情符號／自訂圖片
 * ------------------------------------------------------------------------- */
const EMOJIS = ['🌙', '✨', '⭐', '🌟', '☀️', '🌸', '🌿', '☕', '🍰', '🎁', '🎉', '💌', '📍', '📅', '🛍️', '💎', '❤️', '🔥', '📣', '🎵', '📷', '🎬', '📚', '✈️', '🏠', '👋', '💬', '🎓', '💡', '🧸'];

export function openIconPicker({ value = '', groups } = {}) {
  const allowed = groups || ['brand', 'link'];
  return new Promise((resolve) => {
    let tab = allowed[0];
    const search = el('input', { type: 'search', placeholder: '搜尋圖示，例如 IG、地圖、咖啡' });
    const tabs = el('div', { class: 'icon-tabs' });
    const grid = el('div', { class: 'icon-grid' });
    const extra = el('div');
    const tabDefs = [['brand', '社群品牌'], ['link', '一般圖示'], ['emoji', '表情符號'], ['image', '自訂圖片']]
      .filter(([k]) => allowed.includes(k) || k === 'emoji' || k === 'image');
    const renderTabs = () => {
      tabs.innerHTML = '';
      for (const [k, label] of tabDefs) {
        tabs.append(el('button', { type: 'button', class: tab === k ? 'active' : '', onclick: () => { tab = k; renderTabs(); renderGrid(); } }, label));
      }
    };
    const renderGrid = () => {
      grid.innerHTML = '';
      extra.innerHTML = '';
      search.hidden = !['brand', 'link'].includes(tab);
      if (tab === 'emoji') {
        const custom = el('input', { type: 'text', placeholder: '或輸入任何表情符號', maxlength: 8, style: { maxWidth: '220px' } });
        extra.append(el('div', { class: 'emoji-row' }, EMOJIS.map((em) => el('button', { type: 'button', onclick: () => m.close('emoji:' + em) }, em))),
          el('div', { class: 'fld-url', style: { marginTop: '12px' } }, custom, el('button', { type: 'button', class: 'btn', onclick: () => { if (custom.value.trim()) m.close('emoji:' + custom.value.trim()); } }, '使用')));
        return;
      }
      if (tab === 'image') {
        extra.append(el('p', { class: 'muted', text: '上傳自己的小圖示（建議正方形透明背景 PNG）。' }),
          el('button', { type: 'button', class: 'btn btn-primary', html: icon('images') + '從媒體庫選擇', onclick: async () => {
            const url = await openMediaPicker();
            if (url) m.close('img:' + url);
          } }));
        return;
      }
      const q = search.value.trim().toLowerCase();
      for (const [key, data] of Object.entries(ICONS)) {
        if (data[1] !== tab) continue;
        const hay = `${key} ${data[0]} ${data[5]}`.toLowerCase();
        if (q && !hay.includes(q)) continue;
        const btn = el('button', { type: 'button', class: value === key ? 'is-selected' : '', title: data[0], html: icon(key) }, el('span', { text: data[0] }));
        if (data[1] === 'brand' && data[2]) btn.style.color = data[2];
        btn.addEventListener('click', () => m.close(key));
        grid.append(btn);
      }
      if (!grid.children.length) grid.append(el('p', { class: 'muted', text: '找不到符合的圖示' }));
    };
    search.addEventListener('input', renderGrid);
    const m = modal({
      title: '選擇圖示',
      wide: true,
      body: el('div', {}, tabs, search, el('div', { style: { height: '10px' } }), grid, extra),
      onClose: (r) => resolve(r === undefined ? null : r),
      actions: [{ label: '不使用圖示', value: '' }, { label: '取消', value: null }],
    });
    renderTabs();
    renderGrid();
    setTimeout(() => search.focus(), 50);
  });
}
