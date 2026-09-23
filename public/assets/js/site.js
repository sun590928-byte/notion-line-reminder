/* aftermoonF 官網互動：捲動進場、逐字標題、視差、數字跳動、3D 傾斜、滑鼠光暈、燈箱、表單 */
(function () {
  'use strict';

  var doc = document.documentElement;
  var body = document.body;
  var base = body.getAttribute('data-base') || '';
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  var editor = body.classList.contains('is-editor');
  var noAnim = reduced || body.classList.contains('no-anim');
  var stagger = parseFloat(getComputedStyle(body).getPropertyValue('--stagger')) || 0.09;

  function $all(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  /* ---------- 標題逐字拆分（英文單字不會被斷行） ---------- */
  var CJK = /[\u2e80-\u9fff\uac00-\ud7af\uf900-\ufaff\uff00-\uffef]/;
  function splitChars(el) {
    if (el.getAttribute('data-split-done')) return;
    el.setAttribute('data-split-done', '1');
    el.setAttribute('aria-label', el.textContent.replace(/\s+/g, ' ').trim());
    var i = 0;
    function makeChar(ch) {
      var s = document.createElement('span');
      s.className = 'ch';
      s.setAttribute('aria-hidden', 'true');
      s.style.setProperty('--ci', i++);
      s.textContent = ch;
      return s;
    }
    (function walk(node) {
      Array.prototype.slice.call(node.childNodes).forEach(function (child) {
        if (child.nodeType === 3) {
          var frag = document.createDocumentFragment();
          var word = null;
          Array.from(child.textContent).forEach(function (ch) {
            if (/\s/.test(ch)) { word = null; frag.appendChild(document.createTextNode(ch)); return; }
            if (CJK.test(ch)) { word = null; frag.appendChild(makeChar(ch)); return; }
            if (!word) { word = document.createElement('span'); word.style.whiteSpace = 'nowrap'; frag.appendChild(word); }
            word.appendChild(makeChar(ch));
          });
          child.parentNode.replaceChild(frag, child);
        } else if (child.nodeType === 1 && child.tagName !== 'BR') {
          walk(child);
        }
      });
    })(el);
  }
  $all('.hero-title.split-text').forEach(splitChars);

  /* ---------- 捲動進場：同時進入畫面的元素依序出現 ---------- */
  var revealEls = $all('.rv, .split-text');
  if (noAnim || !('IntersectionObserver' in window)) {
    revealEls.forEach(function (el) { el.classList.add('in'); });
  } else {
    var io = new IntersectionObserver(function (entries) {
      var n = 0;
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var el = entry.target;
        el.style.setProperty('--d', (n++ * stagger).toFixed(2) + 's');
        el.classList.add('in');
        io.unobserve(el);
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });
    revealEls.forEach(function (el) { io.observe(el); });
    // 動畫結束後移除延遲，讓之後的懸停效果即時反應
    document.addEventListener('transitionend', function (e) {
      if (e.propertyName === 'opacity' && e.target.classList && e.target.classList.contains('rv')) {
        e.target.style.removeProperty('--d');
      }
    });
  }

  /* ---------- 數字跳動 ---------- */
  function countUp(el) {
    var target = parseFloat(el.getAttribute('data-count'));
    if (isNaN(target)) return;
    var original = el.textContent;
    var decimals = (el.getAttribute('data-count').split('.')[1] || '').length;
    var useComma = original.indexOf(',') !== -1;
    var start = null;
    var dur = 1600;
    function fmt(v) {
      var s = v.toFixed(decimals);
      return useComma ? Number(s).toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }) : s;
    }
    function step(ts) {
      if (!start) start = ts;
      var p = Math.min(1, (ts - start) / dur);
      var eased = 1 - Math.pow(1 - p, 3);
      el.textContent = fmt(target * eased);
      if (p < 1) requestAnimationFrame(step); else el.textContent = original;
    }
    requestAnimationFrame(step);
  }
  var counters = $all('[data-count]');
  if (counters.length && !noAnim && 'IntersectionObserver' in window) {
    var cio = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) { countUp(entry.target); cio.unobserve(entry.target); }
      });
    }, { threshold: 0.6 });
    counters.forEach(function (el) { cio.observe(el); });
  }

  /* ---------- 捲動相關：頁首、進度條、回到頂端、視差、時間軸 ---------- */
  var header = document.querySelector('.site-header');
  var progress = document.querySelector('.progress span');
  var toTop = document.querySelector('.to-top');
  var parallax = noAnim ? [] : $all('[data-parallax]');
  var timelines = $all('[data-timeline]');
  var lastY = window.scrollY;
  var ticking = false;

  function onScroll() {
    var y = window.scrollY;
    var vh = window.innerHeight;
    if (header) {
      header.classList.toggle('scrolled', y > 10);
      if (header.hasAttribute('data-autohide') && !body.classList.contains('nav-open')) {
        if (y > lastY + 4 && y > 260) header.classList.add('is-hidden');
        else if (y < lastY - 4 || y < 260) header.classList.remove('is-hidden');
      }
    }
    if (progress) {
      var max = doc.scrollHeight - vh;
      progress.style.setProperty('--p', max > 0 ? Math.min(1, y / max).toFixed(4) : 0);
    }
    if (toTop) toTop.classList.toggle('show', y > 700);
    parallax.forEach(function (el) {
      var host = el.parentElement.getBoundingClientRect();
      if (host.bottom < -100 || host.top > vh + 100) return;
      var f = parseFloat(el.getAttribute('data-parallax')) || 0.3;
      el.style.transform = 'translate3d(0,' + (-host.top * f).toFixed(1) + 'px,0)';
    });
    timelines.forEach(function (tl) {
      var r = tl.getBoundingClientRect();
      var p = Math.max(0, Math.min(1, (vh * 0.62 - r.top) / r.height));
      tl.style.setProperty('--tl', p.toFixed(3));
    });
    lastY = y;
    ticking = false;
  }
  window.addEventListener('scroll', function () {
    if (!ticking) { ticking = true; requestAnimationFrame(onScroll); }
  }, { passive: true });
  window.addEventListener('resize', onScroll);
  onScroll();

  if (toTop) toTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' }); });

  /* ---------- 手機選單 ---------- */
  var toggle = document.querySelector('.nav-toggle');
  function closeNav() {
    body.classList.remove('nav-open');
    if (toggle) { toggle.setAttribute('aria-expanded', 'false'); toggle.setAttribute('aria-label', '開啟選單'); }
    body.style.overflow = '';
  }
  if (toggle) {
    toggle.addEventListener('click', function () {
      var open = !body.classList.contains('nav-open');
      body.classList.toggle('nav-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? '關閉選單' : '開啟選單');
      body.style.overflow = open ? 'hidden' : '';
      if (open && header) header.classList.remove('is-hidden');
    });
    $all('.main-nav a').forEach(function (a) { a.addEventListener('click', closeNav); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeNav(); });
  }

  /* ---------- 3D 傾斜（滑鼠跟隨） ---------- */
  if (finePointer && !reduced) {
    $all('[data-tilt]').forEach(function (el) {
      el.addEventListener('pointermove', function (e) {
        var r = el.getBoundingClientRect();
        var x = (e.clientX - r.left) / r.width;
        var y = (e.clientY - r.top) / r.height;
        el.classList.add('tilting');
        el.style.transform = 'perspective(900px) rotateX(' + ((0.5 - y) * 8).toFixed(2) + 'deg) rotateY(' + ((x - 0.5) * 10).toFixed(2) + 'deg) translateY(-4px)';
        el.style.setProperty('--mx', (x * 100).toFixed(1) + '%');
        el.style.setProperty('--my', (y * 100).toFixed(1) + '%');
      });
      el.addEventListener('pointerleave', function () {
        el.classList.remove('tilting');
        el.style.transform = '';
      });
    });
  }

  /* ---------- 深色區塊滑鼠光暈 ---------- */
  if (finePointer && !reduced && body.classList.contains('has-glow')) {
    $all('.sec.bg-dark, .sec.bg-primary, .sec.bg-image, .sec-hero').forEach(function (sec) {
      var layer = document.createElement('div');
      layer.className = 'glow-layer';
      sec.appendChild(layer);
      sec.addEventListener('pointermove', function (e) {
        var r = sec.getBoundingClientRect();
        layer.style.setProperty('--gx', (e.clientX - r.left) + 'px');
        layer.style.setProperty('--gy', (e.clientY - r.top) + 'px');
      });
    });
  }

  /* ---------- 輪播左右按鈕 ---------- */
  $all('.carousel-nav').forEach(function (nav) {
    var scroller = nav.previousElementSibling;
    if (!scroller) return;
    nav.addEventListener('click', function (e) {
      var btn = e.target.closest('button[data-dir]');
      if (!btn) return;
      scroller.scrollBy({ left: parseInt(btn.getAttribute('data-dir'), 10) * scroller.clientWidth * 0.85, behavior: reduced ? 'auto' : 'smooth' });
    });
  });

  /* ---------- 相簿燈箱 ---------- */
  var lightbox = null;
  var lbItems = [];
  var lbIndex = 0;
  function lbShow(i) {
    lbIndex = (i + lbItems.length) % lbItems.length;
    var item = lbItems[lbIndex];
    var img = lightbox.querySelector('img');
    img.style.opacity = '0';
    img.onload = function () { img.style.opacity = '1'; };
    img.src = item.href;
    img.alt = item.caption || '';
    lightbox.querySelector('.lb-cap').textContent = item.caption || '';
  }
  function lbClose() {
    if (!lightbox) return;
    lightbox.classList.remove('open');
    body.style.overflow = '';
    setTimeout(function () { if (lightbox) lightbox.hidden = true; }, 300);
  }
  function lbOpen(items, i) {
    if (!lightbox) {
      lightbox = document.createElement('div');
      lightbox.className = 'lightbox';
      lightbox.setAttribute('role', 'dialog');
      lightbox.setAttribute('aria-modal', 'true');
      lightbox.innerHTML = '<img alt=""><p class="lb-cap"></p>'
        + '<button class="lb-btn lb-close" type="button" aria-label="關閉">✕</button>'
        + '<button class="lb-btn lb-prev" type="button" aria-label="上一張">‹</button>'
        + '<button class="lb-btn lb-next" type="button" aria-label="下一張">›</button>';
      body.appendChild(lightbox);
      lightbox.addEventListener('click', function (e) {
        if (e.target.closest('.lb-prev')) lbShow(lbIndex - 1);
        else if (e.target.closest('.lb-next')) lbShow(lbIndex + 1);
        else if (e.target.closest('.lb-close') || e.target === lightbox) lbClose();
      });
      var sx = null;
      lightbox.addEventListener('pointerdown', function (e) { sx = e.clientX; });
      lightbox.addEventListener('pointerup', function (e) {
        if (sx !== null && Math.abs(e.clientX - sx) > 50) lbShow(lbIndex + (e.clientX < sx ? 1 : -1));
        sx = null;
      });
      document.addEventListener('keydown', function (e) {
        if (!lightbox || lightbox.hidden) return;
        if (e.key === 'Escape') lbClose();
        if (e.key === 'ArrowLeft') lbShow(lbIndex - 1);
        if (e.key === 'ArrowRight') lbShow(lbIndex + 1);
      });
    }
    lbItems = items;
    lightbox.hidden = false;
    lightbox.querySelector('.lb-prev').style.display = items.length > 1 ? '' : 'none';
    lightbox.querySelector('.lb-next').style.display = items.length > 1 ? '' : 'none';
    lbShow(i);
    body.style.overflow = 'hidden';
    requestAnimationFrame(function () { lightbox.classList.add('open'); });
    lightbox.querySelector('.lb-close').focus();
  }
  $all('[data-lightbox]').forEach(function (gallery) {
    gallery.addEventListener('click', function (e) {
      var link = e.target.closest('.g-link');
      if (!link || editor) return;
      e.preventDefault();
      var links = $all('.g-link', gallery);
      lbOpen(links.map(function (a) { return { href: a.getAttribute('href'), caption: a.getAttribute('data-caption') || '' }; }), links.indexOf(link));
    });
  });
  $all('.gallery:not([data-lightbox]) .g-link').forEach(function (a) {
    a.addEventListener('click', function (e) { e.preventDefault(); });
  });

  /* ---------- 常見問題：平滑展開／收合 ---------- */
  $all('.faq-item').forEach(function (d) {
    var summary = d.querySelector('summary');
    summary.addEventListener('click', function (e) {
      if (reduced || !d.animate) return;
      e.preventDefault();
      if (d.getAttribute('data-animating')) return;
      d.setAttribute('data-animating', '1');
      var from = d.offsetHeight;
      var to;
      var closing = d.open;
      if (!closing) { d.open = true; to = d.offsetHeight; } else { to = summary.offsetHeight + (d.offsetHeight - d.clientHeight); }
      d.style.overflow = 'hidden';
      var anim = d.animate({ height: [from + 'px', to + 'px'] }, { duration: 380, easing: 'cubic-bezier(.2,.75,.2,1)' });
      anim.onfinish = function () {
        if (closing) d.open = false;
        d.style.overflow = '';
        d.removeAttribute('data-animating');
      };
    });
  });

  /* ---------- 聯絡表單（不換頁送出） ---------- */
  $all('form[data-contact]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (editor) return;
      if (!form.reportValidity()) return;
      var status = form.querySelector('.form-status');
      form.classList.add('is-sending');
      status.className = 'form-status';
      status.textContent = '傳送中…';
      fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (data.ok) {
            status.classList.add('ok');
            status.textContent = status.getAttribute('data-success') || '已送出，謝謝！';
            form.reset();
          } else {
            status.classList.add('err');
            status.textContent = data.error || '送出失敗，請稍後再試。';
          }
        })
        .catch(function () {
          status.classList.add('err');
          status.textContent = '網路連線異常，請稍後再試。';
        })
        .then(function () { form.classList.remove('is-sending'); });
    });
  });

  /* ---------- 連結點擊統計 ---------- */
  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('[data-link]');
    if (!a || editor || !navigator.sendBeacon) return;
    navigator.sendBeacon(base + '/api/click', new Blob([JSON.stringify({ id: a.getAttribute('data-link') })], { type: 'text/plain' }));
  });
})();
