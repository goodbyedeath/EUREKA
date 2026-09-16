{{-- What a failed Livewire request looks like to the person using the page.

     Without this, a response that is not the JSON Livewire expects — 419 after the session times
     out, 429 from the update limiter, a 500 — leaves only
     "Cannot read properties of undefined (reading 'shift')" in the console (pool.js), the page
     silently stops responding, and the operator has no way to know a reload is all that is needed.

     preventDefault() stops Livewire throwing on the malformed body; the bar below says what
     happened and offers the fix. --}}
<div id="lw-failure" role="alert" hidden
     style="position:fixed;left:50%;bottom:20px;transform:translateX(-50%);z-index:9999;max-width:min(92vw,560px);
            display:flex;gap:12px;align-items:center;padding:12px 16px;border-radius:12px;
            background:#7f1d1d;color:#fee2e2;box-shadow:0 6px 24px rgba(0,0,0,.35);font-size:14px">
    <span id="lw-failure-text" style="flex:1"></span>
    <button type="button" onclick="window.location.reload()"
            style="border:0;border-radius:8px;padding:6px 12px;background:#fee2e2;color:#7f1d1d;font-weight:600;cursor:pointer">
        Muat ulang
    </button>
    <button type="button" onclick="document.getElementById('lw-failure').hidden = true"
            style="border:0;background:none;color:#fecaca;cursor:pointer;font-size:18px;line-height:1" aria-label="Tutup">&times;</button>
</div>
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.hook('request', ({ fail }) => {
            fail(({ status, preventDefault }) => {
                const box = document.getElementById('lw-failure');
                const text = document.getElementById('lw-failure-text');
                if (!box || !text) return;

                if (status === 419) {
                    text.textContent = 'Sesi login habis. Muat ulang halaman, lalu ulangi yang tadi. (HTTP 419)';
                } else if (status === 429) {
                    text.textContent = 'Terlalu banyak permintaan dalam waktu singkat. Tunggu sebentar, lalu coba lagi. (HTTP 429)';
                } else if (status === 403) {
                    text.textContent = 'Tidak punya akses untuk tindakan ini. (HTTP 403)';
                } else if (status >= 500) {
                    text.textContent = 'Server gagal memproses permintaan ini (HTTP ' + status + '). Muat ulang halaman; kalau terulang, catat langkahnya.';
                } else {
                    text.textContent = 'Permintaan halaman gagal (HTTP ' + status + '). Muat ulang halaman.';
                }

                box.hidden = false;
                // Livewire's own handler assumes a JSON body; on these responses it throws in
                // pool.js instead of surfacing the status.
                preventDefault();
            });
        });
    });
</script>