// 連結頁編輯器（Linktree 風格）：連結、圖示、社群、外觀、設定，手機框即時預覽
import { clone, configure, confirmDialog, el, escapeHtml, getPath, icon, moveItem, setPath, toast, uid } from './core.js';
import { buildFields, defaults, makeSortable, openIconPicker } from './fields.js';
import { DocEditor, FRAME_CSS, Preview, bindShortcuts, buildTopbar, historyPanel, inlineEdit, publishDialog, showConflict } from './editor-core.js';

const cfg = JSON.parse(document.getElementById('editor-config').textContent);
configure({ base: cfg.base, csrf: cfg.csrf });
const SCHEMA = cfg.schema;
const CLICKS = cfg.clicks && !Array.isArray(cfg.clicks) ? cfg.clicks : {};
const editor = new DocEditor({ name: 'links', doc: cfg.doc, rev: cfg.rev, hasChanges: cfg.hasChanges });
let neverPublished = !cfg.publishedAt;
const state = { tab: 'links', open: new Set(), focus: null, view: '' };

/* ---------- 版面 ---------- */
const root = document.getElementById('editor');
root.innerHTML = '';
const frameWrap = el('div', { class: 'ed-frame-wrap is-phone', dataset: { device: 'mobile' } });
const topbar = buildTopbar({
  title: '連結頁編輯器',
  backHref: '/admin',
  device: 'mobile',
  onUndo: () => editor.undo(),
  onRedo: () => editor.redo(),
  onDevice: (d) => { frameWrap.dataset.device = d; },
  onPreview: () => window.open(cfg.previewUrl, '_blank'),
  onPublish: openPublish,
  onMobileSwitch: () => root.classList.toggle('show-preview'),
});
const TABS = [['links', 'link', '連結'], ['profile', 'user', '個人資料'], ['socials', 'share-2', '社群'], ['theme', 'palette', '外觀'], ['settings', 'settings', '設定'], ['history', 'history', '版本']];
const tabBar = el('nav', { class: 'ed-tabs', 'aria-label': '編輯項目' }, TABS.map(([key, ic, label]) =>
  el('button', { type: 'button', dataset: { tab: key }, html: icon(ic) + escapeHtml(label), onclick: () => { state.tab = key; renderPanel(); } })));
const panelBody = el('div', { class: 'ed-panel-body' });
root.append(topbar.top, el('div', { class: 'ed-main' }, el('aside', { class: 'ed-panel' }, tabBar, panelBody), el('main', { class: 'ed-canvas' }, frameWrap)));
const preview = new Preview(frameWrap, { onLoad: attachOverlay });

const doc = () => editor.doc;
const commit = (opts = {}) => editor.commit(opts);
function mini(ic, label, onclick) {
  return el('button', { type: 'button', class: 'ed-mini', title: label, 'aria-label': label, html: icon(ic), onclick: (e) => { e.stopPropagation(); onclick(); } });
}

/* ---------- 面板 ---------- */
function renderPanel() {
  tabBar.querySelectorAll('button').forEach((b) => b.classList.toggle('active', b.dataset.tab === state.tab));
  const keep = state.view === state.tab ? panelBody.scrollTop : 0;
  state.view = state.tab;
  panelBody.innerHTML = '';
  ({ links: linksPanel, profile: profilePanel, socials: socialsPanel, theme: themePanel, settings: settingsPanel }[state.tab] || (() => historyPanel(panelBody, editor, { onRestored: afterServerReplace })))();
  panelBody.scrollTop = keep;
  if (state.focus) {
    panelBody.querySelector(`[data-id="${CSS.escape(state.focus)}"]`)?.scrollIntoView({ block: 'center' });
    state.focus = null;
  }
}

function linksPanel() {
  panelBody.append(el('div', { class: 'ed-add-row' },
    el('button', { type: 'button', class: 'ed-add', html: icon('plus') + '新增連結', onclick: () => addLink('link') }),
    el('button', { type: 'button', class: 'ed-add', html: icon('heading') + '分類標題', onclick: () => addLink('header') }),
  ));
  const list = el('ul', { class: 'ed-list lk-list' });
  doc().links.forEach((l) => list.append(linkCard(l)));
  if (!doc().links.length) list.append(el('li', { class: 'empty small', html: icon('link') + '<p>還沒有連結，按上方「新增連結」開始。</p>' }));
  makeSortable(list, { item: '.lk-card', onEnd: (from, to) => { moveItem(doc().links, from, to); commit({ render: true, delay: 80 }); } });
  panelBody.append(list, el('div', { class: 'ed-hint', html: '小技巧：在右側預覽<b>點一下連結</b>可以快速找到它的設定，<b>雙擊名稱或簡介</b>可以直接修改。' }));
}

function linkCard(l) {
  const isHeader = l.type === 'header';
  const card = el('li', { class: 'lk-card' + (isHeader ? ' is-header' : '') + (l.enabled ? '' : ' is-off'), dataset: { id: l.id } });
  const toggle = el('input', { type: 'checkbox', class: 'switch', title: l.enabled ? '顯示中（點擊隱藏）' : '已隱藏（點擊顯示）', 'aria-label': '顯示這個連結' });
  toggle.checked = !!l.enabled;
  toggle.addEventListener('change', () => { l.enabled = toggle.checked; card.classList.toggle('is-off', !l.enabled); commit({ delay: 150 }); });
  const title = el('input', { type: 'text', class: 'lk-title', value: l.title, placeholder: isHeader ? '分類標題' : '連結標題', maxlength: 120 });
  title.addEventListener('input', () => { l.title = title.value; commit({ coalesce: l.id + '.title' }); });
  const grip = el('button', { type: 'button', class: 'ed-grip', title: '拖曳排序', 'aria-label': '拖曳排序', html: icon('grip-vertical') });
  if (isHeader) {
    card.append(el('div', { class: 'lk-row' }, grip, el('span', { class: 'lk-icon static', html: icon('heading') }), el('div', { class: 'lk-fields' }, title), toggle,
      mini('trash-2', '刪除', () => removeLink(l))));
    return card;
  }
  const iconBtn = el('button', { type: 'button', class: 'lk-icon', title: '更換圖示', 'aria-label': '更換圖示', html: l.icon ? icon(l.icon) : icon('plus') });
  iconBtn.addEventListener('click', async () => {
    const picked = await openIconPicker({ value: l.icon });
    if (picked === null) return;
    l.icon = picked;
    iconBtn.innerHTML = picked ? icon(picked) : icon('plus');
    commit({ delay: 150 });
  });
  const url = el('input', { type: 'text', class: 'lk-url', value: l.url, placeholder: 'https://、mailto: 或 tel:', inputmode: 'url' });
  url.addEventListener('input', () => { l.url = url.value.trim(); commit({ coalesce: l.id + '.url' }); });
  const clicks = CLICKS[l.id] || 0;
  const badges = [];
  if (l.highlight && l.highlight !== 'none') badges.push('醒目');
  if (l.start || l.end) badges.push('排程');
  if (l.thumb) badges.push('縮圖');
  const extra = el('div', { class: 'lk-extra' });
  const open = state.open.has(l.id);
  extra.hidden = !open;
  const more = el('button', { type: 'button', class: 'lk-more' + (open ? ' open' : ''), html: icon('settings') + '更多設定' + (badges.length ? `<em>${badges.join('・')}</em>` : '') });
  more.addEventListener('click', () => {
    extra.hidden = !extra.hidden;
    more.classList.toggle('open', !extra.hidden);
    if (extra.hidden) state.open.delete(l.id); else state.open.add(l.id);
    if (!extra.hidden && !extra.childElementCount) fillExtra();
  });
  const fillExtra = () => {
    const fields = SCHEMA.link.filter((f) => ['desc', 'highlight', 'thumb', 'newTab', 'start', 'end'].includes(f.key));
    extra.append(buildFields(fields, l, { onChange: (k, v, o) => commit({ coalesce: o.coalesce ? `${l.id}.${o.coalesce}` : null, delay: 400 }) }));
    extra.append(el('div', { class: 'btn-row', style: { marginTop: '12px' } },
      el('button', { type: 'button', class: 'btn btn-sm', html: icon('copy') + '複製連結', onclick: () => duplicateLink(l) }),
      el('button', { type: 'button', class: 'btn btn-sm btn-danger-ghost', html: icon('trash-2') + '刪除', onclick: () => removeLink(l) })));
  };
  if (open) fillExtra();
  card.append(
    el('div', { class: 'lk-row' }, grip, iconBtn, el('div', { class: 'lk-fields' }, title, url), toggle),
    el('div', { class: 'lk-meta' }, more, el('span', { class: 'lk-clicks', title: '近 30 天點擊次數', html: icon('chart-column') + clicks + ' 次點擊' })),
    extra,
  );
  return card;
}

function addLink(type) {
  const item = { id: uid('l'), ...defaults(SCHEMA.link), type, title: type === 'header' ? '新的分類' : '', url: '', icon: type === 'header' ? '' : 'link' };
  doc().links.unshift(item);
  state.focus = item.id;
  commit({ render: true, delay: 150 });
  setTimeout(() => panelBody.querySelector(`[data-id="${CSS.escape(item.id)}"] .lk-title`)?.focus(), 60);
}

function duplicateLink(l) {
  const copy = clone(l);
  copy.id = uid('l');
  doc().links.splice(doc().links.indexOf(l) + 1, 0, copy);
  commit({ render: true, delay: 80 });
}

async function removeLink(l) {
  if (!await confirmDialog(`確定要刪除「${l.title || '未命名連結'}」嗎？（可以按「復原」救回）`, { title: '刪除連結', okLabel: '刪除', danger: true })) return;
  doc().links.splice(doc().links.indexOf(l), 1);
  commit({ render: true, delay: 80 });
}

function profilePanel() {
  panelBody.append(el('div', { class: 'ed-hint', html: '大頭貼建議使用正方形圖片（至少 400×400）。' }));
  panelBody.append(buildFields(SCHEMA.profile, doc().profile, { onChange: (k, v, o) => commit({ coalesce: o.coalesce ? `profile.${o.coalesce}` : null }) }));
}

function socialsPanel() {
  panelBody.append(el('div', { class: 'ed-hint', html: '社群圖示會以小圓形按鈕顯示在簡介下方（可在「設定」改到頁面底部），也會同步顯示在官網頁尾。' }));
  const list = el('ul', { class: 'ed-list' });
  doc().socials.forEach((s, i) => {
    const iconBtn = el('button', { type: 'button', class: 'lk-icon', title: '選擇平台', html: icon(s.icon) || icon('plus') });
    iconBtn.addEventListener('click', async () => {
      const picked = await openIconPicker({ value: s.icon, groups: ['brand', 'link'] });
      if (!picked) return;
      s.icon = picked;
      iconBtn.innerHTML = icon(picked);
      commit({ delay: 150 });
    });
    const url = el('input', { type: 'text', value: s.url, placeholder: 'https://…' });
    url.addEventListener('input', () => { s.url = url.value.trim(); commit({ coalesce: `social.${i}` }); });
    list.append(el('li', { class: 'lk-card social' },
      el('div', { class: 'lk-row' },
        el('button', { type: 'button', class: 'ed-grip', title: '拖曳排序', html: icon('grip-vertical') }),
        iconBtn, el('div', { class: 'lk-fields' }, url),
        mini('trash-2', '刪除', () => { doc().socials.splice(i, 1); commit({ render: true, delay: 80 }); }))));
  });
  makeSortable(list, { item: '.lk-card', onEnd: (from, to) => { moveItem(doc().socials, from, to); commit({ render: true, delay: 80 }); } });
  panelBody.append(list, el('button', { type: 'button', class: 'ed-add', html: icon('plus') + '新增社群圖示', onclick: async () => {
    const picked = await openIconPicker({ groups: ['brand', 'link'] });
    if (!picked) return;
    doc().socials.push({ icon: picked, url: '' });
    commit({ render: true, delay: 150 });
    setTimeout(() => { const inputs = panelBody.querySelectorAll('.lk-fields input'); inputs[inputs.length - 1]?.focus(); }, 60);
  } }));
}

function themePanel() {
  const t = doc().theme;
  panelBody.append(el('div', { class: 'ed-section-title', text: '主題' }));
  const presets = el('div', { class: 'presets' });
  for (const [key, p] of Object.entries(cfg.themes)) {
    const bg = p.bgType === 'gradient' ? `linear-gradient(${p.bgAngle}deg, ${p.bgColor}, ${p.bgColor2})` : p.bgColor;
    const swatch = el('span', { class: 'preset-swatch', style: { background: bg } },
      el('i', { style: { background: p.buttonStyle === 'outline' ? 'transparent' : p.buttonColor, border: `1.5px solid ${p.buttonStyle === 'glass' ? 'rgba(255,255,255,.5)' : p.buttonColor}`, opacity: p.buttonStyle === 'glass' ? '.5' : '1' } }),
      el('i', { style: { background: p.accent } }));
    presets.append(el('button', { type: 'button', class: 'preset' + (t.preset === key ? ' active' : ''), onclick: () => {
      Object.assign(t, clone(p));
      delete t.label;
      t.preset = key;
      commit({ render: true, delay: 100 });
    } }, swatch, p.label));
  }
  panelBody.append(presets, el('div', { class: 'ed-section-title', text: '自訂外觀' }));
  panelBody.append(buildFields(SCHEMA.theme, t, { exclude: ['preset'], onChange: (k, v, o) => {
    if (t.preset !== 'custom' && k !== 'animation') t.preset = 'custom';
    commit({ coalesce: o.coalesce ? `theme.${o.coalesce}` : null, delay: 450 });
  } }));
}

function settingsPanel() {
  panelBody.append(el('div', { class: 'ed-hint', html: cfg.mode === 'links'
    ? `目前首頁顯示的就是連結頁：<b>${escapeHtml(location.origin + cfg.publicUrl)}</b>`
    : `連結頁網址：<b>${escapeHtml(location.origin + cfg.publicUrl)}</b>（官網公開後首頁會顯示官網）` }));
  panelBody.append(buildFields(SCHEMA.settings, doc().settings, { onChange: (k, v, o) => commit({ coalesce: o.coalesce ? `settings.${o.coalesce}` : null }) }));
}

/* ---------- 預覽互動 ---------- */
function attachOverlay(frame) {
  const d = frame.contentDocument;
  if (!d || !d.body) return;
  const style = d.createElement('style');
  style.textContent = FRAME_CSS;
  d.head.append(style);
  d.addEventListener('click', (e) => {
    if (e.target.closest('[contenteditable]')) return;
    const target = e.target.closest('a, button');
    if (target) e.preventDefault();
    const item = e.target.closest('[data-lid]');
    if (item) {
      const id = item.dataset.lid;
      state.tab = 'links';
      state.open.add(id);
      state.focus = id;
      root.classList.remove('show-preview');
      renderPanel();
      const card = panelBody.querySelector(`[data-id="${CSS.escape(id)}"]`);
      card?.classList.add('flash');
      setTimeout(() => card?.classList.remove('flash'), 1200);
    }
  }, true);
  d.addEventListener('dblclick', (e) => {
    const node = e.target.closest('[data-edit]');
    if (!node) return;
    const path = node.dataset.edit;
    const f = SCHEMA.profile.find((x) => 'profile.' + x.key === path);
    if (!f) return;
    e.preventDefault();
    inlineEdit(node, { type: f.type, value: getPath(doc(), path), onDone: (v) => {
      if (v === getPath(doc(), path)) return;
      setPath(doc(), path, v);
      commit({ render: state.tab === 'profile', delay: 150 });
    } });
  });
}

function openPublish() {
  publishDialog({
    title: '發布連結頁',
    message: `發布後，連結頁（<b>${escapeHtml(location.origin + cfg.publicUrl)}</b>）會立即更新成目前的內容。`,
    onPublish: async (note) => {
      await editor.publish({ note });
      toast('連結頁已發布！');
    },
  });
}

function updateToolbar() {
  topbar.setHistory(editor.canUndo(), editor.canRedo());
  topbar.setUnpublished(editor.hasChanges, neverPublished);
}

function afterServerReplace() {
  renderPanel();
  updateToolbar();
  preview.load(cfg.previewUrl, { instant: true });
}

editor.on('change', ({ render }) => { if (render) renderPanel(); updateToolbar(); });
editor.on('replace', () => { renderPanel(); updateToolbar(); });
editor.on('saved', () => { updateToolbar(); preview.load(cfg.previewUrl, { instant: true }); });
editor.on('status', (s, info) => topbar.setStatus(s, info));
editor.on('conflict', () => showConflict(editor));
editor.on('published', () => { neverPublished = false; updateToolbar(); });
bindShortcuts(editor, { onUndo: () => editor.undo(), onRedo: () => editor.redo() });

renderPanel();
updateToolbar();
preview.load(cfg.previewUrl, { instant: false, keepScroll: false });
