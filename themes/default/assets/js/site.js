(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initMobileNav();
    initCountdowns();
    initShare();
    initAnnouncements();
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

  function initAnnouncements() {
    var bar = document.querySelector('[data-announce-bar]');
    if (bar) {
      var barId = bar.getAttribute('data-id');
      var barKey = 'fam_announce_bar_closed_' + barId;
      if (localStorage.getItem(barKey) === '1') {
        bar.style.display = 'none';
      } else {
        var closeBtn = bar.querySelector('[data-announce-close]');
        if (closeBtn) {
          closeBtn.addEventListener('click', function () {
            bar.style.display = 'none';
            localStorage.setItem(barKey, '1');
          });
        }
      }
    }

    var popup = document.querySelector('[data-announce-popup]');
    if (popup) {
      var popupId = popup.getAttribute('data-id');
      var frequency = popup.getAttribute('data-frequency');
      var intervalDays = parseInt(popup.getAttribute('data-interval-days') || '0', 10);
      var storageKey = 'fam_announce_popup_' + popupId;
      var shouldShow = true;

      if (frequency === 'once') {
        shouldShow = !localStorage.getItem(storageKey);
      } else if (frequency === 'interval_days' && intervalDays > 0) {
        var last = parseInt(localStorage.getItem(storageKey) || '0', 10);
        shouldShow = (Date.now() - last) > (intervalDays * 86400000);
      }

      if (shouldShow) {
        popup.classList.add('open');
        document.body.style.overflow = 'hidden';
      }

      function closePopup() {
        popup.classList.remove('open');
        document.body.style.overflow = '';
        localStorage.setItem(storageKey, String(Date.now()));
      }

      var popupClose = popup.querySelector('[data-announce-close]');
      if (popupClose) popupClose.addEventListener('click', closePopup);
      popup.addEventListener('click', function (e) {
        if (e.target === popup) closePopup();
      });
    }
  }

  function copyLink(url, btn) {
    navigator.clipboard.writeText(url).then(function () {
      var original = btn.textContent;
      btn.textContent = 'تم نسخ الرابط';
      setTimeout(function () { btn.textContent = original; }, 1600);
    });
  }
})();
