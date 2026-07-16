(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initMobileNav();
    initCountdowns();
    initShare();
  });

  function initMobileNav() {
    var trigger = document.querySelector('[data-nav-trigger]');
    var nav = document.querySelector('[data-mobile-nav]');
    var closeBtn = document.querySelector('[data-nav-close]');
    if (!trigger || !nav) return;

    trigger.addEventListener('click', function () {
      nav.classList.add('open');
      document.body.style.overflow = 'hidden';
    });
    function close() {
      nav.classList.remove('open');
      document.body.style.overflow = '';
    }
    if (closeBtn) closeBtn.addEventListener('click', close);
    nav.addEventListener('click', function (e) {
      if (e.target === nav) close();
    });
  }

  function initCountdowns() {
    var els = document.querySelectorAll('[data-countdown]');
    if (!els.length) return;

    function tick() {
      var now = new Date().getTime();
      els.forEach(function (el) {
        var target = new Date(el.getAttribute('data-countdown')).getTime();
        var diff = target - now;
        var label = el.querySelector('[data-countdown-label]');

        if (diff <= 0) {
          el.style.display = 'none';
          return;
        }

        var days = Math.floor(diff / 86400000);
        var hours = Math.floor((diff % 86400000) / 3600000);
        var mins = Math.floor((diff % 3600000) / 60000);
        var secs = Math.floor((diff % 60000) / 1000);

        setCell(el, 'days', days);
        setCell(el, 'hours', hours);
        setCell(el, 'mins', mins);
        setCell(el, 'secs', secs);

        if (label) {
          if (days === 0) label.textContent = 'اليوم';
          else if (days === 1) label.textContent = 'غدًا';
          else label.textContent = 'بعد ' + days + ' يوم';
        }
      });
    }

    function setCell(el, key, value) {
      var cell = el.querySelector('[data-c-' + key + ']');
      if (cell) cell.textContent = String(value).padStart(2, '0');
    }

    tick();
    setInterval(tick, 1000);
  }

  function initShare() {
    document.querySelectorAll('[data-share-native]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var url = btn.getAttribute('data-url') || location.href;
        var title = btn.getAttribute('data-title') || document.title;
        if (navigator.share) {
          navigator.share({ title: title, url: url }).catch(function () {});
        } else {
          copyLink(url, btn);
        }
      });
    });

    document.querySelectorAll('[data-copy-link]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        copyLink(btn.getAttribute('data-url') || location.href, btn);
      });
    });
  }

  function copyLink(url, btn) {
    navigator.clipboard.writeText(url).then(function () {
      var original = btn.textContent;
      btn.textContent = 'تم نسخ الرابط';
      setTimeout(function () { btn.textContent = original; }, 1600);
    });
  }
})();
