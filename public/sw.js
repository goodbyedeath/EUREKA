// Enhanced Service Worker for Laravel PWA with Offline Support
// Bumped to v5 on 2026-09-02: the previous build intercepted navigation POSTs and
// could cache odd responses. Activating under a new name drops those entries.
// Bumped to v6 on 2026-09-09: the offline queue only deleted an entry when the server
// answered ok, so anything it refused — including a 429 — was replayed on every page
// load, forever. A new name forces every device onto the fixed worker.
const CACHE_NAME = 'eureka-pwa-v7';
const OFFLINE_URL = '/offline.html';
const API_CACHE_NAME = 'eureka-api-cache-v1';
const LIVEWIRE_CACHE_NAME = 'eureka-livewire-cache-v1';
const TILE_CACHE_NAME = 'eureka-tiles-v1';

// A weak mobile signal fails slowly rather than cleanly, so every network attempt
// is bounded. Without this the UI just hangs instead of falling back to cache.
const NETWORK_TIMEOUT_MS = 8000;

// Essential files to cache (only files that definitely exist)
const ESSENTIAL_CACHE_URLS = [
    '/',
    '/offline.html',
    '/manifest.json'
];

// Critical routes that should work offline
const OFFLINE_CAPABLE_ROUTES = [
    '/',
    '/user/dashboard',
    '/quest-locations',
    '/gps-tracking',
    '/indoor-map',
    '/quiz/',
    '/team-registration',
    '/kiosk/led'
];

// Install event - cache essential files and setup IndexedDB
self.addEventListener('install', event => {
    event.waitUntil(
        Promise.all([
            // Cache essential files
            caches.open(CACHE_NAME).then(cache => {
                return Promise.allSettled(
                    ESSENTIAL_CACHE_URLS.map(url => 
                        fetch(url)
                            .then(response => {
                                if (response.ok) {
                                    return cache.put(url, response);
                                }
                            })
                            .catch(error => {
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
            return self.skipWaiting();
        }).catch(error => {
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
            
        };
    });
}

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (cacheName !== CACHE_NAME && 
                            cacheName !== API_CACHE_NAME && 
                            cacheName !== TILE_CACHE_NAME && 
                            cacheName !== LIVEWIRE_CACHE_NAME) {
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
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

    // Map tiles are cross-origin, so they must be handled before the origin check
    // below. Cache-first means a precached venue keeps drawing with no network.
    if (isTileRequest(url)) {
        event.respondWith(tileCacheFirst(request));
        return;
    }

    // Skip other cross-origin requests
    if (url.origin !== self.location.origin) {
        return;
    }

    // Form submissions (login, logout, team registration) must reach the network
    // untouched. Intercepting a navigation POST breaks the browser's redirect
    // handling — which surfaced as a bogus 409 on /logout — and queueing one for
    // later sync is meaningless anyway: you cannot log out retroactively.
    if (request.mode === 'navigate' && request.method !== 'GET') {
        return;
    }

    // Try the network, and fall back only when it ACTUALLY fails.
    //
    // This used to be gated on `!navigator.onLine`, which reports "online" whenever
    // the device is attached to any network at all — including a weak signal that
    // passes no data, or a captive portal. That is the normal outdoor failure mode,
    // and it bypassed this worker entirely, so offline support never engaged in the
    // one situation it exists for.
    if (request.method === 'GET') {
        event.respondWith(networkThenOffline(request, url));
    } else if (request.method === 'POST') {
        event.respondWith(handlePostRequest(request, url));
    }
});

// Bounded fetch: reject rather than hang on a signal that never answers.
function fetchWithTimeout(request, ms) {
    return new Promise((resolve, reject) => {
        const timer = setTimeout(() => reject(new Error('network timeout')), ms || NETWORK_TIMEOUT_MS);
        fetch(request).then(
            response => { clearTimeout(timer); resolve(response); },
            error => { clearTimeout(timer); reject(error); }
        );
    });
}

// Network first; on real failure hand over to the existing offline handlers.
async function networkThenOffline(request, url) {
    try {
        const response = await fetchWithTimeout(request);

        // A navigation that followed a redirect cannot be handed straight back:
        // respondWith() rejects a redirected response when the request's redirect
        // mode is not 'follow', and the browser surfaces that as an opaque error
        // status rather than the login page. Re-issue it as an explicit redirect so
        // the address bar follows too. This is the session-expiry path: log out in
        // one tab, refresh another.
        if (request.mode === 'navigate' && response && response.redirected) {
            return Response.redirect(response.url, 302);
        }

        // Keep caches warm while connectivity lasts, so the fallback has something
        // recent to serve when it stops.
        if (response && response.ok && request.method === 'GET') {
            const target = url.pathname.startsWith('/api/') ? API_CACHE_NAME : CACHE_NAME;
            const copy = response.clone();
            caches.open(target).then(cache => cache.put(request, copy)).catch(() => {});
        }

        return response;
    } catch (error) {
        return handleGetRequest(request, url);
    }
}

// Basemap tiles, wherever they are hosted (see config/maps.php).
function isTileRequest(url) {
    if (/\/\d+\/\d+\/\d+(@2x)?\.(png|jpe?g|webp|pbf)$/i.test(url.pathname)) {
        return true;
    }
    return /(^|\.)((tile|tiles|basemaps?)\.|maptiler|stadiamaps|openstreetmap|arcgisonline|cartocdn)/i.test(url.hostname);
}

async function tileCacheFirst(request) {
    const cache = await caches.open(TILE_CACHE_NAME);
    const hit = await cache.match(request, { ignoreVary: true });
    if (hit) {
        return hit;
    }

    try {
        const response = await fetchWithTimeout(request, 10000);
        if (response && (response.ok || response.type === 'opaque')) {
            cache.put(request, response.clone()).catch(() => {});
        }
        return response;
    } catch (error) {
        return Response.error();
    }
}

// Tell every open tab how far the download has got, so the UI can show a real bar
// rather than a spinner that means nothing.
async function broadcastProgress(phase, done, total) {
    const clients = await self.clients.matchAll({ includeUncontrolled: true });
    clients.forEach(client => client.postMessage({
        type: 'PRECACHE_PROGRESS', phase: phase, done: done, total: total
    }));
}

// Fetch a list into a cache, skipping anything already held, reporting as it goes.
async function precacheList(cacheName, urls, phase, allowOpaque) {
    const cache = await caches.open(cacheName);
    let done = 0;
    let stored = 0;

    for (const url of urls) {
        try {
            if (await cache.match(url, { ignoreVary: true })) {
                stored++;
            } else {
                let response = null;
                try {
                    response = await fetch(url, { credentials: 'same-origin' });
                } catch (error) {
                    // Third-party tiles may refuse CORS; an opaque copy still renders.
                    if (allowOpaque) {
                        response = await fetch(url, { mode: 'no-cors', credentials: 'omit' }).catch(() => null);
                    }
                }
                if (response && (response.ok || response.type === 'opaque')) {
                    await cache.put(url, response.clone());
                    stored++;
                }
            }
        } catch (error) {
        }

        done++;
        if (done % 10 === 0 || done === urls.length) {
            await broadcastProgress(phase, done, urls.length);
        }
    }

    return stored;
}

// One pass over everything the leader needs: app shell, post photos, venue tiles.
async function precacheBundle(data) {
    const shell = data.shell || [];
    const images = data.images || [];
    const tiles = data.tiles || [];

    const result = { total: shell.length + images.length + tiles.length };
    result.shell = await precacheList(CACHE_NAME, shell, 'shell', false);
    result.images = await precacheList(CACHE_NAME, images, 'images', false);
    result.tiles = await precacheList(TILE_CACHE_NAME, tiles, 'tiles', true);
    result.stored = result.shell + result.images + result.tiles;

    await broadcastProgress('done', result.total, result.total);

    return result;
}

// Cache the app shell — CSS, JS, MapLibre, icons — so the app opens with no network.
// The page supplies the URLs because only it can resolve Vite's hashed filenames.
async function precacheShell(urls) {
    const cache = await caches.open(CACHE_NAME);
    let stored = 0;

    for (const url of urls) {
        try {
            if (await cache.match(url)) { stored++; continue; }
            const response = await fetch(url, { credentials: 'same-origin' });
            if (response && response.ok) { await cache.put(url, response.clone()); stored++; }
        } catch (error) {
        }
    }

    return stored;
}

// Pull the venue's tiles into the cache while the leader still has signal.
async function precacheTiles(urls) {
    const cache = await caches.open(TILE_CACHE_NAME);
    let stored = 0;

    for (const url of urls) {
        try {
            if (await cache.match(url, { ignoreVary: true })) { stored++; continue; }
            const response = await fetch(url, { mode: 'cors', credentials: 'omit' })
                .catch(() => fetch(url, { mode: 'no-cors', credentials: 'omit' }));
            if (response && (response.ok || response.type === 'opaque')) {
                await cache.put(url, response.clone());
                stored++;
            }
        } catch (error) {
        }
    }

    return stored;
}

// How many writes are still waiting to reach the server?
async function countPendingSubmissions() {
    try {
        const db = await openIndexedDB();
        const transaction = db.transaction(['offlineSubmissions'], 'readonly');
        const store = transaction.objectStore('offlineSubmissions');
        const request = store.count();

        return await new Promise(resolve => {
            request.onsuccess = () => resolve(request.result || 0);
            request.onerror = () => resolve(0);
        });
    } catch (error) {
        return 0;
    }
}

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
        // Let the browser handle the request normally instead of returning service unavailable
        return fetch(request);
    }
}

// Handle navigation requests with offline fallback (only called when offline)
async function handleNavigationRequest(request, url) {
    // This function is only called when navigator.onLine is false
    
    // Try to return cached version first
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
    return offlineResponse || new Response('Offline - No cached version available', { status: 503 });
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
    // Straight to the network, and a failure is reported as a failure.
    //
    // This used to queue a failed Livewire POST for replay and hand the page a fake success.
    // Both were wrong:
    //
    //   * A Livewire payload is a snapshot of one component at one instant, carrying a
    //     checksum tied to server-side state. Replaying it minutes later cannot work — and
    //     if it did, it would re-run whatever the click was. The queued call that started
    //     this investigation was $wire.delete(20).
    //   * The fake body was Livewire 2 shaped (`effects` / `serverMemo`). Livewire 3 expects
    //     `{ components: [...], assets: [] }`, so the client would have been fed garbage and
    //     wedged the component rather than showing an error.
    //
    // Livewire's own client already handles a failed or rate-limited update: it surfaces the
    // error and, on a 429 with Retry-After, backs off. Let it.
    return await fetch(request);
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
        // Clone so the body is still readable if we have to queue it below.
        return await fetchWithTimeout(request.clone());
    } catch (error) {
        // Queue POST requests for background sync
        await queueOfflineSubmission(request, 'post');
        await notifyPending();
        
        // `success` stays true so the quiz UI does not show a hard error, but the
        // queued/pending_sync flags say plainly that the server has NOT seen this
        // yet — the UI must not present it as saved.
        return new Response(JSON.stringify({
            success: true,
            queued: true,
            pending_sync: true,
            message: 'Saved on this device — will sync when the signal returns',
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
    } catch (error) {
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
        event.waitUntil(syncOfflineSubmissions().then(notifyPending));
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
                
                for (const submission of submissions) {
                    // A 429 means the server is asking everyone to slow down. Replaying a
                    // queue into it is precisely what keeps it tripped, so the whole run
                    // stops at the first one and waits for the next sync.
                    if (self.__eurekaBackoffUntil && Date.now() < self.__eurekaBackoffUntil) break;

                    try {
                        const response = await fetch(submission.url, {
                            method: submission.method,
                            headers: new Headers(submission.headers),
                            body: submission.body
                        });

                        if (response.ok) {
                            store.delete(submission.id);
                            continue;
                        }

                        if (response.status === 429) {
                            // Keep it queued, but stop the run and hold off for a minute.
                            self.__eurekaBackoffUntil = Date.now() + 60000;
                            break;
                        }

                        // Any other 4xx is the server's final answer: the request was
                        // malformed, unauthorised, or its CSRF token expired while it sat
                        // here. It cannot become valid by being sent again.
                        //
                        // This is what turned a rate limit into a loop that fed itself:
                        // an entry was only ever deleted on `ok`, so anything the server
                        // refused stayed in the queue and was replayed on every page load,
                        // by every device holding one, forever.
                        if (response.status >= 400 && response.status < 500) {
                            store.delete(submission.id);
                            continue;
                        }

                        // 5xx: the server is unwell, not the request. Retry later, but not
                        // indefinitely — a poisoned entry must eventually stop costing
                        // requests.
                        const tries = (submission.attempts || 0) + 1;
                        if (tries >= 5) store.delete(submission.id);
                        else store.put({ ...submission, attempts: tries });
                    } catch (error) {
                        // Genuinely offline. Leave it queued and count the attempt, so a
                        // request that can never leave this device is not carried forever.
                        const tries = (submission.attempts || 0) + 1;
                        if (tries >= 10) store.delete(submission.id);
                        else store.put({ ...submission, attempts: tries });
                    }
                }

                resolve();
            };
            request.onerror = () => reject(request.error);
        });
    } catch (error) {
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
        event.waitUntil(syncOfflineSubmissions().then(notifyPending));
    } else if (event.data && event.data.type === 'GET_PENDING_COUNT') {
        event.waitUntil(countPendingSubmissions().then(pending => {
            if (event.ports && event.ports[0]) {
                event.ports[0].postMessage({ pending: pending });
            }
        }));
    } else if (event.data && event.data.type === 'PRECACHE_BUNDLE') {
        event.waitUntil(precacheBundle(event.data).then(result => {
            if (event.ports && event.ports[0]) {
                event.ports[0].postMessage(result);
            }
        }));
    } else if (event.data && event.data.type === 'PRECACHE_SHELL') {
        event.waitUntil(precacheShell(event.data.urls || []).then(stored => {
            if (event.ports && event.ports[0]) {
                event.ports[0].postMessage({ stored: stored, requested: (event.data.urls || []).length });
            }
        }));
    } else if (event.data && event.data.type === 'PRECACHE_TILES') {
        event.waitUntil(precacheTiles(event.data.urls || []).then(stored => {
            if (event.ports && event.ports[0]) {
                event.ports[0].postMessage({ stored: stored, requested: (event.data.urls || []).length });
            }
        }));
    }
});

// Push the queue depth to every open tab so the UI can stop claiming things saved.
async function notifyPending() {
    const pending = await countPendingSubmissions();
    const clients = await self.clients.matchAll({ includeUncontrolled: true });
    clients.forEach(client => client.postMessage({ type: 'PENDING_COUNT', pending: pending }));
}

