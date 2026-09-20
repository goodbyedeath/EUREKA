{{-- What a failed Livewire request looks like to the person using the page.

     Without this, a response that is not the JSON Livewire expects — 419 after the session times
     out, 429 from the update limiter, a 500 — leaves only
     "Cannot read properties of undefined (reading 'shift')" in the console (pool.js), the page
     silently stops responding, and the operator has no way to know a reload is all that is needed.

     preventDefault() stops Livewire throwing on the malformed body; the bar below says what
     happened and offers the fix.

     Closing it has to stick (operator, 20 Sep). Pages like Outpost Access refresh themselves every
     ten seconds, so with a dead session the bar came straight back and read as unclosable. Once
     dismissed it stays away for that failure, and only returns for a different failure or after a
     request has succeeded again. --}}
<div id="lw-failure" role="alert" hidden
     style="position:fixed;left:50%;bottom:20px;transform:translateX(-50%);z-index:9999;max-width:min(92vw,560px);
            display:flex;gap:12px;align-items:center;padding:12px 16px;border-radius:12px;
            background:#7f1d1d;color:#fee2e2;box-shadow:0 6px 24px rgba(0,0,0,.35);font-size:14px">
    <span id="lw-failure-text" style="flex:1"></span>
    <button type="button" onclick="window.location.reload()"
            style="border:0;border-radius:8px;padding:6px 12px;background:#fee2e2;color:#7f1d1d;font-weight:600;cursor:pointer;white-space:nowrap">
        Muat ulang
    </button>
    <button type="button" id="lw-failure-close" aria-label="Tutup (Esc)" title="Tutup (Esc)"
            style="border:0;background:rgba(255,255,255,.12);color:#fff;cursor:pointer;font-size:20px;line-height:1;
                   width:32px;height:32px;border-radius:8px;flex:none">&times;</button>
</div>
<script>
    (function () {
        // The failure the operator has already dismissed. Null means nothing is dismissed.
        let dismissed = null;

        function box() { return document.getElementById('lw-failure'); }

        function hide() {
            const el = box();
            if (el) el.hidden = true;
        }

        function messageFor(status) {
            if (status === 419) return 'Sesi login habis. Muat ulang halaman, lalu ulangi yang tadi. (HTTP 419)';
            if (status === 429) return 'Terlalu banyak permintaan dalam waktu singkat. Tunggu sebentar, lalu coba lagi. (HTTP 429)';
            if (status === 403) return 'Tidak punya akses untuk tindakan ini. (HTTP 403)';
            if (status >= 500) return 'Server gagal memproses permintaan ini (HTTP ' + status + '). Muat ulang halaman; kalau terulang, catat langkahnya.';
            return 'Permintaan halaman gagal (HTTP ' + status + '). Muat ulang halaman.';
        }

        document.addEventListener('click', function (e) {
            if (e.target && e.target.id === 'lw-failure-close') {
                const el = box();
                dismissed = el ? el.dataset.status || 'unknown' : 'unknown';
                hide();
            }
        });

        document.addEventListener('keydown', function (e) {
            const el = box();
            if (e.key === 'Escape' && el && !el.hidden) {
                dismissed = el.dataset.status || 'unknown';
                hide();
            }
        });

        document.addEventListener('livewire:init', function () {
            Livewire.hook('request', function ({ fail, succeed }) {
                fail(function ({ status, preventDefault }) {
                    const el = box();
                    const text = document.getElementById('lw-failure-text');
                    if (!el || !text) return;

                    // Livewire's own handler assumes a JSON body; on these responses it throws in
                    // pool.js instead of surfacing the status. Stop that whether or not we show the bar.
                    preventDefault();

                    if (dismissed === String(status)) return;

                    text.textContent = messageFor(status);
                    el.dataset.status = String(status);
                    el.hidden = false;
                });

                succeed(function () {
                    // The page is answering again: clear the bar and let a later failure speak.
                    dismissed = null;
                    hide();
                });
            });
        });
    })();
</script>
