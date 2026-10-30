/**
 * PRANA HOMEO CARE — ADVANCED CLINICAL PWA SERVICE WORKER
 * Ultra-Reliable Offline Shell, Smart Asset Caching & Desktop PWA Engine
 */

const CACHE_NAME = 'prana-pwa-v5.2';

const CRITICAL_APP_SHELL = [
  './',
  'index.html',
  'case.html',
  'manifest.json',
  'favicon.png',
  'favicon.ico',
  'pwa-icon-192.png',
  'pwa-icon-512.png',
  'pwa-icon-maskable.png',
  'pwa-screenshot-desktop.png',
  'pwa-screenshot-mobile.png'
];

// Optional assets cached opportunistically
const OPTIONAL_ASSETS = [
  'assets/prana_brand_logo.png',
  'assets/prana_brand_logo.svg',
  'assets/prana_emblem.png',
  'assets/prana_header_logo.png',
  'clinical-config.js',
  'vendor/react.production.min.js',
  'vendor/react-dom.production.min.js',
  'vendor/tailwindcss.js',
  'vendor/all.min.css'
];

// 1. INSTALL: Cache Critical App Shell
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      // Pre-cache critical files, ignore failures on non-existent optional assets
      return Promise.allSettled(
        CRITICAL_APP_SHELL.map((url) =>
          cache.add(url).catch((err) => console.log('PWA shell cache notice:', url, err.message))
        )
      );
    }).then(() => self.skipWaiting())
  );
});

// 2. ACTIVATE: Purge stale versions & claim active clients
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
      );
    }).then(() => self.clients.claim())
  );
});

// 3. FETCH: Smart Network-First with Offline Cache Fallback
self.addEventListener('fetch', (event) => {
  const req = event.request;
  const url = new URL(req.url);

  // Skip non-GET requests (e.g. POST form submissions, API CRUD)
  if (req.method !== 'GET') {
    return;
  }

  // Skip backend API endpoints and cloud sync (always network)
  if (
    url.pathname.startsWith('/api') ||
    url.pathname.includes('cases.php') ||
    url.hostname.includes('firebaseio.com') ||
    url.hostname.includes('googleapis.com') ||
    url.hostname.includes('supabase.co')
  ) {
    return;
  }

  // A. Navigation requests (HTML pages): Network first, fallback to cached HTML
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req)
        .then((networkRes) => {
          if (networkRes && networkRes.status === 200) {
            const copy = networkRes.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
          }
          return networkRes;
        })
        .catch(async () => {
          const cached = await caches.match(req);
          if (cached) return cached;
          const rootCached = await caches.match('./') || await caches.match('index.html') || await caches.match('case.html');
          return rootCached;
        })
    );
    return;
  }

  // B. Static Assets (Fonts, Google Fonts, Images, Scripts, CSS): Cache First with background refresh
  event.respondWith(
    caches.match(req).then((cachedRes) => {
      if (cachedRes) {
        // Opportunistic background update for cached assets
        fetch(req).then((freshRes) => {
          if (freshRes && freshRes.status === 200) {
            caches.open(CACHE_NAME).then((cache) => cache.put(req, freshRes));
          }
        }).catch(() => {});
        return cachedRes;
      }

      return fetch(req).then((networkRes) => {
        if (
          networkRes &&
          networkRes.status === 200 &&
          (url.pathname.endsWith('.png') ||
           url.pathname.endsWith('.jpg') ||
           url.pathname.endsWith('.jpeg') ||
           url.pathname.endsWith('.svg') ||
           url.pathname.endsWith('.css') ||
           url.pathname.endsWith('.js') ||
           url.pathname.endsWith('.woff2') ||
           url.hostname.includes('fonts.googleapis.com') ||
           url.hostname.includes('fonts.gstatic.com') ||
           url.hostname.includes('cdnjs.cloudflare.com'))
        ) {
          const copy = networkRes.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(req, copy));
        }
        return networkRes;
      }).catch((err) => {
        // Return placeholder or fallback if available
        return cachedRes;
      });
    })
  );
});
