@php
    // The shell the app needs to open with no network. Vite emits hashed filenames,
    // so the list is resolved here — the worker cannot guess them, and the previous
    // hardcoded '/css/app.css' and '/js/app.js' were 404s that failed silently.
    $eurekaShell = [
        '/',
        '/offline.html',
        '/manifest.json',
        '/vendor/maplibre/4.7.1/maplibre-gl.js',
        '/vendor/maplibre/4.7.1/maplibre-gl.css',
        '/vendor/maplibre/3.6.2/maplibre-gl.js',
        '/vendor/maplibre/3.6.2/maplibre-gl.css',
        // three.js powers the AR experience; without it a cached AR outpost is a
        // blank screen, which is worse than the panorama it replaced.
        '/vendor/three/0.169.0/three.module.min.js',
        '/vendor/three/0.169.0/loaders/GLTFLoader.js',
        '/vendor/three/0.169.0/utils/BufferGeometryUtils.js',
    ];

    if (is_file($eurekaManifest = public_path('build/manifest.json'))) {
        foreach ((json_decode(file_get_contents($eurekaManifest), true) ?: []) as $eurekaEntry) {
            if (! empty($eurekaEntry['file'])) {
                $eurekaShell[] = '/build/' . $eurekaEntry['file'];
            }

            foreach ($eurekaEntry['css'] ?? [] as $eurekaCss) {
                $eurekaShell[] = '/build/' . $eurekaCss;
            }
        }
    }

    $eurekaShell = array_values(array_unique($eurekaShell));
@endphp
{{-- Offline support for the team side. Registers the worker, shows honest sync
     state, and can pull the venue's map tiles down while signal still exists. --}}
<script>
window.EUREKA_SHELL = @json($eurekaShell);
(function () {
    if (!('serviceWorker' in navigator)) return;

    // The version query busts both the browser HTTP cache and Cloudflare, which was
    // serving /sw.js with max-age=604800 — a stale worker kept controlling pages.
    navigator.serviceWorker.register('/sw.js?v={{ @filemtime(public_path('sw.js')) ?: 0 }}').catch(function () {});

    var pill = null;

    function ensurePill() {
        if (pill) return pill;
        pill = document.createElement('div');
        pill.id = 'eureka-sync-status';
        pill.setAttribute('role', 'status');
        pill.style.cssText = [
            'position:fixed', 'left:50%', 'transform:translateX(-50%)', 'bottom:16px',
            'z-index:2147483000', 'padding:8px 16px', 'border-radius:9999px',
            'font:600 13px/1.2 system-ui,-apple-system,sans-serif', 'color:#fff',
            'box-shadow:0 4px 14px rgba(0,0,0,.28)', 'display:none', 'pointer-events:none',
            'max-width:92vw', 'text-align:center'
        ].join(';');
        document.body.appendChild(pill);
        return pill;
    }

    function show(text, background) {
        var p = ensurePill();
        p.textContent = text;
        p.style.background = background;
        p.style.display = 'block';
    }

    function hide() {
        if (pill) pill.style.display = 'none';
    }

    var lastPending = 0;

    function render(pending) {
        lastPending = pending;
        if (pending > 0) {
            show(pending + (pending === 1 ? ' answer' : ' answers') + ' saved on this device — waiting for signal', '#b45309');
        } else if (!navigator.onLine) {
            show('Offline — your answers are being saved on this device', '#b45309');
        } else {
            hide();
        }
    }

    // Ask the worker how deep the queue is. Never guess: the queue is the truth.
    function refresh() {
        var sw = navigator.serviceWorker.controller;
        if (!sw) return;
        var channel = new MessageChannel();
        channel.port1.onmessage = function (event) {
            render((event.data && event.data.pending) || 0);
        };
        try { sw.postMessage({ type: 'GET_PENDING_COUNT' }, [channel.port2]); } catch (e) {}
    }

    navigator.serviceWorker.addEventListener('message', function (event) {
        if (event.data && event.data.type === 'PENDING_COUNT') {
            render(event.data.pending || 0);
        }
    });

    function requestSync() {
        var sw = navigator.serviceWorker.controller;
        if (sw) { try { sw.postMessage({ type: 'SYNC_NOW' }); } catch (e) {} }
        setTimeout(refresh, 1500);
    }

    window.addEventListener('online', function () {
        if (lastPending > 0) show('Signal back — syncing…', '#047857');
        requestSync();
    });
    window.addEventListener('offline', function () { render(lastPending); });
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) refresh();
    });

    navigator.serviceWorker.ready.then(function () {
        refresh();
        if (navigator.onLine) {
            requestSync();
            precacheShell();
        }
    });

    // Pull the app shell down on first online visit, so a later cold start with no
    // signal still opens a working, styled app rather than a blank page.
    function precacheShell() {
        var sw = navigator.serviceWorker.controller;
        if (!sw) return;
        try { sw.postMessage({ type: "PRECACHE_SHELL", urls: @json($eurekaShell) }); } catch (e) {}
    }
    setInterval(refresh, 20000);

    // ---- Venue tile precaching -------------------------------------------------
    // Call from the console or a button once the posts are set:
    //   EurekaOffline.precacheVenue({north, south, east, west}, 14, 18)
    function tileXY(lon, lat, z) {
        var n = Math.pow(2, z);
        var latRad = lat * Math.PI / 180;
        return {
            x: Math.floor((lon + 180) / 360 * n),
            y: Math.floor((1 - Math.log(Math.tan(latRad) + 1 / Math.cos(latRad)) / Math.PI) / 2 * n)
        };
    }

    function precacheVenue(bounds, minZoom, maxZoom, limit) {
        var cfg = window.EUREKA_MAP || {};
        var template = (cfg.tiles || [])[0];
        if (!template) return Promise.reject(new Error('no tile template configured'));

        minZoom = minZoom || 14;
        maxZoom = maxZoom || 18;
        limit = limit || 1500;

        var urls = [];
        for (var z = minZoom; z <= maxZoom && urls.length < limit; z++) {
            var a = tileXY(bounds.west, bounds.north, z);
            var b = tileXY(bounds.east, bounds.south, z);
            for (var x = Math.min(a.x, b.x); x <= Math.max(a.x, b.x) && urls.length < limit; x++) {
                for (var y = Math.min(a.y, b.y); y <= Math.max(a.y, b.y) && urls.length < limit; y++) {
                    urls.push(template.replace('{z}', z).replace('{x}', x).replace('{y}', y));
                }
            }
        }

        show('Downloading ' + urls.length + ' map tiles for offline use…', '#1d4ed8');

        return new Promise(function (resolve, reject) {
            var sw = navigator.serviceWorker.controller;
            if (!sw) { hide(); reject(new Error('service worker not controlling this page yet — reload once')); return; }
            var channel = new MessageChannel();
            channel.port1.onmessage = function (event) {
                var d = event.data || {};
                show('Offline map ready — ' + d.stored + ' of ' + d.requested + ' tiles cached', '#047857');
                setTimeout(function () { render(lastPending); }, 4000);
                resolve(d);
            };
            sw.postMessage({ type: 'PRECACHE_TILES', urls: urls }, [channel.port2]);
        });
    }

    window.EurekaOffline = { refresh: refresh, sync: requestSync, precacheVenue: precacheVenue };
})();
</script>
