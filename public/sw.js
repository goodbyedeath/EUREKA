// Service Worker for Laravel PWA
const CACHE_NAME = 'eureka-pwa-v2';
const OFFLINE_URL = '/offline.html';

// Essential files to cache (only files that definitely exist)
const ESSENTIAL_CACHE_URLS = [
    '/',
    '/offline.html',
    '/manifest.json'
];

// Install event - cache only essential files that exist
self.addEventListener('install', event => {
    console.log('Service Worker installing...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Caching essential files');
                // Cache files one by one to avoid addAll failures
                return Promise.allSettled(
                    ESSENTIAL_CACHE_URLS.map(url => 
                        fetch(url)
                            .then(response => {
                                if (response.ok) {
                                    return cache.put(url, response);
                                }
                                console.warn(`Failed to cache: ${url}`);
                            })
                            .catch(error => {
                                console.warn(`Error caching ${url}:`, error);
                            })
                    )
                );
            })
            .then(() => {
                console.log('Service Worker installed successfully');
                return self.skipWaiting();
            })
            .catch(error => {
                console.error('Service Worker installation failed:', error);
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    console.log('Service Worker activating...');
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (cacheName !== CACHE_NAME) {
                            console.log('Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('Service Worker activated');
                return self.clients.claim();
            })
    );
});

// Fetch event - handle requests appropriately
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);

    // Skip cross-origin requests
    if (url.origin !== self.location.origin) {
        return;
    }

    // Skip non-GET requests entirely (POST, PUT, DELETE, etc.)
    if (request.method !== 'GET') {
        return;
    }

    // Skip API routes and admin routes
    if (url.pathname.startsWith('/api/') || 
        url.pathname.startsWith('/admin/') ||
        url.pathname.startsWith('/livewire/')) {
        return;
    }

    // Handle navigation requests (page loads)
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then(response => {
                    // Cache successful page responses
                    if (response.ok) {
                        const responseClone = response.clone();
                        caches.open(CACHE_NAME)
                            .then(cache => cache.put(request, responseClone))
                            .catch(error => console.warn('Failed to cache navigation:', error));
                    }
                    return response;
                })
                .catch(() => {
                    // Return offline page when network fails
                    return caches.open(CACHE_NAME)
                        .then(cache => cache.match(OFFLINE_URL))
                        .catch(() => new Response('Offline', { status: 503 }));
                })
        );
        return;
    }

    // Handle static assets (CSS, JS, images, etc.)
    if (request.destination === 'style' || 
        request.destination === 'script' || 
        request.destination === 'image' ||
        request.destination === 'font') {
        
        event.respondWith(
            caches.match(request)
                .then(cachedResponse => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    
                    // Fetch and cache the asset
                    return fetch(request)
                        .then(response => {
                            if (response.ok) {
                                const responseClone = response.clone();
                                caches.open(CACHE_NAME)
                                    .then(cache => cache.put(request, responseClone))
                                    .catch(error => console.warn('Failed to cache asset:', error));
                            }
                            return response;
                        });
                })
                .catch(error => {
                    console.warn('Asset fetch failed:', error);
                    return new Response('Asset not available', { status: 404 });
                })
        );
        return;
    }

    // For other GET requests, try network first
    event.respondWith(
        fetch(request)
            .catch(() => {
                // If network fails, try cache
                return caches.match(request);
            })
    );
});

// Handle skip waiting message from app
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});