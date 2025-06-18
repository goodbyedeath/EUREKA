// public/serviceworker.js
const CACHE_NAME = 'laravel-pwa-v1';
const OFFLINE_URL = '/offline.html';

// Files to cache for offline functionality
const CACHE_URLS = [
    '/',
    '/build/assets/app-DCRMsj8y.css',
    '/build/assets/app-BHVf6n3-.js',
    '/logo.png',
    '/manifest.json',
    OFFLINE_URL
];

// Install event - cache essential files
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Caching essential files');
                return cache.addAll(CACHE_URLS);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch event - serve cached content when offline
self.addEventListener('fetch', event => {
    // Skip cross-origin requests
    if (!event.request.url.startsWith(self.location.origin)) {
        return;
    }

    // Handle navigation requests
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .catch(() => {
                    return caches.open(CACHE_NAME)
                        .then(cache => cache.match(OFFLINE_URL));
                })
        );
        return;
    }

    // Handle other requests with cache-first strategy
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Return cached version or fetch from network
                return response || fetch(event.request)
                    .then(fetchResponse => {
                        // Cache successful responses
                        if (fetchResponse.status === 200) {
                            const responseClone = fetchResponse.clone();
                            caches.open(CACHE_NAME)
                                .then(cache => {
                                    cache.put(event.request, responseClone);
                                });
                        }
                        return fetchResponse;
                    });
            })
            .catch(() => {
                // Return offline page for navigation requests
                if (event.request.mode === 'navigate') {
                    return caches.match(OFFLINE_URL);
                }
            })
    );
});