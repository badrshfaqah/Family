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
    initDeclarativeActions();
    initMobileNav();
    initCountdowns();
    initShare();
    initShareCard();
    initAnnouncements();
    initA2hs();
    initPush();
  });

  function initDeclarativeActions() {
    document.querySelectorAll('[data-auto-submit]').forEach(function (el) {
      el.addEventListener('change', function () {
        if (el.form) el.form.submit();
      });
    });
    document.querySelectorAll('[data-print-page]').forEach(function (el) {
      el.addEventListener('click', function () { window.print(); });
    });
  }

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
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': btn.getAttribute('data-csrf-token') || ''
              },
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
        var text = btn.getAttribute('data-text') || '';
        if (navigator.share) {
          var payload = { title: title, url: url };
          if (text) payload.text = text;
          navigator.share(payload).catch(function () {});
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

  // «المشاركة/التصدير كصورة»: توليد بطاقة بهوية الموقع (مناسبة أو وفاة) وتنزيلها أو مشاركتها
  function initShareCard() {
    document.querySelectorAll('[data-share-card]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (btn.getAttribute('data-busy')) return;
        btn.setAttribute('data-busy', '1');
        var original = btn.textContent;
        btn.textContent = 'جارٍ تجهيز الصورة…';

        function done() {
          btn.textContent = original;
          btn.removeAttribute('data-busy');
        }

        var isObituary = btn.dataset.shareCard === 'obituary';
        var builder = isObituary ? buildObituaryCard : buildEventCard;
        var fileName = isObituary ? 'obituary-card.png' : 'event-card.png';

        builder(btn.dataset).then(function (blob) {
          var file = new File([blob], fileName, { type: 'image/png' });
          if (navigator.canShare && navigator.share && navigator.canShare({ files: [file] })) {
            return navigator.share({ files: [file], title: btn.dataset.title || btn.dataset.name || '' }).catch(function () {});
          }
          var a = document.createElement('a');
          a.href = URL.createObjectURL(blob);
          a.download = fileName;
          document.body.appendChild(a);
          a.click();
          a.remove();
          setTimeout(function () { URL.revokeObjectURL(a.href); }, 4000);
        }).then(done, done);
      });
    });
  }

  function loadCardImage(src) {
    return new Promise(function (resolve) {
      if (!src) return resolve(null);
      var img = new Image();
      img.onload = function () { resolve(img); };
      img.onerror = function () { resolve(null); };
      img.src = src;
    });
  }

  function drawCardCover(ctx, img, x, y, w, h) {
    var scale = Math.max(w / img.width, h / img.height);
    var sw = w / scale, sh = h / scale;
    ctx.drawImage(img, (img.width - sw) / 2, (img.height - sh) / 2, sw, sh, x, y, w, h);
  }

  function cardRoundRect(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
  }

  function wrapCardText(ctx, text, maxWidth) {
    var words = String(text || '').split(/\s+/).filter(Boolean);
    var lines = [], line = '';
    words.forEach(function (word) {
      var test = line ? line + ' ' + word : word;
      if (line && ctx.measureText(test).width > maxWidth) {
        lines.push(line);
        line = word;
      } else {
        line = test;
      }
    });
    if (line) lines.push(line);
    return lines;
  }

  function cardHexToRgba(hex, alpha) {
    var h = String(hex).replace('#', '');
    if (h.length === 3) h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
    var n = parseInt(h, 16);
    if (isNaN(n)) return 'rgba(0,0,0,' + alpha + ')';
    return 'rgba(' + ((n >> 16) & 255) + ',' + ((n >> 8) & 255) + ',' + (n & 255) + ',' + alpha + ')';
  }

  function cardDiamond(ctx, cx, cy, size) {
    ctx.beginPath();
    ctx.moveTo(cx, cy - size);
    ctx.lineTo(cx + size, cy);
    ctx.lineTo(cx, cy + size);
    ctx.lineTo(cx - size, cy);
    ctx.closePath();
  }

  // شريط بنقش السدو (كما في هوية الموقع): أرضية بلون الهوية وصف معينات ذهبية
  function cardSaduBand(ctx, W, y, h, primary, secondary) {
    ctx.fillStyle = primary;
    ctx.fillRect(0, y, W, h);
    var cy = y + h / 2, size = h * 0.3;
    ctx.fillStyle = secondary;
    for (var x = 17; x < W + 17; x += 34) {
      cardDiamond(ctx, x, cy, size);
      ctx.fill();
    }
  }

  // فاصل زخرفي: خطان رفيعان يلتقيان بمعين ذهبي
  function cardOrnamentDivider(ctx, cx, cy, halfSpan, secondary) {
    ctx.strokeStyle = secondary;
    ctx.lineWidth = 1.6;
    ctx.beginPath();
    ctx.moveTo(cx - halfSpan, cy);
    ctx.lineTo(cx - 30, cy);
    ctx.moveTo(cx + 30, cy);
    ctx.lineTo(cx + halfSpan, cy);
    ctx.stroke();
    ctx.fillStyle = secondary;
    cardDiamond(ctx, cx, cy, 9);
    ctx.fill();
    ctx.lineWidth = 1;
    ctx.strokeStyle = cardHexToRgba(secondary, 0.6);
    cardDiamond(ctx, cx - halfSpan - 16, cy, 5);
    ctx.stroke();
    cardDiamond(ctx, cx + halfSpan + 16, cy, 5);
    ctx.stroke();
  }

  function cardFont(size, weight) {
    return (weight || 700) + ' ' + size + 'px Tajawal, "Segoe UI", Tahoma, Arial, sans-serif';
  }

  // على iOS Safari تحديدًا، document.fonts.ready لا يضمن تحميل وزن خط لم يُستخدم
  // بعد في الصفحة، فيظهر نص الكانفاس فارغًا؛ نطلب تحميل كل وزن صراحةً قبل الرسم
  function ensureCardFontsLoaded() {
    if (!document.fonts || !document.fonts.load) return Promise.resolve();
    var loads = [400, 700, 800].map(function (weight) {
      try { return document.fonts.load(weight + ' 16px Tajawal'); } catch (e) { return Promise.resolve(); }
    });
    var settle = Promise.all(loads).catch(function () {});
    var timeout = new Promise(function (resolve) { setTimeout(resolve, 1500); });
    return Promise.race([settle, timeout]);
  }

  function cardThemeColors() {
    var rootStyle = getComputedStyle(document.documentElement);
    return {
      primary: (rootStyle.getPropertyValue('--c-primary') || '').trim() || '#0f6e5e',
      secondary: (rootStyle.getPropertyValue('--c-secondary') || '').trim() || '#c9a24b',
    };
  }

  // بطاقة دعوة احترافية بهوية الموقع — بدون البوستر (خلفية مصممة وليست صورة)
  function buildEventCard(d) {
    var theme = cardThemeColors();
    var primary = theme.primary;
    var secondary = theme.secondary;
    var W = 1080;

    var fontsReady = ensureCardFontsLoaded();

    return Promise.all([loadCardImage(d.person), fontsReady]).then(function (loaded) {
      var person = loaded[0];

      var rows = [];
      if (d.date) rows.push('🗓  ' + d.date);
      if (d.time) rows.push('🕗  ' + d.time);
      if (d.place) rows.push('📍  ' + d.place);

      // مقادير التخطيط (تُستخدم في حساب الارتفاع ثم في الرسم بنفس القيم)
      var personR = 145;
      var personBlock = person ? (2 * (personR + 14) + 24) : 0;   // الدائرة بإطارها + تنفس
      var chipBlock = d.type ? 96 : 0;
      var titleLineH = 84;
      var dividerBlock = 72;
      var rowH = 80;
      var panelBlock = rows.length ? rows.length * rowH + 48 : 0;

      var scratch = document.createElement('canvas').getContext('2d');
      scratch.font = cardFont(58, 800);
      var titleLines = wrapCardText(scratch, d.title, W - 280).slice(0, 3);
      var titleBlock = titleLines.length * titleLineH + 6;

      var total = personBlock + chipBlock + titleBlock + dividerBlock + panelBlock;
      var contentTop = 216, bottomReserve = 210;
      var H = 1350;
      if (total > H - contentTop - bottomReserve) {
        H = contentTop + bottomReserve + total;
      }

      var canvas = document.createElement('canvas');
      canvas.width = W;
      canvas.height = H;
      var ctx = canvas.getContext('2d');
      ctx.textAlign = 'center';
      try { ctx.direction = 'rtl'; } catch (e) {}

      // خلفية كريمية بتدرج دافئ + توهج خفيف بلون الهوية أعلى البطاقة
      var bg = ctx.createLinearGradient(0, 0, 0, H);
      bg.addColorStop(0, '#fdfaf1');
      bg.addColorStop(1, '#f5eedb');
      ctx.fillStyle = bg;
      ctx.fillRect(0, 0, W, H);
      var glow = ctx.createRadialGradient(W / 2, 0, 60, W / 2, 0, H * 0.55);
      glow.addColorStop(0, cardHexToRgba(primary, 0.07));
      glow.addColorStop(1, 'rgba(0,0,0,0)');
      ctx.fillStyle = glow;
      ctx.fillRect(0, 0, W, H);

      // نقش نقاط خافت جدًا بلون الهوية
      ctx.fillStyle = cardHexToRgba(primary, 0.04);
      for (var px = 30; px < W; px += 46) {
        for (var py = 56; py < H - 44; py += 46) {
          ctx.beginPath();
          ctx.arc(px, py, 2.2, 0, Math.PI * 2);
          ctx.fill();
        }
      }

      // شريطا سدو علوي وسفلي
      cardSaduBand(ctx, W, 0, 30, primary, secondary);
      cardSaduBand(ctx, W, H - 30, 30, primary, secondary);

      // إطار ذهبي مزدوج
      ctx.strokeStyle = secondary;
      ctx.lineWidth = 3;
      cardRoundRect(ctx, 48, 76, W - 96, H - 152, 26);
      ctx.stroke();
      ctx.lineWidth = 1.2;
      ctx.strokeStyle = cardHexToRgba(secondary, 0.7);
      cardRoundRect(ctx, 61, 89, W - 122, H - 178, 18);
      ctx.stroke();

      // معينان صغيران يكسران خط الإطار في منتصفه علويًا وسفليًا
      ctx.fillStyle = '#fdfaf1';
      ctx.beginPath();
      ctx.arc(W / 2, 76, 20, 0, Math.PI * 2);
      ctx.fill();
      ctx.beginPath();
      ctx.arc(W / 2, H - 76, 20, 0, Math.PI * 2);
      ctx.fill();
      ctx.fillStyle = secondary;
      cardDiamond(ctx, W / 2, 76, 11);
      ctx.fill();
      cardDiamond(ctx, W / 2, H - 76, 11);
      ctx.fill();

      // اسم الموقع وتحته زخرفة
      ctx.fillStyle = primary;
      ctx.font = cardFont(38, 800);
      ctx.fillText(d.site || '', W / 2, 152, W - 280);
      cardOrnamentDivider(ctx, W / 2, 180, 140, secondary);

      // توسيط المحتوى عموديًا داخل الإطار
      var y = contentTop + Math.max(0, (H - contentTop - bottomReserve - total) / 2);

      // صورة صاحب المناسبة: دائرة بظل ناعم وإطار ذهبي مزدوج
      if (person) {
        var cy2 = y + personR + 14;
        ctx.save();
        ctx.shadowColor = 'rgba(38,48,40,.28)';
        ctx.shadowBlur = 26;
        ctx.shadowOffsetY = 10;
        ctx.beginPath();
        ctx.arc(W / 2, cy2, personR + 14, 0, Math.PI * 2);
        ctx.fillStyle = '#ffffff';
        ctx.fill();
        ctx.restore();
        ctx.lineWidth = 4;
        ctx.strokeStyle = secondary;
        ctx.beginPath();
        ctx.arc(W / 2, cy2, personR + 14, 0, Math.PI * 2);
        ctx.stroke();
        ctx.lineWidth = 1.5;
        ctx.strokeStyle = cardHexToRgba(secondary, 0.7);
        ctx.beginPath();
        ctx.arc(W / 2, cy2, personR + 6, 0, Math.PI * 2);
        ctx.stroke();
        ctx.save();
        ctx.beginPath();
        ctx.arc(W / 2, cy2, personR, 0, Math.PI * 2);
        ctx.clip();
        drawCardCover(ctx, person, W / 2 - personR, cy2 - personR, personR * 2, personR * 2);
        ctx.restore();
        y = cy2 + personR + 14 + 24;
      }

      // رقاقة النوع بتدرج ذهبي
      if (d.type) {
        ctx.font = cardFont(33, 800);
        var chipW = ctx.measureText(d.type).width + 84;
        var chipG = ctx.createLinearGradient(0, y, 0, y + 62);
        chipG.addColorStop(0, '#e0bd6d');
        chipG.addColorStop(1, secondary);
        cardRoundRect(ctx, (W - chipW) / 2, y, chipW, 62, 31);
        ctx.fillStyle = chipG;
        ctx.fill();
        ctx.lineWidth = 1;
        ctx.strokeStyle = 'rgba(122,94,35,.45)';
        ctx.stroke();
        ctx.fillStyle = '#2a220a';
        ctx.fillText(d.type, W / 2, y + 43);
        y += 96;
      }

      // العنوان
      ctx.fillStyle = '#20302a';
      ctx.font = cardFont(58, 800);
      titleLines.forEach(function (line) {
        ctx.fillText(line, W / 2, y + 58);
        y += titleLineH;
      });
      y += 6;

      // فاصل زخرفي
      cardOrnamentDivider(ctx, W / 2, y + 30, 170, secondary);
      y += dividerBlock;

      // لوحة التفاصيل: صندوق شفاف بحد ذهبي وفواصل منقطة
      if (rows.length) {
        var panelX = 140, panelW = W - 280, panelH = rows.length * rowH + 44;
        ctx.save();
        ctx.shadowColor = 'rgba(120,100,50,.10)';
        ctx.shadowBlur = 18;
        ctx.shadowOffsetY = 6;
        cardRoundRect(ctx, panelX, y, panelW, panelH, 22);
        ctx.fillStyle = 'rgba(255,255,255,.6)';
        ctx.fill();
        ctx.restore();
        ctx.lineWidth = 1.5;
        ctx.strokeStyle = cardHexToRgba(secondary, 0.55);
        cardRoundRect(ctx, panelX, y, panelW, panelH, 22);
        ctx.stroke();

        ctx.fillStyle = '#31413a';
        ctx.font = cardFont(40, 700);
        rows.forEach(function (row, i) {
          var ry = y + 22 + i * rowH;
          ctx.fillText(row, W / 2, ry + 54, panelW - 70);
          if (i < rows.length - 1) {
            ctx.save();
            ctx.strokeStyle = cardHexToRgba(secondary, 0.45);
            ctx.setLineDash([2, 8]);
            ctx.lineWidth = 1.5;
            ctx.beginPath();
            ctx.moveTo(panelX + 44, ry + rowH);
            ctx.lineTo(panelX + panelW - 44, ry + rowH);
            ctx.stroke();
            ctx.restore();
          }
        });
        y += panelBlock;
      }

      // رابط الصفحة داخل كبسولة بيضاء بحد ذهبي
      var urlText = (d.url || '').replace(/^https?:\/\//, '').replace(/\/$/, '');
      if (urlText) {
        ctx.font = cardFont(28, 700);
        var uw = Math.min(ctx.measureText(urlText).width + 96, W - 280);
        var uy = H - 168;
        cardRoundRect(ctx, (W - uw) / 2, uy, uw, 58, 29);
        ctx.fillStyle = '#ffffff';
        ctx.fill();
        ctx.lineWidth = 2;
        ctx.strokeStyle = secondary;
        ctx.stroke();
        ctx.fillStyle = primary;
        ctx.fillText(urlText, W / 2, uy + 39, uw - 60);
      }

      return new Promise(function (resolve, reject) {
        canvas.toBlob(function (blob) {
          if (blob) resolve(blob); else reject(new Error('canvas'));
        }, 'image/png');
      });
    });
  }

  // بطاقة تعزية بهوية الموقع: الآية في الأعلى، ثم «انتقل إلى رحمة الله» واسم المتوفى
  function buildObituaryCard(d) {
    var theme = cardThemeColors();
    var primary = theme.primary;
    var secondary = theme.secondary;
    var W = 1080;
    var verse = 'يَا أَيَّتُهَا النَّفْسُ الْمُطْمَئِنَّةُ ارْجِعِي إِلَىٰ رَبِّكِ رَاضِيَةً مَّرْضِيَّةً';

    var fontsReady = ensureCardFontsLoaded();

    return fontsReady.then(function () {
      var rows = [];
      if (d.date) rows.push('🗓  ' + d.date);
      if (d.city) rows.push('🏙  ' + d.city);
      if (d.venue) rows.push('📍  ' + d.venue);

      var nameLineH = 78;
      var verseLineH = 52;
      var dividerBlock = 72;
      var rowH = 80;
      var panelBlock = rows.length ? rows.length * rowH + 48 : 0;

      var scratch = document.createElement('canvas').getContext('2d');
      scratch.font = cardFont(56, 800);
      var nameLines = wrapCardText(scratch, d.name, W - 280).slice(0, 2);
      var nameBlock = nameLines.length * nameLineH + 6;
      scratch.font = cardFont(34, 700);
      var verseLines = wrapCardText(scratch, verse, W - 320);
      var verseBlock = verseLines.length * verseLineH + 40;

      var eulogyBlock = 78;
      var total = verseBlock + eulogyBlock + nameBlock + dividerBlock + panelBlock;
      var contentTop = 216, bottomReserve = 210;
      var H = 1250;
      if (total > H - contentTop - bottomReserve) {
        H = contentTop + bottomReserve + total;
      }

      var canvas = document.createElement('canvas');
      canvas.width = W;
      canvas.height = H;
      var ctx = canvas.getContext('2d');
      ctx.textAlign = 'center';
      try { ctx.direction = 'rtl'; } catch (e) {}

      var bg = ctx.createLinearGradient(0, 0, 0, H);
      bg.addColorStop(0, '#fdfaf1');
      bg.addColorStop(1, '#f5eedb');
      ctx.fillStyle = bg;
      ctx.fillRect(0, 0, W, H);
      var glow = ctx.createRadialGradient(W / 2, 0, 60, W / 2, 0, H * 0.55);
      glow.addColorStop(0, cardHexToRgba(primary, 0.07));
      glow.addColorStop(1, 'rgba(0,0,0,0)');
      ctx.fillStyle = glow;
      ctx.fillRect(0, 0, W, H);

      ctx.fillStyle = cardHexToRgba(primary, 0.04);
      for (var px = 30; px < W; px += 46) {
        for (var py = 56; py < H - 44; py += 46) {
          ctx.beginPath();
          ctx.arc(px, py, 2.2, 0, Math.PI * 2);
          ctx.fill();
        }
      }

      cardSaduBand(ctx, W, 0, 30, primary, secondary);
      cardSaduBand(ctx, W, H - 30, 30, primary, secondary);

      ctx.strokeStyle = secondary;
      ctx.lineWidth = 3;
      cardRoundRect(ctx, 48, 76, W - 96, H - 152, 26);
      ctx.stroke();
      ctx.lineWidth = 1.2;
      ctx.strokeStyle = cardHexToRgba(secondary, 0.7);
      cardRoundRect(ctx, 61, 89, W - 122, H - 178, 18);
      ctx.stroke();

      ctx.fillStyle = '#fdfaf1';
      ctx.beginPath();
      ctx.arc(W / 2, 76, 20, 0, Math.PI * 2);
      ctx.fill();
      ctx.beginPath();
      ctx.arc(W / 2, H - 76, 20, 0, Math.PI * 2);
      ctx.fill();
      ctx.fillStyle = secondary;
      cardDiamond(ctx, W / 2, 76, 11);
      ctx.fill();
      cardDiamond(ctx, W / 2, H - 76, 11);
      ctx.fill();

      ctx.fillStyle = primary;
      ctx.font = cardFont(38, 800);
      ctx.fillText(d.site || '', W / 2, 152, W - 280);
      cardOrnamentDivider(ctx, W / 2, 180, 140, secondary);

      var y = contentTop + Math.max(0, (H - contentTop - bottomReserve - total) / 2);

      ctx.fillStyle = primary;
      ctx.font = cardFont(34, 700);
      verseLines.forEach(function (line) {
        ctx.fillText(line, W / 2, y + 44, W - 260);
        y += verseLineH;
      });
      y += 40;

      ctx.fillStyle = secondary;
      ctx.font = cardFont(40, 800);
      ctx.fillText('انتقل إلى رحمة الله', W / 2, y + 34, W - 280);
      y += eulogyBlock;

      ctx.fillStyle = '#20302a';
      ctx.font = cardFont(56, 800);
      nameLines.forEach(function (line) {
        ctx.fillText(line, W / 2, y + 54, W - 200);
        y += nameLineH;
      });
      y += 6;

      cardOrnamentDivider(ctx, W / 2, y + 30, 170, secondary);
      y += dividerBlock;

      if (rows.length) {
        var panelX = 140, panelW = W - 280, panelH = rows.length * rowH + 44;
        ctx.save();
        ctx.shadowColor = 'rgba(120,100,50,.10)';
        ctx.shadowBlur = 18;
        ctx.shadowOffsetY = 6;
        cardRoundRect(ctx, panelX, y, panelW, panelH, 22);
        ctx.fillStyle = 'rgba(255,255,255,.6)';
        ctx.fill();
        ctx.restore();
        ctx.lineWidth = 1.5;
        ctx.strokeStyle = cardHexToRgba(secondary, 0.55);
        cardRoundRect(ctx, panelX, y, panelW, panelH, 22);
        ctx.stroke();

        ctx.fillStyle = '#31413a';
        ctx.font = cardFont(40, 700);
        rows.forEach(function (row, i) {
          var ry = y + 22 + i * rowH;
          ctx.fillText(row, W / 2, ry + 54, panelW - 70);
          if (i < rows.length - 1) {
            ctx.save();
            ctx.strokeStyle = cardHexToRgba(secondary, 0.45);
            ctx.setLineDash([2, 8]);
            ctx.lineWidth = 1.5;
            ctx.beginPath();
            ctx.moveTo(panelX + 44, ry + rowH);
            ctx.lineTo(panelX + panelW - 44, ry + rowH);
            ctx.stroke();
            ctx.restore();
          }
        });
        y += panelBlock;
      }

      var urlText = (d.url || '').replace(/^https?:\/\//, '').replace(/\/$/, '');
      if (urlText) {
        ctx.font = cardFont(28, 700);
        var uw = Math.min(ctx.measureText(urlText).width + 96, W - 280);
        var uy = H - 168;
        cardRoundRect(ctx, (W - uw) / 2, uy, uw, 58, 29);
        ctx.fillStyle = '#ffffff';
        ctx.fill();
        ctx.lineWidth = 2;
        ctx.strokeStyle = secondary;
        ctx.stroke();
        ctx.fillStyle = primary;
        ctx.fillText(urlText, W / 2, uy + 39, uw - 60);
      }

      return new Promise(function (resolve, reject) {
        canvas.toBlob(function (blob) {
          if (blob) resolve(blob); else reject(new Error('canvas'));
        }, 'image/png');
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
