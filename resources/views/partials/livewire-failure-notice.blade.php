{{-- What a failed Livewire request looks like to the person using the page.

     Without this, a response that is not the JSON Livewire expects — 419 after the session times
     out, 429 from the update limiter, a 500 — leaves only
     "Cannot read properties of undefined (reading 'shift')" in the console (pool.js), the page
     silently stops responding, and the operator has no way to know a reload is all that is needed.

     preventDefault() stops Livewire throwing on the malformed body; the bar below says what
     happened.

     It used to carry a "Muat ulang" button. The operator had it removed on 20 Sep as useless, and
     they are right: anyone who reads "muat ulang halaman" reloads the way they always do, and the
     button only added something to mis-click on a bar whose whole job is to be read and dismissed.
     The sentences still say when a reload is the fix.

     Closing it has to stick (operator, 20 Sep). Pages like Outpost Access refresh themselves every
     ten seconds, so with a dead session the bar came straight back and read as unclosable. Once
     dismissed it stays away for that failure, and only returns for a different failure or after a
     request has succeeded again. --}}
{{-- display:none is set inline, and the script toggles that same property.

     It used to rely on the `hidden` attribute while carrying an inline display:flex. `hidden` works
     through the UA stylesheet rule [hidden] { display: none }, and an inline style outranks a UA
     rule in the cascade, so in Chrome the attribute hid nothing: this bar sat on every page from
     load, empty, and neither × nor Esc could dismiss it (Esc worse — its guard read el.hidden,
     which was true while the bar was plainly on screen, so it returned early every time). Firefox
     marks that rule !important and behaved, which is how it survived a look at the markup.
     Verified in a real headless Chrome, before and after — see the operator's report, 20 Sep. --}}
<div id="lw-failure" role="alert" hidden
     style="position:fixed;left:50%;bottom:20px;transform:translateX(-50%);z-index:9999;max-width:min(92vw,560px);
            display:none;gap:12px;align-items:center;padding:12px 16px;border-radius:12px;
            background:#7f1d1d;color:#fee2e2;box-shadow:0 6px 24px rgba(0,0,0,.35);font-size:14px">
    <span id="lw-failure-text" style="flex:1"></span>
    <button type="button" id="lw-failure-close" aria-label="Tutup (Esc)" title="Tutup (Esc)"
            style="border:0;background:rgba(255,255,255,.12);color:#fff;cursor:pointer;font-size:20px;line-height:1;
                   width:32px;height:32px;border-radius:8px;flex:none">&times;</button>
</div>
<script>
    (function () {
        // The failure the operator has already dismissed. Null means nothing is dismissed.
        let dismissed = null;

        function box() { return document.getElementById('lw-failure'); }

        function showing() {
            const el = box();
            return !!el && el.style.display !== 'none';
        }

        function hide() {
            const el = box();
            if (!el) return;
            // display is the property that decides this, so it is the property we set. The
            // attribute stays in step for assistive technology.
            el.style.display = 'none';
            el.hidden = true;
        }

        function show() {
            const el = box();
            if (!el) return;
            el.hidden = false;
            el.style.display = 'flex';
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
            if (e.key === 'Escape' && showing()) {
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
                    show();
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
