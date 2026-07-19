/*
 * Service Worker بسيط: يخزّن الأصول الثابتة (القالب والوسائط) للعمل السريع
 * والمحدود دون اتصال، ويترك الصفحات الديناميكية ولوحة التحكم دائمًا طازجة
 * من الشبكة أولًا. لا يعتمد على مسار ثابت، بل يكتشف مجلد التركيب تلقائيًا
 * ليعمل سواء كان النظام في الجذر أو داخل مجلد فرعي.
 */

var CACHE_NAME = 'fam-cache-v2';
var BASE_PATH = self.location.pathname.replace(/sw\.js$/, '');

self.addEventListener('install', function (event) {
  self.skipWaiting();
});

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(
        keys.filter(function (key) { return key !== CACHE_NAME; })
            .map(function (key) { return caches.delete(key); })
      );
    }).then(function () { return self.clients.claim(); })
  );
});

function isCacheableAsset(pathname) {
  return pathname.indexOf(BASE_PATH + 'themes/') === 0
      || pathname.indexOf(BASE_PATH + 'storage/uploads/') === 0
      || pathname.indexOf(BASE_PATH + 'admin/assets/') === 0;
}

// إشعارات Push: عرض الإشعار الوارد وفتح الرابط عند الضغط عليه
self.addEventListener('push', function (event) {
  var data = {};
  try { data = event.data ? event.data.json() : {}; } catch (e) {}
  event.waitUntil(
    self.registration.showNotification(data.title || 'إشعار جديد', {
      body: data.body || '',
      icon: data.icon || (BASE_PATH + 'pwa-icon-192.png'),
      badge: data.icon || (BASE_PATH + 'pwa-icon-192.png'),
      dir: 'rtl',
      lang: 'ar',
      data: { url: data.url || BASE_PATH }
    })
  );
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  var url = (event.notification.data && event.notification.data.url) || BASE_PATH;
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (list) {
      for (var i = 0; i < list.length; i++) {
        if (list[i].url === url && 'focus' in list[i]) return list[i].focus();
      }
      return clients.openWindow(url);
    })
  );
});

self.addEventListener('fetch', function (event) {
  var request = event.request;
  if (request.method !== 'GET') return;

  var url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  // لوحة التحكم ومعالج التثبيت: دائمًا من الشبكة مباشرة، بلا أي تخزين مؤقت
  if (url.pathname.indexOf(BASE_PATH + 'admin/') === 0 && url.pathname.indexOf(BASE_PATH + 'admin/assets/') !== 0) return;
  if (url.pathname.indexOf(BASE_PATH + 'install/') === 0) return;

  if (isCacheableAsset(url.pathname)) {
    // الأصول الثابتة: من التخزين المؤقت أولًا لسرعة أعلى، مع تحديثها في الخلفية
    event.respondWith(
      caches.open(CACHE_NAME).then(function (cache) {
        return cache.match(request).then(function (cached) {
          var networked = fetch(request).then(function (response) {
            if (response && response.ok) cache.put(request, response.clone());
            return response;
          }).catch(function () { return cached; });
          return cached || networked;
        });
      })
    );
    return;
  }

  if (request.mode === 'navigate') {
    // صفحات الموقع العام: الشبكة أولًا، مع نسخة مخزنة كحل احتياطي عند انقطاع الاتصال
    event.respondWith(
      fetch(request).then(function (response) {
        if (response && response.ok) {
          caches.open(CACHE_NAME).then(function (cache) { cache.put(request, response.clone()); });
        }
        return response;
      }).catch(function () {
        return caches.match(request).then(function (cached) {
          return cached || caches.match(BASE_PATH);
        });
      })
    );
  }
});
