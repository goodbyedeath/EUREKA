// Enhanced Service Worker for Laravel PWA with Offline Support
const CACHE_NAME = 'eureka-pwa-v4-enhanced';
const OFFLINE_URL = '/offline.html';
const API_CACHE_NAME = 'eureka-api-cache-v1';
const LIVEWIRE_CACHE_NAME = 'eureka-livewire-cache-v1';

// Essential files to cache (only files that definitely exist)
const ESSENTIAL_CACHE_URLS = [
    '/',
    '/offline.html',
    '/manifest.json',
    '/css/app.css',
    '/js/app.js'
];

// Critical routes that should work offline
const OFFLINE_CAPABLE_ROUTES = [
    '/',
    '/user/dashboard',
    '/user/quest-locations',
    '/quiz/',
    '/team-registration',
    '/kiosk/led',
    '/team-member-view',
    '/test-offline'
];

// API endpoints to cache for offline functionality
const CACHEABLE_API_ROUTES = [
    '/api/kiosk/data',
    '/api/kiosk/leaderboard',
    '/api/live/positions',
    '/livewire/update'  // For basic Livewire functionality
];

// Install event - cache essential files and setup IndexedDB
self.addEventListener('install', event => {
    console.log('Enhanced Service Worker installing...');
    event.waitUntil(
        Promise.all([
            // Cache essential files
            caches.open(CACHE_NAME).then(cache => {
                console.log('Caching essential files');
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
            }),
            // Initialize API cache
            caches.open(API_CACHE_NAME),
            // Initialize Livewire cache
            caches.open(LIVEWIRE_CACHE_NAME),
            // Setup IndexedDB
            setupIndexedDB()
        ]).then(() => {
            console.log('Enhanced Service Worker installed successfully');
            return self.skipWaiting();
        }).catch(error => {
            console.error('Enhanced Service Worker installation failed:', error);
        })
    );
});

// Setup IndexedDB for offline data storage
async function setupIndexedDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('EurekaOfflineDB', 2);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            
            // Store for user data
            if (!db.objectStoreNames.contains('userData')) {
                db.createObjectStore('userData', { keyPath: 'id' });
            }
            
            // Store for quiz data
            if (!db.objectStoreNames.contains('quizData')) {
                db.createObjectStore('quizData', { keyPath: 'id' });
            }
            
            // Store for team data
            if (!db.objectStoreNames.contains('teamData')) {
                db.createObjectStore('teamData', { keyPath: 'id' });
            }
            
            // Store for offline submissions
            if (!db.objectStoreNames.contains('offlineSubmissions')) {
                const store = db.createObjectStore('offlineSubmissions', { 
                    keyPath: 'id', 
                    autoIncrement: true 
                });
                store.createIndex('timestamp', 'timestamp');
                store.createIndex('type', 'type');
            }
            
            console.log('IndexedDB setup complete');
        };
    });
}

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    console.log('Enhanced Service Worker activating...');
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (cacheName !== CACHE_NAME && 
                            cacheName !== API_CACHE_NAME && 
                            cacheName !== LIVEWIRE_CACHE_NAME) {
                            console.log('Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('Enhanced Service Worker activated');
                return self.clients.claim();
            })
    );
});

// Enhanced fetch event handler
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);

    // Skip cross-origin requests
    if (url.origin !== self.location.origin) {
        return;
    }

    // Handle different types of requests
    if (request.method === 'GET') {
        event.respondWith(handleGetRequest(request, url));
    } else if (request.method === 'POST') {
        event.respondWith(handlePostRequest(request, url));
    }
});

// Handle GET requests with enhanced caching
async function handleGetRequest(request, url) {
    try {
        // Handle navigation requests (page loads)
        if (request.mode === 'navigate') {
            return await handleNavigationRequest(request, url);
        }

        // Handle API requests
        if (url.pathname.startsWith('/api/')) {
            return await handleApiRequest(request, url);
        }

        // Handle Livewire requests
        if (url.pathname.startsWith('/livewire/')) {
            return await handleLivewireRequest(request, url);
        }

        // Handle static assets
        if (request.destination === 'style' || 
            request.destination === 'script' || 
            request.destination === 'image' ||
            request.destination === 'font') {
            return await handleAssetRequest(request);
        }

        // Default: network first, cache fallback
        return await networkFirstStrategy(request, CACHE_NAME);

    } catch (error) {
        console.error('Error handling GET request:', error);
        return new Response('Service unavailable', { status: 503 });
    }
}

// Handle navigation requests with offline fallback
async function handleNavigationRequest(request, url) {
    try {
        const response = await fetch(request);
        
        if (response.ok) {
            // Cache successful page responses
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
            return response;
        }
        
        throw new Error('Network response not ok');
    } catch (error) {
        // Try to return cached version
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Check if this is an offline-capable route
        const isOfflineCapable = OFFLINE_CAPABLE_ROUTES.some(route => 
            url.pathname === route || url.pathname.startsWith(route)
        );
        
        if (isOfflineCapable) {
            // Return offline-capable page content
            return await createOfflineResponse(url.pathname);
        }
        
        // Return offline page
        const offlineResponse = await caches.match(OFFLINE_URL);
        return offlineResponse || new Response('Offline', { status: 503 });
    }
}

// Handle API requests with caching
async function handleApiRequest(request, url) {
    try {
        const response = await fetch(request);
        
        if (response.ok) {
            // Cache API responses for offline use
            const cache = await caches.open(API_CACHE_NAME);
            cache.put(request, response.clone());
            
            // Also store in IndexedDB for complex queries
            if (url.pathname.includes('/user/') || 
                url.pathname.includes('/quiz/') || 
                url.pathname.includes('/team/')) {
                await storeApiResponseInDB(url.pathname, await response.clone().json());
            }
        }
        
        return response;
    } catch (error) {
        // Try cached version first
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Try IndexedDB for complex data
        const dbResponse = await getFromIndexedDB(url.pathname);
        if (dbResponse) {
            return new Response(JSON.stringify(dbResponse), {
                headers: { 'Content-Type': 'application/json' }
            });
        }
        
        throw error;
    }
}

// Handle Livewire requests with limited offline support
async function handleLivewireRequest(request, url) {
    try {
        return await fetch(request);
    } catch (error) {
        // For critical Livewire routes, try to provide offline fallback
        if (url.pathname.includes('message') || url.pathname.includes('update')) {
            // Queue the request for when online
            await queueOfflineSubmission(request, 'livewire');
            
            return new Response(JSON.stringify({
                effects: { html: '', dirty: [] },
                serverMemo: { checksum: '', data: {} }
            }), {
                headers: { 'Content-Type': 'application/json' }
            });
        }
        
        throw error;
    }
}

// Handle static assets with cache first strategy
async function handleAssetRequest(request) {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        return new Response('Asset not available', { status: 404 });
    }
}

// Handle POST requests with offline queuing
async function handlePostRequest(request, url) {
    try {
        return await fetch(request);
    } catch (error) {
        // Queue POST requests for background sync
        await queueOfflineSubmission(request, 'post');
        
        // Return success response to prevent UI errors
        return new Response(JSON.stringify({
            success: true,
            message: 'Queued for sync when online',
            offline: true
        }), {
            status: 200,
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

// Network first strategy with cache fallback
async function networkFirstStrategy(request, cacheName) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(cacheName);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        throw error;
    }
}

// Store API response in IndexedDB
async function storeApiResponseInDB(path, data) {
    try {
        const db = await openIndexedDB();
        const transaction = db.transaction(['userData', 'quizData', 'teamData'], 'readwrite');
        
        if (path.includes('/user/')) {
            const store = transaction.objectStore('userData');
            store.put({ id: path, data: data, timestamp: Date.now() });
        } else if (path.includes('/quiz/')) {
            const store = transaction.objectStore('quizData');
            store.put({ id: path, data: data, timestamp: Date.now() });
        } else if (path.includes('/team/')) {
            const store = transaction.objectStore('teamData');
            store.put({ id: path, data: data, timestamp: Date.now() });
        }
    } catch (error) {
        console.warn('Failed to store API response in IndexedDB:', error);
    }
}

// Get data from IndexedDB
async function getFromIndexedDB(path) {
    try {
        const db = await openIndexedDB();
        const transaction = db.transaction(['userData', 'quizData', 'teamData'], 'readonly');
        
        let store;
        if (path.includes('/user/')) {
            store = transaction.objectStore('userData');
        } else if (path.includes('/quiz/')) {
            store = transaction.objectStore('quizData');
        } else if (path.includes('/team/')) {
            store = transaction.objectStore('teamData');
        }
        
        if (store) {
            const request = store.get(path);
            return new Promise((resolve, reject) => {
                request.onsuccess = () => resolve(request.result?.data);
                request.onerror = () => reject(request.error);
            });
        }
    } catch (error) {
        console.warn('Failed to get data from IndexedDB:', error);
    }
    return null;
}

// Queue offline submissions for background sync
async function queueOfflineSubmission(request, type) {
    try {
        const db = await openIndexedDB();
        const transaction = db.transaction(['offlineSubmissions'], 'readwrite');
        const store = transaction.objectStore('offlineSubmissions');
        
        const submission = {
            url: request.url,
            method: request.method,
            headers: [...request.headers.entries()],
            body: request.method !== 'GET' ? await request.text() : null,
            type: type,
            timestamp: Date.now()
        };
        
        store.add(submission);
        console.log('Queued offline submission:', submission);
    } catch (error) {
        console.error('Failed to queue offline submission:', error);
    }
}

// Create offline response for capable routes
async function createOfflineResponse(pathname) {
    const offlineContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>EUREKA - Offline Mode</title>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
                body { font-family: system-ui; padding: 2rem; text-align: center; }
                .offline-notice { background: #fef3cd; padding: 1rem; margin: 1rem 0; border-radius: 8px; }
            </style>
        </head>
        <body>
            <div class="offline-notice">
                📱 You're viewing this page offline. Some features may be limited.
            </div>
            <div id="app">
                <h1>EUREKA</h1>
                <p>Loading offline content...</p>
            </div>
            <script>
                // Basic offline functionality
                console.log('Offline mode active for:', '${pathname}');
                
                // Try to load cached data
                if ('caches' in window) {
                    caches.match('${pathname}').then(response => {
                        if (response) {
                            response.text().then(html => {
                                document.getElementById('app').innerHTML = html;
                            });
                        }
                    });
                }
            </script>
        </body>
        </html>
    `;
    
    return new Response(offlineContent, {
        headers: { 'Content-Type': 'text/html' }
    });
}

// Open IndexedDB connection
async function openIndexedDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('EurekaOfflineDB', 2);
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

// Background sync event
self.addEventListener('sync', event => {
    if (event.tag === 'background-sync') {
        event.waitUntil(syncOfflineSubmissions());
    }
});

// Sync offline submissions when online
async function syncOfflineSubmissions() {
    try {
        const db = await openIndexedDB();
        const transaction = db.transaction(['offlineSubmissions'], 'readwrite');
        const store = transaction.objectStore('offlineSubmissions');
        const request = store.getAll();
        
        return new Promise((resolve, reject) => {
            request.onsuccess = async () => {
                const submissions = request.result;
                console.log('Syncing offline submissions:', submissions.length);
                
                for (const submission of submissions) {
                    try {
                        const response = await fetch(submission.url, {
                            method: submission.method,
                            headers: new Headers(submission.headers),
                            body: submission.body
                        });
                        
                        if (response.ok) {
                            // Remove successful submission
                            store.delete(submission.id);
                            console.log('Synced submission:', submission.id);
                        }
                    } catch (error) {
                        console.warn('Failed to sync submission:', submission.id, error);
                    }
                }
                
                resolve();
            };
            request.onerror = () => reject(request.error);
        });
    } catch (error) {
        console.error('Background sync failed:', error);
    }
}

// Handle messages from the application
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    } else if (event.data && event.data.type === 'CACHE_USER_DATA') {
        // Cache user-specific data
        storeApiResponseInDB('/user/current', event.data.userData);
    } else if (event.data && event.data.type === 'SYNC_NOW') {
        // Trigger immediate sync
        self.registration.sync.register('background-sync');
    }
});

console.log('Enhanced Service Worker loaded with offline capabilities');