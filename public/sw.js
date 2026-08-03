const CACHE_NAME = 'wms-listrindo-v2';
const OFFLINE_URL = '/offline';

const urlsToCache = [
  '/',
  '/offline',
];

// 1. INSTALL EVENT - Robust caching with allSettled
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return Promise.allSettled(
        urlsToCache.map(url => 
          cache.add(url).catch(err => {
            console.warn(`[SW] Gagal cache ${url}:`, err.message);
            return null;
          })
        )
      );
    }).then(() => self.skipWaiting())
  );
});

// 2. ACTIVATE EVENT - Clean old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter(name => name !== CACHE_NAME)
          .map(name => caches.delete(name))
      );
    }).then(() => self.clients.claim())
  );
});

// 3. FETCH EVENT - Ensure valid Response object is always returned
self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;
  if (!event.request.url.startsWith(self.location.origin)) return;
  
  event.respondWith(
    fetch(event.request)
      .then(response => {
        if (!response || response.status === 0) {
          throw new Error('Invalid response');
        }
        return response;
      })
      .catch(async (error) => {
        console.warn('[SW] Fetch failed:', error.message);
        
        // Try cache fallback
        const cached = await caches.match(event.request);
        if (cached) return cached;
        
        // Navigation fallback
        if (event.request.mode === 'navigate') {
          const offlinePage = await caches.match(OFFLINE_URL);
          if (offlinePage) return offlinePage;
        }
        
        // LAST RESORT: Valid JSON Response instead of rejecting promise
        return new Response(
          JSON.stringify({ 
            error: 'Offline', 
            message: 'Tidak ada koneksi internet atau server error' 
          }),
          {
            status: 503,
            statusText: 'Service Unavailable',
            headers: new Headers({ 'Content-Type': 'application/json' })
          }
        );
      })
  );
});
