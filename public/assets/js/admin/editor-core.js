// 編輯器核心：草稿自動儲存、復原／重做、發布、雙緩衝即時預覽
import { api, confirmDialog, el, escapeHtml, icon, modal, siteUrl, toast } from './core.js';

export class DocEditor {
  constructor({ name, doc, rev, hasChanges }) {
    this.name = name;
    this.doc = doc;
    this.rev = rev;
    this.hasChanges = !!hasChanges;
    this.snapshot = JSON.stringify(doc);
    this.undoStack = [];
    this.redoStack = [];
    this.lastCoalesce = null;
    this.lastCommitAt = 0;
    this.saving = false;
    this.pending = false;
    this.dirty = false;
    this.edits = 0;
    this.handlers = {};
  }

  on(event, fn) {
    (this.handlers[event] ||= []).push(fn);
    return this;
  }

  emit(event, ...args) {
    (this.handlers[event] || []).forEach((fn) => fn(...args));
  }

  /** 資料已直接修改後呼叫：記錄復原點並排程儲存 */
  commit({ coalesce = null, render = false, delay = 800 } = {}) {
    const json = JSON.stringify(this.doc);
    if (json === this.snapshot) return;
    const now = Date.now();
    const merge = coalesce && coalesce === this.lastCoalesce && now - this.lastCommitAt < 1500;
    if (!merge) {
      this.undoStack.push(this.snapshot);
      if (this.undoStack.length > 100) this.undoStack.shift();
    }
    this.redoStack = [];
    this.snapshot = json;
    this.lastCoalesce = coalesce;
    this.lastCommitAt = now;
    this.edits++;
    this.hasChanges = true;
    this.emit('change', { render });
    this.scheduleSave(delay);
  }

  canUndo() { return this.undoStack.length > 0; }
  canRedo() { return this.redoStack.length > 0; }

  undo() {
    if (!this.undoStack.length) return;
    this.redoStack.push(this.snapshot);
    this.snapshot = this.undoStack.pop();
    this.doc = JSON.parse(this.snapshot);
    this.lastCoalesce = null;
    this.edits++;
    this.emit('replace');
    this.scheduleSave(200);
  }

  redo() {
    if (!this.redoStack.length) return;
    this.undoStack.push(this.snapshot);
    this.snapshot = this.redoStack.pop();
    this.doc = JSON.parse(this.snapshot);
    this.lastCoalesce = null;
    this.edits++;
    this.emit('replace');
    this.scheduleSave(200);
  }

  /** 以伺服器內容取代（還原版本、捨棄變更後） */
  replace(doc, rev, hasChanges) {
    this.doc = doc;
    this.rev = rev;
    this.hasChanges = !!hasChanges;
    this.snapshot = JSON.stringify(doc);
    this.undoStack = [];
    this.redoStack = [];
    this.lastCoalesce = null;
    this.emit('replace');
    this.emit('status', 'saved');
  }

  scheduleSave(delay = 800) {
    this.dirty = true;
    this.emit('status', 'dirty');
    clearTimeout(this.timer);
    this.timer = setTimeout(() => this.save(), delay);
  }

  async save({ force = false } = {}) {
    clearTimeout(this.timer);
    this.timer = null;
    if (this.saving) { this.pending = true; return; }
    this.saving = true;
    this.dirty = false;
    this.emit('status', 'saving');
    const editsAtStart = this.edits;
    try {
      const res = await api(`/admin/api/doc/${this.name}/save`, { data: { doc: this.doc, rev: this.rev, force } });
      this.rev = res.rev;
      this.hasChanges = res.hasChanges;
      this.emit('saved', res, editsAtStart === this.edits);
      this.emit('status', this.dirty ? 'dirty' : 'saved', res.savedAt);
    } catch (e) {
      this.dirty = true;
      if (e.status === 409) {
        this.emit('status', 'error', '內容衝突');
        this.emit('conflict');
      } else {
        this.emit('status', 'error', e.message);
        toast('自動儲存失敗：' + e.message, 'err', 5000);
      }
    } finally {
      this.saving = false;
      if (this.pending) {
        this.pending = false;
        this.save();
      }
    }
  }

  /** 等待所有變更都已儲存 */
  async flush() {
    if (this.dirty) await this.save();
    while (this.saving) await new Promise((r) => setTimeout(r, 60));
    if (this.dirty) await this.save();
  }

  async publish(extra = {}) {
    await this.flush();
    const res = await api(`/admin/api/doc/${this.name}/publish`, { data: extra });
    this.hasChanges = false;
    this.emit('published', res);
    return res;
  }
}

/** 雙 iframe 預覽：在背景載入新版本，載入完成才切換，避免閃爍並保留捲動位置 */
export class Preview {
  constructor(wrap, { onLoad } = {}) {
    this.wrap = wrap;
    this.onLoad = onLoad;
    this.front = el('iframe', { class: 'ed-frame is-front', title: '即時預覽' });
    this.back = el('iframe', { class: 'ed-frame is-back', title: '預覽（載入中）', 'aria-hidden': 'true', tabindex: '-1' });
    this.bar = el('div', { class: 'ed-frame-bar', html: '<span class="spinner" style="width:14px;height:14px;border-width:2px"></span>更新預覽中…' });
    wrap.append(this.front, this.back, this.bar);
    this.loading = false;
    this.queued = null;
    this.lastUrl = '';
  }

  load(url, { instant = true, keepScroll = true } = {}) {
    if (this.loading) { this.queued = { url, instant, keepScroll }; return; }
    this.loading = true;
    const sep = url.includes('?') ? '&' : '?';
    const full = url + sep + 'editor=1' + (instant ? '&instant=1' : '') + '&_=' + Date.now();
    const samePage = keepScroll && url === this.lastUrl;
    let y = 0;
    try { y = this.front.contentWindow?.scrollY || 0; } catch { y = 0; }
    const timer = setTimeout(() => this.bar.classList.add('show'), 350);
    const back = this.back;
    back.onload = () => {
      clearTimeout(timer);
      this.bar.classList.remove('show');
      try { if (samePage) back.contentWindow.scrollTo(0, y); } catch { /* 忽略 */ }
      requestAnimationFrame(() => {
        const front = this.front;
        back.classList.replace('is-back', 'is-front');
        front.classList.replace('is-front', 'is-back');
        back.removeAttribute('aria-hidden');
        back.removeAttribute('tabindex');
        front.setAttribute('aria-hidden', 'true');
        front.setAttribute('tabindex', '-1');
        this.front = back;
        this.back = front;
        this.lastUrl = url;
        this.loading = false;
        this.onLoad?.(back);
        if (this.queued) {
          const q = this.queued;
          this.queued = null;
          this.load(q.url, q);
        }
      });
    };
    back.src = full;
  }

  doc() {
    try { return this.front.contentDocument; } catch { return null; }
  }
}

/** 編輯器上方工具列 */
export function buildTopbar({ title, backHref, extra, onUndo, onRedo, onDevice, device = 'desktop', onPreview, onPublish, onMobileSwitch }) {
  const status = el('span', { class: 'ed-status', html: '<i></i><span>已儲存草稿</span>' });
  const unpub = el('span', { class: 'ed-unpub' });
  const undo = el('button', { type: 'button', title: '復原（Ctrl+Z）', 'aria-label': '復原', html: icon('undo-2'), onclick: onUndo });
  const redo = el('button', { type: 'button', title: '重做（Ctrl+Shift+Z）', 'aria-label': '重做', html: icon('redo-2'), onclick: onRedo });
  const devices = el('div', { class: 'ed-devices', role: 'group', 'aria-label': '預覽裝置' },
    [['desktop', 'monitor', '電腦'], ['tablet', 'tablet', '平板'], ['mobile', 'smartphone', '手機']].map(([k, ic, label]) =>
      el('button', { type: 'button', class: device === k ? 'active' : '', title: label, 'aria-label': label, dataset: { device: k }, html: icon(ic), onclick: (e) => {
        devices.querySelectorAll('button').forEach((b) => b.classList.toggle('active', b === e.currentTarget));
        onDevice(k);
      } })));
  const top = el('header', { class: 'ed-top' },
    el('a', { class: 'ed-back', href: siteUrl(backHref), title: '回到後台', 'aria-label': '回到後台', html: icon('chevron-left') }),
    el('div', { class: 'ed-title' }, el('strong', { text: title }), status),
    extra || null,
    el('div', { class: 'ed-tools' }, undo, redo, el('span', { class: 'ed-sep' }), devices),
    el('div', { class: 'ed-actions' },
      unpub,
      el('button', { type: 'button', class: 'btn ed-mobile-switch', html: icon('eye') + '<span>切換預覽</span>', onclick: onMobileSwitch }),
      el('button', { type: 'button', class: 'btn', title: '在新分頁預覽草稿', html: icon('external-link') + '<span>預覽</span>', onclick: onPreview }),
      el('button', { type: 'button', class: 'btn btn-primary', html: icon('rocket') + '<span>發布</span>', onclick: onPublish }),
    ),
  );
  return {
    top,
    setStatus(state, info) {
      status.className = 'ed-status ' + state;
      const text = { saving: '儲存中…', dirty: '有尚未儲存的變更', saved: info ? `已儲存草稿 ${info}` : '已儲存草稿', error: '儲存失敗' + (info ? '：' + info : '') }[state] || '';
      status.querySelector('span').textContent = text;
    },
    setHistory(canUndo, canRedo) {
      undo.disabled = !canUndo;
      redo.disabled = !canRedo;
    },
    setUnpublished(has, neverPublished = false) {
      unpub.textContent = neverPublished ? '尚未發布' : has ? '有未發布的變更' : '已是最新發布版本';
      unpub.classList.toggle('clean', !has && !neverPublished);
    },
  };
}

/** 版本紀錄面板 */
export async function historyPanel(container, editor, { onRestored } = {}) {
  container.innerHTML = '';
  container.append(el('div', { class: 'ed-hint', html: '每次<b>發布</b>都會保存一個版本。還原後內容會先回到<b>草稿</b>，確認沒問題再重新發布。' }));
  const list = el('ul', { class: 'rev-list' }, el('li', { class: 'muted', text: '載入中…' }));
  container.append(list);
  const discard = el('button', { type: 'button', class: 'btn btn-danger-ghost', html: icon('rotate-ccw') + '捨棄所有未發布的變更' });
  discard.addEventListener('click', async () => {
    if (!await confirmDialog('草稿會回到目前公開的版本，尚未發布的修改將全部遺失。', { title: '捨棄未發布的變更？', okLabel: '捨棄', danger: true })) return;
    try {
      const res = await api(`/admin/api/doc/${editor.name}/discard`, { data: {} });
      editor.replace(res.doc, res.rev, false);
      onRestored?.();
      toast('已回到公開版本');
    } catch (e) { toast(e.message, 'err'); }
  });
  container.append(el('div', { style: { marginTop: '18px' } }, discard));
  try {
    const res = await api(`/admin/api/doc/${editor.name}/revisions`, { method: 'GET' });
    list.innerHTML = '';
    if (!res.items.length) list.append(el('li', { class: 'muted', text: '還沒有發布紀錄。' }));
    res.items.forEach((r, i) => {
      const btn = el('button', { type: 'button', class: 'btn btn-sm' }, '還原');
      btn.addEventListener('click', async () => {
        if (!await confirmDialog(`把草稿還原成 ${r.at} 發布的版本？目前草稿的內容會被取代。`, { title: '還原版本', okLabel: '還原' })) return;
        try {
          const out = await api(`/admin/api/doc/${editor.name}/restore`, { data: { id: r.id } });
          editor.replace(out.doc, out.rev, out.hasChanges);
          onRestored?.();
          toast('已還原為草稿，確認後請按「發布」');
        } catch (e) { toast(e.message, 'err'); }
      });
      list.append(el('li', {}, el('div', {}, el('strong', { text: r.at + (i === 0 ? '（目前公開）' : '') }), el('small', { text: [r.by, r.note].filter(Boolean).join('・') || '—' })), btn));
    });
  } catch (e) {
    list.innerHTML = `<li class="danger">${escapeHtml(e.message)}</li>`;
  }
}

/** 其他視窗改過同一份草稿時 */
export function showConflict(editor) {
  if (document.querySelector('.ed-conflict')) return;
  const bar = el('div', { class: 'ed-conflict', role: 'alert' },
    el('span', { html: icon('triangle-alert') + ' 草稿已在其他視窗或裝置被修改。' }),
    el('button', { type: 'button', class: 'btn btn-sm', onclick: () => location.reload() }, '重新載入'),
    el('button', { type: 'button', class: 'btn btn-sm btn-primary', onclick: async () => { bar.remove(); await editor.save({ force: true }); toast('已用這個視窗的內容覆蓋'); } }, '以這裡的內容為準'),
  );
  document.body.append(bar);
}

/** 快捷鍵：Ctrl+Z／Ctrl+Shift+Z／Ctrl+S */
export function bindShortcuts(editor, { onUndo, onRedo }) {
  document.addEventListener('keydown', (e) => {
    const mod = e.metaKey || e.ctrlKey;
    if (!mod) return;
    const key = e.key.toLowerCase();
    if (key === 's') {
      e.preventDefault();
      editor.save();
      return;
    }
    const t = e.target;
    const typing = t && (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName));
    if (typing) return;
    if (key === 'z' && !e.shiftKey) { e.preventDefault(); onUndo(); }
    if ((key === 'z' && e.shiftKey) || key === 'y') { e.preventDefault(); onRedo(); }
  });
  window.addEventListener('beforeunload', (e) => {
    if (editor.dirty || editor.saving) {
      editor.save();
      e.preventDefault();
      e.returnValue = '';
    }
  });
}

/** 在預覽畫面中直接編輯文字（雙擊） */
export function inlineEdit(node, { type, value, onDone }) {
  if (node.isContentEditable) return;
  const original = node.innerHTML;
  if (type === 'richtext') {
    node.innerHTML = value || '';
    node.contentEditable = 'true';
  } else {
    node.textContent = value || '';
    node.contentEditable = 'plaintext-only';
    if (node.contentEditable !== 'plaintext-only') node.contentEditable = 'true';
  }
  node.focus();
  const doc = node.ownerDocument;
  const sel = doc.getSelection();
  const range = doc.createRange();
  range.selectNodeContents(node);
  range.collapse(false);
  sel.removeAllRanges();
  sel.addRange(range);
  let cancelled = false;
  const finish = () => {
    node.removeEventListener('blur', finish);
    node.removeEventListener('keydown', onKey);
    node.removeAttribute('contenteditable');
    if (cancelled) { node.innerHTML = original; return; }
    let next;
    if (type === 'richtext') next = node.innerHTML;
    else if (type === 'textarea') next = node.innerText.replace(/\n{3,}/g, '\n\n').trim();
    else next = node.innerText.replace(/\s*\n\s*/g, ' ').trim();
    onDone(next);
  };
  const onKey = (e) => {
    if (e.key === 'Escape') { cancelled = true; node.blur(); }
    if (e.key === 'Enter' && type === 'text') { e.preventDefault(); node.blur(); }
  };
  node.addEventListener('blur', finish);
  node.addEventListener('keydown', onKey);
}

/** 發布對話框 */
export function publishDialog({ title, message, extra, onPublish }) {
  const note = el('input', { type: 'text', placeholder: '這次更新了什麼？（選填，方便日後還原）', maxlength: 200 });
  const body = el('div', {}, el('p', { html: message }), extra || null, el('label', { class: 'field', style: { marginTop: '12px' } }, '版本備註', note));
  modal({
    title,
    body,
    actions: [
      { label: '取消', value: null },
      { label: '發布', class: 'btn-primary', icon: 'rocket', autofocus: true, onClick: async (close, btn) => {
        btn.classList.add('is-loading');
        try {
          await onPublish(note.value.trim());
          close(true);
        } catch (e) {
          btn.classList.remove('is-loading');
          toast(e.message, 'err', 5000);
        }
      } },
    ],
  });
}

/** 預覽畫面內共用的編輯樣式 */
export const FRAME_CSS = `
.amf-hover{outline:2px dashed rgba(124,92,255,.9)!important;outline-offset:-2px}
.amf-selected{outline:2px solid #7c5cff!important;outline-offset:-2px}
.amf-label{position:fixed;z-index:2147483000;pointer-events:none;background:#7c5cff;color:#fff;font:600 12px/1 -apple-system,"PingFang TC","Microsoft JhengHei",sans-serif;padding:6px 9px;border-radius:0 0 8px 0;display:none}
.amf-tb{position:fixed;z-index:2147483001;display:none;gap:2px;padding:3px;border-radius:10px;background:#1f1b2d;box-shadow:0 10px 30px rgba(0,0,0,.35)}
.amf-tb button{width:30px;height:30px;display:grid;place-items:center;border:0;border-radius:7px;background:none;color:#fff;cursor:pointer;padding:0}
.amf-tb button:hover{background:rgba(255,255,255,.14)}
.amf-tb svg{width:16px;height:16px}
.amf-tip{position:fixed;left:50%;bottom:14px;transform:translateX(-50%);z-index:2147483000;background:rgba(31,27,45,.88);color:#fff;font:500 12px/1.4 -apple-system,"PingFang TC","Microsoft JhengHei",sans-serif;padding:7px 12px;border-radius:999px;pointer-events:none;transition:opacity .4s}
`;
