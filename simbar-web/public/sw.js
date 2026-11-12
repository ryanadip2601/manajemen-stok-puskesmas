// SIMBAR service worker: makes the app installable, keeps the app shell available offline,
// shows push notifications and the number on the app icon. Data (Supabase, /api) is never cached.
const SHELL = 'simbar-shell-v2';

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(SHELL).then((cache) => cache.addAll(['/', '/manifest.webmanifest', '/icons/icon-192.png'])).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  event.waitUntil(caches.keys()
    .then((names) => Promise.all(names.filter((name) => name !== SHELL).map((name) => caches.delete(name))))
    .then(() => self.clients.claim()));
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);
  if (request.method !== 'GET' || url.origin !== self.location.origin || url.pathname.startsWith('/api/')) return;
  if (request.mode === 'navigate') {
    // Pages: always try the network first so a new version shows up straight away.
    event.respondWith(fetch(request).then((response) => {
      const copy = response.clone();
      caches.open(SHELL).then((cache) => cache.put('/', copy));
      return response;
    }).catch(() => caches.match('/')));
    return;
  }
  if (url.pathname.startsWith('/assets/') || url.pathname.startsWith('/icons/')) {
    // Built files have content hashes in their names, so a cached copy is always correct.
    event.respondWith(caches.match(request).then((cached) => cached || fetch(request).then((response) => {
      if (response.ok) { const copy = response.clone(); caches.open(SHELL).then((cache) => cache.put(request, copy)); }
      return response;
    })));
  }
});

self.addEventListener('push', (event) => {
  let data = {};
  try { data = event.data ? event.data.json() : {}; } catch { data = { body: event.data?.text() }; }
  const title = data.title || 'SIMBAR';
  // When SIMBAR is open on screen it plays its own "ting", so the system notification stays quiet.
  const shown = self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
    const open = windows.filter((client) => client.visibilityState === 'visible');
    open.forEach((client) => client.postMessage({ type: 'simbar-push', tag: data.tag || 'simbar' }));
    return self.registration.showNotification(title, {
      body: data.body || '',
      icon: '/icons/icon-192.png',
      badge: '/icons/badge-96.png',
      tag: data.tag || 'simbar',
      renotify: true,
      silent: open.length > 0,
      data: { url: data.url || '/' },
      vibrate: open.length > 0 ? undefined : [80, 40, 80],
    });
  });
  const tasks = [shown];
  if (typeof data.badge === 'number' && 'setAppBadge' in self.navigator) {
    tasks.push(data.badge > 0 ? self.navigator.setAppBadge(data.badge) : self.navigator.clearAppBadge());
  }
  event.waitUntil(Promise.all(tasks).catch(() => {}));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const target = new URL(event.notification.data?.url || '/', self.location.origin).href;
  event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
    for (const client of windows) {
      if (new URL(client.url).origin === self.location.origin) {
        client.postMessage({ type: 'simbar-open', url: target });
        return client.focus();
      }
    }
    return self.clients.openWindow(target);
  }));
});
