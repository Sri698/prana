/**
 * PRANA HOMEO CARE — SERVICE WORKER
 * High Performance Offline Clinical PWA Cache
 */

const CACHE_NAME = 'prana-clinical-v4';
const ASSETS_TO_CACHE = [
  'case.html',
  'clinical-config.js',
  'manifest.json',
  'favicon.png',
  'paediatric-care.html',
  'cancer-palliative-care.html',
  'assets/paediatric_child_smiling.png',
  'assets/paediatric_child_steps.png',
  'assets/cancer_palliative_care.jpg',
  'assets/prana_brand_logo.png',
  'assets/prana_emblem.png',
  'assets/panda_front_cropped.png',
  'vendor/react.production.min.js',
  'vendor/react-dom.production.min.js',
  'vendor/tailwindcss.js',
  'vendor/all.min.css',
  'vendor/crypto-js.min.js',
  'vendor/jspdf.umd.min.js'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE).catch(err => {
        console.warn('Cache addAll non-fatal warning:', err);
      });
    }).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
      );
    }).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  // Pass Firebase & Supabase cloud API requests directly to network
  if (
    event.request.url.includes('firebaseio.com') ||
    event.request.url.includes('googleapis.com') ||
    event.request.url.includes('supabase.co') ||
    event.request.method !== 'GET'
  ) {
    return;
  }

  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      if (cachedResponse) {
        return cachedResponse;
      }
      return fetch(event.request).then((networkResponse) => {
        // Cache external CDN scripts (Tailwind, React, FontAwesome, etc.)
        if (
          networkResponse &&
          networkResponse.status === 200 &&
          (event.request.url.includes('cdn') ||
           event.request.url.includes('unpkg') ||
           event.request.url.includes('fonts.gstatic.com'))
        ) {
          const clone = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
        }
        return networkResponse;
      }).catch(() => {
        // Fallback for case.html
        if (event.request.mode === 'navigate') {
          return caches.match('case.html');
        }
      });
    })
  );
});
