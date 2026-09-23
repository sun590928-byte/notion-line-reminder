// 官網編輯器（Wix 式）：區塊新增／排序／隱藏、點選預覽畫面編輯、雙擊文字直接修改、頁面與主題管理
import { clone, configure, confirmDialog, el, escapeHtml, getPath, icon, modal, moveItem, promptDialog, setPath, slugify, toast, uid } from './core.js';
import { buildFields, defaults, makeSortable } from './fields.js';
import { DocEditor, FRAME_CSS, Preview, bindShortcuts, buildTopbar, historyPanel, inlineEdit, publishDialog, showConflict } from './editor-core.js';

const cfg = JSON.parse(document.getElementById('editor-config').textContent);
configure({ base: cfg.base, csrf: cfg.csrf });
const SCHEMA = cfg.schema;
const SECTIONS = SCHEMA.sections;
const editor = new DocEditor({ name: 'site', doc: cfg.doc, rev: cfg.rev, hasChanges: cfg.hasChanges });
let neverPublished = !cfg.publishedAt;
let mode = cfg.mode;

const state = {
  pageId: cfg.doc.homeId || cfg.doc.pages[0]?.id,
  tab: 'sections',
  selected: null,
  subtab: 'content',
  scrollTo: null,
  view: '',
  tipShown: false,
};

/* ---------- 版面 ---------- */
const root = document.getElementById('editor');
root.innerHTML = '';
const pageSelect = el('select', { 'aria-label': '正在編輯的頁面' });
pageSelect.addEventListener('change', () => switchPage(pageSelect.value));
const frameWrap = el('div', { class: 'ed-frame-wrap', dataset: { device: 'desktop' } });
const topbar = buildTopbar({
  title: '官網編輯器',
  backHref: '/admin',
  extra: el('div', { class: 'ed-page-select' }, pageSelect),
  onUndo: () => editor.undo(),
  onRedo: () => editor.redo(),
  onDevice: (d) => { frameWrap.dataset.device = d; },
  onPreview: () => window.open(previewUrl(), '_blank'),
  onPublish: openPublish,
  onMobileSwitch: () => root.classList.toggle('show-preview'),
});
const TABS = [['sections', 'layers', '區塊'], ['pages', 'file', '頁面'], ['design', 'palette', '設計'], ['layout', 'panels-top-left', '頁首頁尾'], ['history', 'history', '版本']];
const tabBar = el('nav', { class: 'ed-tabs', 'aria-label': '編輯項目' }, TABS.map(([key, ic, label]) =>
  el('button', { type: 'button', dataset: { tab: key }, html: icon(ic) + escapeHtml(label), onclick: () => { state.tab = key; if (key !== 'sections') state.selected = null; renderPanel(); } })));
const panelBody = el('div', { class: 'ed-panel-body' });
root.append(topbar.top, el('div', { class: 'ed-main' }, el('aside', { class: 'ed-panel' }, tabBar, panelBody), el('main', { class: 'ed-canvas' }, frameWrap)));
const preview = new Preview(frameWrap, { onLoad: attachOverlay });

/* ---------- 工具 ---------- */
const doc = () => editor.doc;
const currentPage = () => doc().pages.find((p) => p.id === state.pageId) || doc().pages[0];
const isHome = (p) => p.id === doc().homeId;
const pagesCtx = () => doc().pages.map((p) => ({ id: p.id, title: p.title }));
function previewUrl() {
  const p = currentPage();
  return cfg.previewBase + (isHome(p) ? '' : '/' + encodeURIComponent(p.slug));
}
function findSection(id) {
  if (!id) return null;
  for (const page of doc().pages) {
    const section = page.sections.find((s) => s.id === id);
    if (section) return { page, section };
  }
  return null;
}
function fieldForPath(type, path) {
  const def = SECTIONS[type];
  if (!def) return null;
  const parts = String(path).split('.');
  const f = def.fields.find((x) => x.key === parts[0]);
  if (!f) return null;
  if (f.type === 'list' && parts.length === 3) return f.fields.find((x) => x.key === parts[2]) || null;
  return parts.length === 1 ? f : null;
}
function summary(s) {
  const d = s.data || {};
  const text = d.heading || d.title || d.eyebrow || (Array.isArray(d.items) && d.items.length ? `${d.items.length} 個項目` : '') || SECTIONS[s.type]?.desc || '';
  return String(text).replace(/\s+/g, ' ').slice(0, 40);
}
function newSection(type, overrides = {}) {
  const def = SECTIONS[type];
  return { id: uid('s'), type, hidden: false, data: Object.assign(clone(def.sample), overrides), style: defaults(def.styleFields) };
}
function commit(opts = {}) {
  editor.commit(opts);
}
function mini(ic, label, onclick, disabled = false) {
  return el('button', { type: 'button', class: 'ed-mini', title: label, 'aria-label': label, html: icon(ic), disabled, onclick: (e) => { e.stopPropagation(); onclick(); } });
}

/* ---------- 面板 ---------- */
function renderAll() {
  renderPageSelect();
  renderPanel();
  updateToolbar();
}

function renderPageSelect() {
  pageSelect.innerHTML = '';
  for (const p of doc().pages) pageSelect.append(el('option', { value: p.id, selected: p.id === currentPage().id }, (isHome(p) ? '🏠 ' : '') + p.title));
}

function renderPanel() {
  tabBar.querySelectorAll('button').forEach((b) => b.classList.toggle('active', b.dataset.tab === state.tab));
  if (state.selected && !findSection(state.selected)) state.selected = null;
  const view = [state.tab, state.selected, state.subtab, state.pageId].join('|');
  const keep = view === state.view ? panelBody.scrollTop : 0;
  state.view = view;
  panelBody.innerHTML = '';
  if (state.tab === 'sections') state.selected ? sectionEditor() : sectionList();
  else if (state.tab === 'pages') pagesPanel();
  else if (state.tab === 'design') designPanel();
  else if (state.tab === 'layout') layoutPanel();
  else historyPanel(panelBody, editor, { onRestored: afterServerReplace });
  panelBody.scrollTop = keep;
}

function sectionList() {
  const page = currentPage();
  panelBody.append(el('div', { class: 'ed-hint', html: `正在編輯「<b>${escapeHtml(page.title)}</b>」。點選區塊修改內容，拖曳左側把手調整順序。也可以直接在右邊預覽畫面<b>點選區塊</b>、<b>雙擊文字</b>修改。` }));
  const list = el('ul', { class: 'ed-list' });
  page.sections.forEach((s, i) => {
    const def = SECTIONS[s.type];
    const li = el('li', { class: 'ed-item' + (s.hidden ? ' is-hidden' : ''), dataset: { id: s.id }, tabindex: '0' },
      el('button', { type: 'button', class: 'ed-grip', title: '拖曳排序', 'aria-label': '拖曳排序', html: icon('grip-vertical') }),
      el('span', { class: 'ed-item-icon', html: icon(def?.icon || 'square-stack') }),
      el('span', { class: 'ed-item-text' }, el('strong', { text: def?.label || s.type }), el('small', { text: summary(s) })),
      el('span', { class: 'ed-item-actions' },
        mini(s.hidden ? 'eye-off' : 'eye', s.hidden ? '顯示區塊' : '隱藏區塊', () => { s.hidden = !s.hidden; commit({ render: true, delay: 100 }); }),
        mini('copy', '複製區塊', () => duplicateSection(page, i)),
        mini('trash-2', '刪除區塊', () => deleteSection(page, i)),
      ),
    );
    li.addEventListener('click', (e) => { if (!e.target.closest('button')) selectSection(s.id, { scroll: true }); });
    li.addEventListener('keydown', (e) => { if (e.key === 'Enter') selectSection(s.id, { scroll: true }); });
    li.addEventListener('mouseenter', () => hoverInPreview(s.id, true));
    li.addEventListener('mouseleave', () => hoverInPreview(s.id, false));
    list.append(li);
  });
  makeSortable(list, { item: '.ed-item', onEnd: (from, to) => { moveItem(page.sections, from, to); commit({ render: true, delay: 100 }); } });
  panelBody.append(list, el('button', { type: 'button', class: 'ed-add', html: icon('plus') + '新增區塊', onclick: () => openGallery(page.sections.length) }));
}

function sectionEditor() {
  const { page, section: s } = findSection(state.selected);
  const def = SECTIONS[s.type];
  const idx = page.sections.indexOf(s);
  panelBody.append(el('div', { class: 'ed-sub-head' },
    mini('chevron-left', '返回區塊列表', () => { state.selected = null; renderPanel(); markSelected(); }),
    el('h3', { html: icon(def.icon) + escapeHtml(def.label) }),
    mini('chevron-up', '上移', () => moveSection(page, idx, idx - 1), idx === 0),
    mini('chevron-down', '下移', () => moveSection(page, idx, idx + 1), idx === page.sections.length - 1),
    mini(s.hidden ? 'eye-off' : 'eye', s.hidden ? '顯示區塊' : '隱藏區塊', () => { s.hidden = !s.hidden; commit({ render: true, delay: 100 }); }),
    mini('copy', '複製區塊', () => duplicateSection(page, idx)),
    mini('trash-2', '刪除區塊', () => deleteSection(page, idx)),
  ));
  panelBody.append(el('div', { class: 'ed-subtabs' }, [['content', '內容'], ['style', '樣式與動畫']].map(([k, label]) =>
    el('button', { type: 'button', class: state.subtab === k ? 'active' : '', onclick: () => { state.subtab = k; renderPanel(); } }, label))));
  if (s.hidden) panelBody.append(el('div', { class: 'ed-hint', html: '這個區塊目前<b>已隱藏</b>，訪客看不到。' }));
  if (state.subtab === 'content') {
    panelBody.append(el('p', { class: 'fld-help', text: def.desc }));
    panelBody.append(buildFields(def.fields, s.data, {
      ctx: { pages: pagesCtx() },
      onChange: (key, value, o) => commit({ coalesce: o.coalesce ? `${s.id}.${o.coalesce}` : null }),
    }));
  } else {
    panelBody.append(buildFields(def.styleFields, s.style, {
      ctx: { pages: pagesCtx() },
      onChange: (key, value, o) => commit({ coalesce: o.coalesce ? `${s.id}.style.${o.coalesce}` : null, delay: 400 }),
    }));
  }
  panelBody.append(el('button', { type: 'button', class: 'ed-add', style: { marginTop: '22px' }, html: icon('plus') + '在下方新增區塊', onclick: () => openGallery(idx + 1) }));
}

function selectSection(id, { scroll = false } = {}) {
  state.tab = 'sections';
  if (state.selected !== id) state.subtab = 'content';
  state.selected = id;
  root.classList.remove('show-preview');
  renderPanel();
  markSelected(scroll);
}

function openGallery(index) {
  const grid = el('div', { class: 'sec-gallery' });
  const m = modal({ title: '新增區塊', body: grid, wide: true });
  for (const [type, def] of Object.entries(SECTIONS)) {
    grid.append(el('button', { type: 'button', onclick: () => { m.close(); addSection(type, index); } },
      el('span', { html: icon(def.icon) }), el('strong', { text: def.label }), el('small', { text: def.desc })));
  }
}

function addSection(type, index) {
  const page = currentPage();
  const s = newSection(type);
  page.sections.splice(index, 0, s);
  state.selected = s.id;
  state.subtab = 'content';
  state.tab = 'sections';
  state.scrollTo = s.id;
  commit({ render: true, delay: 60 });
  toast(`已新增「${SECTIONS[type].label}」區塊`);
}

function duplicateSection(page, i) {
  const copy = clone(page.sections[i]);
  copy.id = uid('s');
  page.sections.splice(i + 1, 0, copy);
  state.selected = copy.id;
  state.scrollTo = copy.id;
  commit({ render: true, delay: 60 });
}

async function deleteSection(page, i) {
  const s = page.sections[i];
  if (!await confirmDialog(`確定要刪除「${SECTIONS[s.type]?.label || s.type}」區塊嗎？（可以按「復原」救回）`, { title: '刪除區塊', okLabel: '刪除', danger: true })) return;
  page.sections.splice(i, 1);
  if (state.selected === s.id) state.selected = null;
  commit({ render: true, delay: 60 });
}

function moveSection(page, from, to) {
  if (to < 0 || to >= page.sections.length) return;
  moveItem(page.sections, from, to);
  state.scrollTo = page.sections[to].id;
  commit({ render: true, delay: 60 });
}

/* 頁面管理 */
function pagesPanel() {
  panelBody.append(el('div', { class: 'ed-hint', html: '點選頁面切換編輯；拖曳可以調整<b>選單順序</b>。🏠 是首頁（官網公開時訪客看到的第一頁）。' }));
  const list = el('ul', { class: 'ed-list' });
  doc().pages.forEach((p) => {
    const li = el('li', { class: 'ed-item' + (p.id === currentPage().id ? ' is-selected' : ''), tabindex: '0' },
      el('button', { type: 'button', class: 'ed-grip', title: '拖曳排序', html: icon('grip-vertical') }),
      el('span', { class: 'ed-item-icon', html: icon(isHome(p) ? 'house' : (p.membersOnly ? 'lock' : 'file')) }),
      el('span', { class: 'ed-item-text' }, el('strong', { text: p.title }), el('small', { text: isHome(p) ? '/（首頁）' : '/' + p.slug })),
      el('span', { class: 'ed-item-actions', style: { opacity: 1 } },
        mini(p.showInNav ? 'eye' : 'eye-off', p.showInNav ? '顯示在選單（點擊隱藏）' : '不在選單（點擊顯示）', () => { p.showInNav = !p.showInNav; commit({ render: true, delay: 100 }); }),
        mini('settings', '頁面設定', () => openPageSettings(p)),
      ),
    );
    li.addEventListener('click', (e) => { if (!e.target.closest('button')) switchPage(p.id); });
    list.append(li);
  });
  makeSortable(list, { item: '.ed-item', onEnd: (from, to) => { moveItem(doc().pages, from, to); commit({ render: true, delay: 100 }); } });
  panelBody.append(list, el('button', { type: 'button', class: 'ed-add', html: icon('plus') + '新增頁面', onclick: addPage }));
}

function switchPage(id) {
  if (!doc().pages.some((p) => p.id === id)) return;
  state.pageId = id;
  state.selected = null;
  renderAll();
  preview.load(previewUrl(), { instant: false, keepScroll: false });
}

async function addPage() {
  const title = await promptDialog('新增頁面', { label: '頁面名稱', placeholder: '例如：作品集' });
  if (!title) return;
  const base = slugify(title) || 'page';
  let slug = base;
  let n = 2;
  while (doc().pages.some((p) => p.slug === slug) || cfg.reservedSlugs.includes(slug)) slug = `${base}-${n++}`;
  const page = {
    id: uid('p'), title, slug, showInNav: true, membersOnly: false, seoTitle: '', seoDescription: '', seoImage: '',
    sections: [newSection('hero', { eyebrow: '', title, subtitle: '在這裡寫下這一頁的簡介。', buttons: [], height: 'md', scrollHint: false }), newSection('text')],
  };
  doc().pages.push(page);
  state.pageId = page.id;
  state.selected = null;
  commit({ render: true, delay: 60 });
  renderPageSelect();
  toast(`已新增頁面「${title}」${/[^\x00-\x7F]/.test(title) ? '，可在頁面設定修改網址' : ''}`);
}

function openPageSettings(p) {
  const body = el('div');
  body.append(buildFields(SCHEMA.page, p, { onChange: (key, v, o) => {
    commit({ coalesce: o.coalesce ? `page.${p.id}.${key}` : null });
    if (key === 'title') renderPageSelect();
  } }));
  const actions = el('div', { class: 'btn-row', style: { marginTop: '18px' } });
  if (!isHome(p)) {
    actions.append(el('button', { type: 'button', class: 'btn', html: icon('house') + '設為首頁', onclick: () => { doc().homeId = p.id; commit({ render: true, delay: 60 }); renderPageSelect(); m.close(); toast('已設為首頁'); } }));
  }
  actions.append(el('button', { type: 'button', class: 'btn', html: icon('copy') + '複製頁面', onclick: () => {
    const copy = clone(p);
    copy.id = uid('p');
    copy.title = p.title + '（複本）';
    copy.slug = p.slug + '-copy';
    copy.sections.forEach((s) => { s.id = uid('s'); });
    doc().pages.splice(doc().pages.indexOf(p) + 1, 0, copy);
    commit({ render: true, delay: 60 });
    renderPageSelect();
    m.close();
  } }));
  if (!isHome(p)) {
    actions.append(el('button', { type: 'button', class: 'btn btn-danger-ghost', html: icon('trash-2') + '刪除頁面', onclick: async () => {
      m.close();
      if (!await confirmDialog(`確定要刪除「${p.title}」頁面與其中所有區塊嗎？`, { title: '刪除頁面', okLabel: '刪除', danger: true })) return;
      doc().pages.splice(doc().pages.indexOf(p), 1);
      if (state.pageId === p.id) state.pageId = doc().homeId;
      commit({ render: true, delay: 60 });
      renderAll();
    } }));
  }
  body.append(actions);
  const m = modal({ title: `頁面設定：${p.title}`, body, onClose: () => renderPanel(), actions: [{ label: '完成', class: 'btn-primary', value: true }] });
}

/* 設計（主題） */
function designPanel() {
  const t = doc().theme;
  panelBody.append(el('div', { class: 'ed-section-title', text: '主題配色' }));
  const presets = el('div', { class: 'presets' });
  for (const [key, p] of Object.entries(cfg.themes)) {
    const swatch = el('span', { class: 'preset-swatch', style: { background: p.bg, border: `1px solid ${p.surface}` } },
      el('i', { style: { background: p.primary } }), el('i', { style: { background: p.accent } }));
    presets.append(el('button', { type: 'button', class: 'preset' + (t.preset === key ? ' active' : ''), onclick: () => {
      for (const k of ['primary', 'accent', 'bg', 'surface', 'text', 'muted', 'dark', 'headingFont', 'bodyFont', 'radius', 'buttonShape']) if (p[k] !== undefined) t[k] = p[k];
      t.preset = key;
      commit({ render: true, delay: 100 });
    } }, swatch, p.label));
  }
  panelBody.append(presets, el('div', { class: 'ed-section-title', text: '細部調整' }));
  const themeKeys = ['primary', 'accent', 'bg', 'surface', 'text', 'muted', 'dark', 'headingFont', 'bodyFont', 'radius', 'buttonShape'];
  panelBody.append(buildFields(SCHEMA.theme, t, { exclude: ['preset'], onChange: (key, v, o) => {
    if (themeKeys.includes(key) && t.preset !== 'custom') t.preset = 'custom';
    commit({ coalesce: o.coalesce ? `theme.${o.coalesce}` : null, delay: 500 });
  } }));
}

function layoutPanel() {
  panelBody.append(el('div', { class: 'ed-section-title', text: '頁首（選單）' }));
  panelBody.append(el('p', { class: 'fld-help', text: '選單會自動列出「頁面」中設定為顯示在選單的頁面。' }));
  panelBody.append(buildFields(SCHEMA.header, doc().header, { ctx: { pages: pagesCtx() }, onChange: (k, v, o) => commit({ coalesce: o.coalesce ? `header.${o.coalesce}` : null }) }));
  panelBody.append(el('div', { class: 'ed-section-title', text: '頁尾' }));
  panelBody.append(buildFields(SCHEMA.footer, doc().footer, { ctx: { pages: pagesCtx() }, onChange: (k, v, o) => commit({ coalesce: o.coalesce ? `footer.${o.coalesce}` : null }) }));
}

/* ---------- 預覽畫面互動 ---------- */
function frameDoc() {
  return preview.doc();
}

function hoverInPreview(id, on) {
  const d = frameDoc();
  const node = d?.querySelector(`[data-sid="${CSS.escape(id)}"]`);
  if (node && id !== state.selected) node.classList.toggle('amf-hover', on);
}

function markSelected(scroll = false) {
  const d = frameDoc();
  if (!d) return;
  d.querySelectorAll('.amf-selected').forEach((n) => n.classList.remove('amf-selected'));
  const node = state.selected ? d.querySelector(`[data-sid="${CSS.escape(state.selected)}"]`) : null;
  if (node) {
    node.classList.add('amf-selected');
    node.classList.remove('amf-hover');
    if (scroll) node.scrollIntoView({ block: 'start', behavior: 'smooth' });
  }
  d.defaultView?.dispatchEvent(new Event('amf:position'));
}

function attachOverlay(frame) {
  const d = frame.contentDocument;
  const w = frame.contentWindow;
  if (!d || !d.body) return;
  const style = d.createElement('style');
  style.textContent = FRAME_CSS;
  d.head.append(style);
  const label = d.createElement('div');
  label.className = 'amf-label';
  const tb = d.createElement('div');
  tb.className = 'amf-tb';
  tb.innerHTML = [['up', 'chevron-up', '上移'], ['down', 'chevron-down', '下移'], ['copy', 'copy', '複製'], ['hide', 'eye-off', '隱藏／顯示'], ['del', 'trash-2', '刪除']]
    .map(([act, ic, title]) => `<button type="button" data-act="${act}" title="${title}" aria-label="${title}">${icon(ic)}</button>`).join('');
  d.body.append(label, tb);
  let hovered = null;

  const place = () => {
    if (hovered) {
      const r = hovered.getBoundingClientRect();
      label.textContent = (SECTIONS[hovered.dataset.type]?.label || '') + (hovered.classList.contains('is-hidden-sec') ? '（已隱藏）' : '');
      label.style.display = 'block';
      label.style.left = Math.max(0, r.left) + 'px';
      label.style.top = Math.max(0, r.top) + 'px';
    } else {
      label.style.display = 'none';
    }
    const sel = state.selected && d.querySelector(`[data-sid="${CSS.escape(state.selected)}"]`);
    if (sel) {
      const r = sel.getBoundingClientRect();
      const visible = r.bottom > 40 && r.top < w.innerHeight;
      tb.style.display = visible ? 'flex' : 'none';
      tb.style.top = Math.min(Math.max(8, r.top + 8), w.innerHeight - 46) + 'px';
      tb.style.left = Math.max(8, r.right - 190) + 'px';
    } else {
      tb.style.display = 'none';
    }
  };
  w.addEventListener('amf:position', place);
  w.addEventListener('scroll', place, { passive: true });
  w.addEventListener('resize', place);

  d.addEventListener('mouseover', (e) => {
    const sec = e.target.closest?.('[data-sid]');
    if (sec === hovered) return;
    hovered?.classList.remove('amf-hover');
    hovered = sec;
    if (sec && sec.dataset.sid !== state.selected) sec.classList.add('amf-hover');
    place();
  });
  d.documentElement.addEventListener('mouseleave', () => { hovered?.classList.remove('amf-hover'); hovered = null; place(); });

  tb.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-act]');
    const found = findSection(state.selected);
    if (!btn || !found) return;
    e.preventDefault();
    e.stopPropagation();
    const { page, section } = found;
    const i = page.sections.indexOf(section);
    const act = btn.dataset.act;
    if (act === 'up') moveSection(page, i, i - 1);
    if (act === 'down') moveSection(page, i, i + 1);
    if (act === 'copy') duplicateSection(page, i);
    if (act === 'hide') { section.hidden = !section.hidden; commit({ render: true, delay: 60 }); }
    if (act === 'del') deleteSection(page, i);
  });

  d.addEventListener('click', (e) => {
    if (e.target.closest('.amf-tb') || e.target.closest('[contenteditable]')) return;
    if (e.target.closest('a, button, summary, label, input, textarea, select')) e.preventDefault();
    const sec = e.target.closest('[data-sid]');
    if (sec) selectSection(sec.dataset.sid);
  }, true);
  d.addEventListener('submit', (e) => e.preventDefault(), true);

  d.addEventListener('dblclick', (e) => {
    const node = e.target.closest('[data-edit]');
    const sec = node?.closest('[data-sid]');
    const found = sec && findSection(sec.dataset.sid);
    if (!found) return;
    const path = node.dataset.edit;
    const field = fieldForPath(found.section.type, path);
    if (!field || !['text', 'textarea', 'richtext'].includes(field.type)) return;
    e.preventDefault();
    inlineEdit(node, {
      type: field.type,
      value: getPath(found.section.data, path),
      onDone: (value) => {
        if (value === getPath(found.section.data, path)) return;
        setPath(found.section.data, path, value);
        commit({ render: state.selected === found.section.id, delay: 150 });
      },
    });
  });

  markSelected(false);
  if (state.scrollTo) {
    d.querySelector(`[data-sid="${CSS.escape(state.scrollTo)}"]`)?.scrollIntoView({ block: 'start', behavior: 'smooth' });
    state.scrollTo = null;
  }
  place();
  if (!state.tipShown) {
    state.tipShown = true;
    const tip = d.createElement('div');
    tip.className = 'amf-tip';
    tip.textContent = '點選區塊即可編輯・雙擊文字可直接修改';
    d.body.append(tip);
    setTimeout(() => { tip.style.opacity = '0'; }, 4200);
    setTimeout(() => tip.remove(), 5000);
  }
}

/* ---------- 發布 ---------- */
function openPublish() {
  let publicToggle = null;
  let extra = null;
  if (mode !== 'website') {
    publicToggle = el('input', { type: 'checkbox', class: 'switch' });
    extra = el('label', { class: 'switch-row', style: { padding: '12px 14px', borderRadius: '12px', background: 'var(--line-2)' } },
      el('span', {}, '同時公開官網', el('small', { text: '首頁改為顯示官方網站，連結頁會移到 /links' })), publicToggle);
  }
  publishDialog({
    title: '發布官方網站',
    message: mode === 'website'
      ? '發布後，<b>公開的官方網站</b>會立即更新成目前的草稿內容。'
      : '官網目前是<b>隱藏</b>狀態（首頁顯示連結頁）。發布會把草稿存成正式版本，訪客仍然看不到，直到你選擇公開。',
    extra,
    onPublish: async (note) => {
      const res = await editor.publish({ note, mode: publicToggle?.checked ? 'website' : '' });
      toast(res.mode === 'website' ? '已發布，官網已公開！' : '已發布（官網仍然隱藏中）');
    },
  });
}

/* ---------- 狀態同步 ---------- */
function updateToolbar() {
  topbar.setHistory(editor.canUndo(), editor.canRedo());
  topbar.setUnpublished(editor.hasChanges, neverPublished);
}

function afterServerReplace() {
  if (!doc().pages.some((p) => p.id === state.pageId)) state.pageId = doc().homeId;
  state.selected = null;
  renderAll();
  preview.load(previewUrl(), { instant: true });
}

editor.on('change', ({ render }) => {
  if (render) {
    renderPanel();
    renderPageSelect();
  } else if (state.tab === 'sections' && !state.selected) {
    renderPanel();
  }
  updateToolbar();
});
editor.on('replace', () => {
  if (!doc().pages.some((p) => p.id === state.pageId)) state.pageId = doc().homeId;
  renderAll();
});
editor.on('saved', (res) => {
  if (res.slugs) {
    for (const p of doc().pages) if (res.slugs[p.id] && res.slugs[p.id] !== p.slug) p.slug = res.slugs[p.id];
  }
  updateToolbar();
  preview.load(previewUrl(), { instant: true });
});
editor.on('status', (s, info) => topbar.setStatus(s, info));
editor.on('conflict', () => showConflict(editor));
editor.on('published', (res) => {
  neverPublished = false;
  mode = res.mode || mode;
  updateToolbar();
});
bindShortcuts(editor, { onUndo: () => editor.undo(), onRedo: () => editor.redo() });

renderAll();
preview.load(previewUrl(), { instant: false, keepScroll: false });
