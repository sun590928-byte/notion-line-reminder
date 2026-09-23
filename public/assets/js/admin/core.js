// 後台共用工具：API 呼叫、提示訊息、對話框、圖示、DOM 建立
import { ICONS } from './icons.js';

const CFG = {
  base: document.querySelector('meta[name="base-url"]')?.content ?? '',
  csrf: document.querySelector('meta[name="csrf-token"]')?.content ?? '',
};

export function configure(values) {
  Object.assign(CFG, values);
}

export const $ = (sel, root = document) => root.querySelector(sel);
export const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

export function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

/** 站內網址（補上子目錄） */
export function siteUrl(path) {
  if (!path) return CFG.base || '/';
  if (/^[a-z][a-z0-9+.-]*:/i.test(path) || path.startsWith('//')) return path;
  if (CFG.base && (path === CFG.base || path.startsWith(CFG.base + '/'))) return path;
  return CFG.base + (path.startsWith('/') ? path : '/' + path);
}

export function mediaUrl(src) {
  if (!src) return '';
  if (/^https?:\/\//i.test(src)) return src;
  if (src.startsWith('/') && !src.startsWith('//')) return siteUrl(src);
  return '';
}

export function iconData(name) {
  return ICONS[name] || null;
}

export function icon(name, cls = '') {
  if (!name) return '';
  if (name.startsWith('emoji:')) return `<span class="ico ico-emoji ${cls}" aria-hidden="true">${escapeHtml(name.slice(6))}</span>`;
  if (name.startsWith('img:')) return `<img class="ico ico-img ${cls}" src="${escapeHtml(mediaUrl(name.slice(4)))}" alt="">`;
  const data = ICONS[name];
  if (!data) return '';
  const inner = data[4];
  return data[3] === 'f'
    ? `<svg class="ico ${cls}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">${inner}</svg>`
    : `<svg class="ico ${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">${inner}</svg>`;
}

/** 建立 DOM：el('button', { class: 'btn', onclick: fn }, '文字') */
export function el(tag, attrs = {}, ...children) {
  const node = document.createElement(tag);
  for (const [key, value] of Object.entries(attrs || {})) {
    if (value === null || value === undefined || value === false) continue;
    if (key === 'class') node.className = value;
    else if (key === 'html') node.innerHTML = value;
    else if (key === 'text') node.textContent = value;
    else if (key === 'dataset') Object.assign(node.dataset, value);
    else if (key === 'style' && typeof value === 'object') Object.assign(node.style, value);
    else if (key.startsWith('on') && typeof value === 'function') node.addEventListener(key.slice(2), value);
    else if (key === 'value' && 'value' in node) node.value = value;
    else if (value === true) node.setAttribute(key, '');
    else node.setAttribute(key, value);
  }
  for (const child of children.flat(Infinity)) {
    if (child === null || child === undefined || child === false) continue;
    node.append(child instanceof Node ? child : document.createTextNode(String(child)));
  }
  return node;
}

export class ApiError extends Error {
  constructor(message, status = 0, data = null) {
    super(message);
    this.status = status;
    this.data = data;
  }
}

/** 呼叫後台 API（自動帶 CSRF） */
export async function api(path, { method = 'POST', data, form } = {}) {
  const headers = { 'X-CSRF-Token': CFG.csrf, Accept: 'application/json' };
  let body;
  if (form instanceof FormData) {
    body = form;
  } else if (data !== undefined && method !== 'GET') {
    headers['Content-Type'] = 'application/json';
    body = JSON.stringify(data);
  }
  let res;
  try {
    res = await fetch(siteUrl(path), { method, headers, body, credentials: 'same-origin' });
  } catch {
    throw new ApiError('網路連線失敗，請檢查網路後再試一次。');
  }
  let json = null;
  try { json = await res.json(); } catch { json = null; }
  if (res.status === 401 && json?.login) {
    toast('登入逾時，即將前往登入頁…', 'err');
    setTimeout(() => { location.href = siteUrl('/admin/login?next=' + encodeURIComponent(location.pathname)); }, 1400);
    throw new ApiError(json.error || '請重新登入', 401, json);
  }
  if (res.status === 419) throw new ApiError('頁面已過期，請重新整理後再試一次。', 419, json);
  if (!res.ok || !json || json.ok === false) {
    throw new ApiError(json?.error || `伺服器錯誤（${res.status}）`, res.status, json);
  }
  return json;
}

export function toast(message, type = 'ok', timeout = 3200) {
  let box = document.getElementById('toasts');
  if (!box) {
    box = el('div', { class: 'toasts', id: 'toasts' });
    document.body.append(box);
  }
  const node = el('div', { class: `toast ${type}`, role: 'status', html: icon(type === 'ok' ? 'circle-check' : 'circle-alert') }, message);
  box.append(node);
  setTimeout(() => {
    node.classList.add('out');
    setTimeout(() => node.remove(), 320);
  }, timeout);
}

/** 對話框 */
export function modal({ title = '', body = '', wide = false, actions = [], onClose } = {}) {
  const backdrop = el('div', { class: 'modal-backdrop' });
  const bodyEl = el('div', { class: 'modal-body' });
  if (typeof body === 'string') bodyEl.innerHTML = body; else if (body) bodyEl.append(body);
  const close = (result) => {
    document.removeEventListener('keydown', onKey);
    backdrop.remove();
    onClose?.(result);
  };
  const onKey = (e) => { if (e.key === 'Escape') close(null); };
  const foot = actions.length ? el('div', { class: 'modal-foot' }, actions.map((a) => {
    const btn = el('button', { class: `btn ${a.class || ''}`, type: 'button', html: (a.icon ? icon(a.icon) : '') + escapeHtml(a.label) });
    btn.addEventListener('click', () => a.onClick ? a.onClick(close, btn) : close(a.value ?? null));
    if (a.autofocus) setTimeout(() => btn.focus(), 30);
    return btn;
  })) : null;
  const dialog = el('div', { class: `modal${wide ? ' wide' : ''}`, role: 'dialog', 'aria-modal': 'true', 'aria-label': title },
    el('div', { class: 'modal-head' }, el('h2', { text: title }), el('button', { class: 'modal-close', type: 'button', 'aria-label': '關閉', html: icon('x'), onclick: () => close(null) })),
    bodyEl,
    foot,
  );
  backdrop.append(dialog);
  backdrop.addEventListener('mousedown', (e) => { if (e.target === backdrop) close(null); });
  document.addEventListener('keydown', onKey);
  document.body.append(backdrop);
  return { el: dialog, body: bodyEl, close };
}

export function confirmDialog(message, { title = '請確認', okLabel = '確定', danger = false } = {}) {
  return new Promise((resolve) => {
    modal({
      title,
      body: el('p', { text: message }),
      onClose: (r) => resolve(r === true),
      actions: [
        { label: '取消', value: false },
        { label: okLabel, class: danger ? 'btn-danger' : 'btn-primary', value: true, autofocus: true },
      ],
    });
  });
}

export function promptDialog(title, { label = '', value = '', placeholder = '', okLabel = '確定' } = {}) {
  return new Promise((resolve) => {
    const input = el('input', { type: 'text', value, placeholder });
    const m = modal({
      title,
      body: el('label', { class: 'field' }, label, input),
      onClose: (r) => resolve(r),
      actions: [
        { label: '取消', value: null },
        { label: okLabel, class: 'btn-primary', onClick: (close) => close(input.value.trim()) },
      ],
    });
    input.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); m.close(input.value.trim()); } });
    setTimeout(() => { input.focus(); input.select(); }, 40);
  });
}

export async function copyText(text) {
  try {
    await navigator.clipboard.writeText(text);
  } catch {
    const ta = el('textarea', { style: { position: 'fixed', opacity: '0' } });
    ta.value = text;
    document.body.append(ta);
    ta.select();
    document.execCommand('copy');
    ta.remove();
  }
  toast('已複製');
}

export function debounce(fn, ms = 300) {
  let t;
  return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}

/** 產生與伺服器相容的 ID（英數、底線，3–40 字） */
export function uid(prefix = 'x') {
  const rnd = crypto.getRandomValues(new Uint8Array(6));
  return prefix + '_' + Array.from(rnd, (b) => b.toString(16).padStart(2, '0')).join('').slice(0, 10);
}

export function clone(value) {
  return value === undefined ? undefined : JSON.parse(JSON.stringify(value));
}

export function getPath(obj, path) {
  return String(path).split('.').reduce((o, k) => (o == null ? undefined : o[k]), obj);
}

export function setPath(obj, path, value) {
  const keys = String(path).split('.');
  const last = keys.pop();
  const target = keys.reduce((o, k) => (o[k] ??= {}), obj);
  target[last] = value;
}

export function moveItem(list, from, to) {
  const [item] = list.splice(from, 1);
  list.splice(to, 0, item);
  return list;
}

export function slugify(text) {
  return String(text || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 60);
}

export function fmtNumber(n) {
  return Number(n || 0).toLocaleString('en-US');
}
