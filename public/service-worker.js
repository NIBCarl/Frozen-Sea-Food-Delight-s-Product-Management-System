// Service Worker for Frozen Seafood System - PWA Implementation
const CACHE_NAME = 'seafood-v1';
const API_CACHE = 'seafood-api-v1';

// Assets to cache on install
const STATIC_ASSETS = [
  '/',
  '/index.html',
  '/css/app.css',
  '/js/app.js',
  '/images/logo.png',
  '/images/placeholder-product.jpg',
];

// API routes to cache
const API_ROUTES = [
  '/api/v1/products',
  '/api/v1/categories',
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
  console.log('[Service Worker] Installing...');
  
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('[Service Worker] Caching static assets');
        return cache.addAll(STATIC_ASSETS);
      })
      .then(() => self.skipWaiting())
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  console.log('[Service Worker] Activating...');
  
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME && cacheName !== API_CACHE) {
            console.log('[Service Worker] Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Handle API requests
  if (url.pathname.startsWith('/api/')) {
    event.respondWith(handleAPIRequest(request));
    return;
  }

  // Handle static assets
  event.respondWith(
    caches.match(request)
      .then((cachedResponse) => {
        if (cachedResponse) {
          return cachedResponse;
        }

        return fetch(request).then((response) => {
          // Cache successful responses
          if (response && response.status === 200) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(request, responseClone);
            });
          }
          return response;
        });
      })
      .catch(() => {
        // Return offline page if available
        if (request.mode === 'navigate') {
          return caches.match('/offline.html');
        }
      })
  );
});

// Handle API requests - Network first, fallback to cache
async function handleAPIRequest(request) {
  const url = new URL(request.url);
  
  // Only cache GET requests
  if (request.method !== 'GET') {
    return fetch(request);
  }

  // Try network first
  try {
    const response = await fetch(request);
    
    // Cache successful responses for products and categories
    if (response && response.status === 200) {
      if (shouldCacheAPI(url.pathname)) {
        const responseClone = response.clone();
        const cache = await caches.open(API_CACHE);
        await cache.put(request, responseClone);
      }
    }
    
    return response;
  } catch (error) {
    // Network failed, try cache
    console.log('[Service Worker] Network request failed, trying cache');
    const cachedResponse = await caches.match(request);
    
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Return offline response
    return new Response(
      JSON.stringify({
        success: false,
        message: 'Offline - Data not available',
        offline: true
      }),
      {
        status: 503,
        headers: { 'Content-Type': 'application/json' }
      }
    );
  }
}

// Check if API endpoint should be cached
function shouldCacheAPI(pathname) {
  return API_ROUTES.some(route => pathname.startsWith(route));
}

// Background sync for offline orders (future enhancement)
self.addEventListener('sync', (event) => {
  console.log('[Service Worker] Background sync:', event.tag);
  
  if (event.tag === 'sync-orders') {
    event.waitUntil(syncOrders());
  }
});

// Sync orders when back online
async function syncOrders() {
  console.log('[Service Worker] Syncing offline orders...');
  
  // Get pending orders from IndexedDB or localStorage
  // Send them to the server
  // This is a placeholder for future implementation
  
  return Promise.resolve();
}

// Push notifications (future enhancement)
self.addEventListener('push', (event) => {
  if (!event.data) return;

  const data = event.data.json();
  const options = {
    body: data.body,
    icon: '/images/logo.png',
    badge: '/images/badge.png',
    data: data.url,
  };

  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

// Notification click handler
self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  event.waitUntil(
    clients.openWindow(event.notification.data)
  );
});

console.log('[Service Worker] Loaded successfully');

