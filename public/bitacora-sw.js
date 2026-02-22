const CACHE_NAME = 'bitacora-v4';
const ASSETS = [
  '/bitacora',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
  '/sounds/alert.mp3',
  '/img/icons/icon-192x192.png',
  '/img/icons/icon-512x512.png'
];

// Install: cache assets
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(ASSETS))
  );
  self.skipWaiting();
});

// Activate: clean old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    )
  );
  self.clients.claim();
});

// Fetch: network first, fallback to cache
self.addEventListener('fetch', event => {
  // Skip non-GET
  if (event.request.method !== 'GET') return;

  event.respondWith(
    fetch(event.request)
      .then(response => {
        // Cache successful responses
        if (response.status === 200) {
          const clone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
        }
        return response;
      })
      .catch(() => caches.match(event.request))
  );
});

// ---- Notificaciones desde la página ----
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'mostrar-alerta') {
    self.registration.showNotification(event.data.title || 'Bitácora Cycloid', {
      body: event.data.body || 'Actividad en progreso',
      icon: '/img/icons/icon-192x192.png',
      badge: '/img/icons/icon-72x72.png',
      vibrate: [300, 100, 300, 100, 300],
      tag: 'bitacora-30min',
      renotify: true,
      requireInteraction: true
    });
  }
});

// ---- Push desde el servidor (Web Push API) ----
self.addEventListener('push', event => {
  let data = { title: 'Bitácora Cycloid', body: 'Actividad en progreso', url: '/bitacora' };
  try {
    if (event.data) data = Object.assign(data, event.data.json());
  } catch (e) {}

  event.waitUntil(
    self.registration.showNotification(data.title, {
      body: data.body,
      icon: '/img/icons/icon-192x192.png',
      badge: '/img/icons/icon-72x72.png',
      vibrate: [300, 100, 300, 100, 300],
      tag: 'bitacora-push',
      renotify: true,
      requireInteraction: true,
      data: { url: data.url || '/bitacora' }
    })
  );
});

// Clic en notificación: abrir la bitácora
self.addEventListener('notificationclick', event => {
  event.notification.close();
  const url = (event.notification.data && event.notification.data.url) || '/bitacora';
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clientList => {
      for (const client of clientList) {
        if (client.url.includes('/bitacora') && 'focus' in client) {
          return client.focus();
        }
      }
      return clients.openWindow(url);
    })
  );
});
