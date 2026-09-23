/* aftermoonF 連結頁：分享按鈕、點擊統計 */
(function () {
  'use strict';
  var body = document.body;
  var base = body.getAttribute('data-base') || '';
  var editor = body.classList.contains('is-editor');
  var toast = document.querySelector('.lt-toast');
  var toastTimer = null;

  function showToast(text) {
    if (!toast) return;
    toast.textContent = text;
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toast.classList.remove('show'); }, 2200);
  }

  var share = document.querySelector('.lt-share');
  if (share) {
    share.addEventListener('click', function () {
      var url = location.href.split('#')[0].replace(/[?&]editor=1/, '');
      var title = share.getAttribute('data-title') || document.title;
      if (navigator.share) {
        navigator.share({ title: title, url: url }).catch(function () {});
        return;
      }
      if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(function () { showToast('已複製連結'); }, function () { showToast(url); });
      } else {
        showToast(url);
      }
    });
  }

  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('[data-link]');
    if (!a || editor || !navigator.sendBeacon) return;
    navigator.sendBeacon(base + '/api/click', new Blob([JSON.stringify({ id: a.getAttribute('data-link') })], { type: 'text/plain' }));
  });
})();
