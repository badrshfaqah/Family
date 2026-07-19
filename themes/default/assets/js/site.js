(function () {
  'use strict';

  // التقاط حدث التثبيت فور تحميل السكربت (قد يُطلقه المتصفح قبل اكتمال تحميل الصفحة)
  var deferredInstallPrompt = null;

  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredInstallPrompt = e;
    revealInstallButtons();
  });

  window.addEventListener('appinstalled', function () {
    deferredInstallPrompt = null;
    var bar = document.querySelector('[data-a2hs]');
    if (bar) bar.hidden = true;
    try { localStorage.setItem('fam_a2hs_closed', '1'); } catch (e) {}
  });

  function revealInstallButtons() {
    document.querySelectorAll('[data-a2hs-install]').forEach(function (b) { b.hidden = false; });
    var pageCard = document.querySelector('[data-android-install]');
    if (pageCard) pageCard.hidden = false;
  }

  document.addEventListener('DOMContentLoaded', function () {
    initMobileNav();
    initCountdowns();
    initShare();
    initAnnouncements();
    initA2hs();
    initPush();
  });

  // بانر "أضف للشاشة الرئيسية" + التثبيت المباشر حيث يدعمه المتصفح
  function initA2hs() {
    // الحدث قد يكون التُقط قبل اكتمال تحميل الصفحة — أظهر الأزرار الآن
    if (deferredInstallPrompt) revealInstallButtons();

    document.addEventListener('click', function (e) {
      var trigger = e.target.closest('[data-a2hs-install],[data-a2hs-install-page]');
      if (!trigger) return;
      if (deferredInstallPrompt) {
        deferredInstallPrompt.prompt();
        deferredInstallPrompt.userChoice.then(function () { deferredInstallPrompt = null; });
        return;
      }
      // لا يوجد أمر تثبيت متاح (استُهلك أو غير مدعوم): انتقل لصفحة الشرح
      var fallback = trigger.getAttribute('data-install-fallback');
      if (fallback && location.pathname.indexOf('install-app') === -1) location.href = fallback;
    });

    var bar = document.querySelector('[data-a2hs]');
    if (!bar) return;

    var standalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
    if (standalone || localStorage.getItem('fam_a2hs_closed') === '1') return;

    bar.hidden = false;
    bar.querySelector('[data-a2hs-close]').addEventListener('click', function () {
      bar.hidden = true;
      localStorage.setItem('fam_a2hs_closed', '1');
    });
  }

  // تفعيل تنبيهات Push والاشتراك فيها — يعمل من أي زر [data-push-enable] (الترويسة أو صفحة التثبيت)
  function initPush() {
    var buttons = document.querySelectorAll('[data-push-enable]');
    if (!buttons.length) return;

    var status = document.querySelector('[data-push-status]');
    var supported = ('serviceWorker' in navigator) && ('PushManager' in window) && ('Notification' in window);

    function say(msg) {
      if (status) status.textContent = msg;
      showToast(msg);
    }

    function showToast(msg) {
      var toast = document.querySelector('.push-toast');
      if (!toast) {
        toast = document.createElement('div');
        toast.className = 'push-toast';
        document.body.appendChild(toast);
      }
      toast.textContent = msg;
      toast.classList.add('show');
      clearTimeout(toast._timer);
      toast._timer = setTimeout(function () { toast.classList.remove('show'); }, 3500);
    }

    function markDone() {
      buttons.forEach(function (b) {
        if (b.hasAttribute('data-hide-on-active')) {
          b.hidden = true;
        } else {
          b.textContent = '✅ التنبيهات مفعلة';
          b.disabled = true;
        }
      });
    }

    // إذا كانت التنبيهات مفعلة مسبقًا على هذا الجهاز: أخفِ زر الترويسة
    if (supported && Notification.permission === 'granted') {
      navigator.serviceWorker.ready.then(function (reg) {
        return reg.pushManager.getSubscription();
      }).then(function (sub) {
        if (sub) markDone();
      }).catch(function () {});
    }

    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        // متصفح لا يدعم التنبيهات (مثل سفاري الآيفون قبل تثبيت الموقع): وجّه لصفحة الشرح
        if (!supported) {
          var installUrl = btn.getAttribute('data-install-url');
          if (installUrl && location.pathname.indexOf('install-app') === -1) {
            location.href = installUrl;
          } else {
            say('على الآيفون: ثبّت الموقع على الشاشة الرئيسية أولًا، ثم افتحه من الأيقونة وفعّل التنبيهات.');
          }
          return;
        }

        Notification.requestPermission().then(function (permission) {
          if (permission !== 'granted') {
            say('لم يُمنح الإذن — يمكنك السماح بالتنبيهات من إعدادات المتصفح ثم المحاولة مجددًا.');
            return;
          }
          navigator.serviceWorker.ready.then(function (reg) {
            return reg.pushManager.subscribe({
              userVisibleOnly: true,
              applicationServerKey: urlBase64ToUint8Array(btn.getAttribute('data-vapid'))
            });
          }).then(function (sub) {
            return fetch(btn.getAttribute('data-subscribe-url') || 'push/subscribe', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(sub)
            });
          }).then(function (res) {
            if (res.ok) {
              say('✅ تم تفعيل التنبيهات — سيصلك كل جديد');
              markDone();
            } else {
              say('تعذر حفظ الاشتراك، حاول مرة أخرى.');
            }
          }).catch(function () {
            say('تعذر التفعيل، حاول مرة أخرى.');
          });
        });
      });
    });
  }

  function urlBase64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - base64String.length % 4) % 4);
    var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    var raw = window.atob(base64);
    var output = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; ++i) output[i] = raw.charCodeAt(i);
    return output;
  }

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
