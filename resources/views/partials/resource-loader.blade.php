{{-- "Loading Resources" — game-style patching screen. Pulls the app shell, post
     photos and the venue's map tiles onto the device before the team walks out of
     signal. Quiz content is deliberately excluded: it is QR-gated. --}}
<div id="eureka-loader" style="display:none;position:fixed;inset:0;z-index:2147483600;background:linear-gradient(160deg,#0f172a,#1e293b);color:#f8fafc;font-family:system-ui,-apple-system,sans-serif;align-items:center;justify-content:center;padding:24px;">
    <div style="width:100%;max-width:420px;text-align:center;">
        <div style="font-size:15px;letter-spacing:.14em;text-transform:uppercase;color:#94a3b8;margin-bottom:10px;">{{ \App\Models\BrandSetting::appName() }}</div>
        <h2 style="margin:0 0 6px;font-size:24px;font-weight:700;">Loading Resources</h2>
        <div style="height:12px;border-radius:9999px;background:#334155;overflow:hidden;box-shadow:inset 0 1px 3px rgba(0,0,0,.4);">
            <div id="eureka-loader-bar" style="height:100%;width:0%;border-radius:9999px;background:linear-gradient(90deg,#22c55e,#4ade80);transition:width .25s ease;"></div>
        </div>

        <div style="display:flex;justify-content:space-between;margin-top:10px;font-size:13px;color:#cbd5e1;">
            <span id="eureka-loader-phase">Preparing…</span>
            <span id="eureka-loader-pct" style="font-variant-numeric:tabular-nums;font-weight:600;">0%</span>
        </div>

        <p id="eureka-loader-detail" style="margin:18px 0 0;font-size:12px;color:#64748b;min-height:16px;"></p>

        <button id="eureka-loader-retry" type="button"
                style="display:none;margin-top:24px;padding:10px 22px;border:0;border-radius:9999px;background:#22c55e;color:#052e16;font-size:14px;font-weight:700;cursor:pointer;">
            Try again
        </button>
    </div>
</div>

<script>
(function () {
    if (!('serviceWorker' in navigator)) return;

    var READY_KEY = 'eureka.resources.ready.v1';

    var el = {
        root: document.getElementById('eureka-loader'),
        bar: document.getElementById('eureka-loader-bar'),
        phase: document.getElementById('eureka-loader-phase'),
        pct: document.getElementById('eureka-loader-pct'),
        detail: document.getElementById('eureka-loader-detail'),
        retry: document.getElementById('eureka-loader-retry')
    };

    var PHASE_LABEL = { shell: 'App files', images: 'Photos', tiles: 'Map tiles', done: 'Finished' };
    // Weighted so the bar tracks real work: tiles dominate the download.
    var WEIGHT = { shell: 0.10, images: 0.25, tiles: 0.65 };
    var ORDER = ['shell', 'images', 'tiles'];

    function open() { el.root.style.display = 'flex'; }
    function close() { el.root.style.display = 'none'; }

    function paint(phase, done, total) {
        var base = 0;
        for (var i = 0; i < ORDER.length; i++) {
            if (ORDER[i] === phase) break;
            base += WEIGHT[ORDER[i]] * 100;
        }
        var frac = total > 0 ? (done / total) : 1;
        var pct = phase === 'done' ? 100 : Math.min(99, Math.round(base + frac * WEIGHT[phase] * 100));

        el.bar.style.width = pct + '%';
        el.pct.textContent = pct + '%';
        el.phase.textContent = PHASE_LABEL[phase] || phase;
        el.detail.textContent = phase === 'done' ? '' : (done + ' of ' + total);
    }

    navigator.serviceWorker.addEventListener('message', function (event) {
        var d = event.data || {};
        if (d.type === 'PRECACHE_PROGRESS') paint(d.phase, d.done, d.total);
    });

    function tileXY(lon, lat, z) {
        var n = Math.pow(2, z);
        var latRad = lat * Math.PI / 180;
        return {
            x: Math.floor((lon + 180) / 360 * n),
            y: Math.floor((1 - Math.log(Math.tan(latRad) + 1 / Math.cos(latRad)) / Math.PI) / 2 * n)
        };
    }

    function tileUrls(bounds, minZoom, maxZoom, limit) {
        var template = ((window.EUREKA_MAP || {}).tiles || [])[0];
        if (!template || !bounds) return [];

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
        return urls;
    }

    function load(options) {
        options = options || {};
        var sw = navigator.serviceWorker.controller;
        if (!sw) return Promise.reject(new Error('worker not in control yet — reload once'));

        open();
        el.phase.textContent = 'Checking what is needed…';

        el.retry.style.display = 'none';

        return fetch('{{ route('user.offline.manifest') }}', {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        })
            .then(function (r) {
                // A redirect here means an auth/middleware bounce, and r.json() would
                // throw on the HTML — which used to surface as "check your connection".
                if (r.redirected) throw new Error('Session expired. Please log in again.');
                if (!r.ok) throw new Error('Server returned ' + r.status + '.');
                var type = r.headers.get('content-type') || '';
                if (type.indexOf('json') === -1) throw new Error('Unexpected response from server.');
                return r.json();
            })
            .then(function (manifest) {
                var tiles = tileUrls(manifest.bounds, options.minZoom || 14, options.maxZoom || 19, options.limit || 1200);

                return new Promise(function (resolve) {
                    var channel = new MessageChannel();
                    channel.port1.onmessage = function (event) {
                        var d = event.data || {};
                        paint('done', 1, 1);
                        el.phase.textContent = 'Ready to play offline';
                        el.detail.textContent = d.stored + ' of ' + d.total + ' files cached';
                        try { localStorage.setItem(READY_KEY, String(Date.now())); } catch (e) {}
                        setTimeout(close, 1600);
                        resolve(d);
                    };

                    sw.postMessage({
                        type: 'PRECACHE_BUNDLE',
                        shell: (window.EUREKA_SHELL || []).concat(manifest.pages || []),
                        images: (manifest.images || []).concat(manifest.models || []),
                        tiles: tiles
                    }, [channel.port2]);
                });
            })
            .catch(function (error) {
                el.phase.textContent = 'Could not finish';
                el.detail.textContent = error && error.message ? error.message : 'Download failed.';
                el.retry.style.display = 'inline-block';
                throw error;
            });
    }

    el.retry.addEventListener('click', function () { load().catch(function () {}); });

    window.EurekaResources = {
        load: load,
        isReady: function () { try { return !!localStorage.getItem(READY_KEY); } catch (e) { return false; } },
        reset: function () { try { localStorage.removeItem(READY_KEY); } catch (e) {} }
    };

    // Any [data-eureka-redownload] control re-runs the patch on demand.
    document.querySelectorAll('[data-eureka-redownload]').forEach(function (button) {
        button.addEventListener('click', function () {
            var label = button.querySelector('[data-eureka-redownload-label]') || button;
            var note = document.querySelector('[data-eureka-redownload-note]');
            var original = label.textContent;

            if (!navigator.onLine) {
                if (note) note.textContent = 'You are offline — reconnect first.';
                return;
            }

            button.disabled = true;
            label.textContent = 'Downloading…';
            if (note) note.textContent = '';

            // Clearing the flag means an interrupted run retries cleanly next login.
            window.EurekaResources.reset();

            load().then(function (result) {
                if (note) note.textContent = 'Up to date — ' + result.stored + ' files ready offline.';
            }).catch(function () {
                if (note) note.textContent = 'Download failed. Check your connection and try again.';
            }).finally(function () {
                button.disabled = false;
                label.textContent = original;
            });
        });
    });

    // First online visit after login: patch automatically, once.
    navigator.serviceWorker.ready.then(function () {
        if (!navigator.onLine) return;
        if (window.EurekaResources.isReady()) return;
        setTimeout(function () { load().catch(function () {}); }, 800);
    });
})();
</script>
