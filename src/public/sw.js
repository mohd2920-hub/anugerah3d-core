const CACHE_NAME = 'anugerah3d-agent-v1';
const STATIC_ASSETS = [
    '/icons/agent-app.svg',
    '/icons/agent-app-192.png',
    '/icons/agent-app-512.png'
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;

    const url = new URL(event.request.url);
    if (url.origin !== self.location.origin) return;

    if (STATIC_ASSETS.includes(url.pathname) || url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.match(event.request).then((cached) => cached || fetch(event.request).then((response) => {
                const copy = response.clone();
                caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));
                return response;
            }))
        );
        return;
    }

    if (event.request.mode === 'navigate') {
        event.respondWith(fetch(event.request).catch(() => new Response(`<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#17324d"><title>Offline | A3D Agent</title><style>body{margin:0;background:#eef3f6;color:#17324d;font-family:system-ui;display:grid;min-height:100vh;place-items:center}.card{margin:24px;max-width:340px;text-align:center;background:#fff;border-radius:28px;padding:36px 24px;box-shadow:0 15px 40px #17324d18}.logo{display:grid;margin:auto;width:58px;height:58px;place-items:center;border-radius:18px;background:#17324d;color:#fff;font-weight:900}button{border:0;border-radius:14px;padding:13px 20px;background:#e7682b;color:white;font-weight:700}</style></head><body><main class="card"><div class="logo">A3D</div><h1>You are offline</h1><p>Reconnect to the internet to securely access the latest agent information.</p><button onclick="location.reload()">Try again</button></main></body></html>`, {headers: {'Content-Type': 'text/html'}})));
    }
});
