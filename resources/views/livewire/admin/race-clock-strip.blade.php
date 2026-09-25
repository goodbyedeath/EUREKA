<div wire:poll.10s.visible>
    {{-- Every team's race clock. Lived on Outpost Access until 25 Sep, when the operator moved it
         here: Team Progress is the screen the crew keeps open beside the desk. The chips carry
         their own deadline and the script counts it down, so the number moves every second while
         the poll above only refreshes what the server knows. --}}

    {{-- How far each team is and whether its clock has stopped. The desk is where "why has that team
         not finished?" is asked, so the answer is here rather than in a report read afterwards.
         Each chip carries its deadline, and the script below counts it down and rings once. --}}
    @if ($standings->isEmpty())
        <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">Belum ada tim, jadi belum ada jam yang berjalan.</p>
    @endif

    @if ($standings->isNotEmpty())
        <div class="mb-4 flex flex-wrap gap-2" id="race-standings">
            @foreach ($standings as $s)
                <div wire:key="prog-{{ $s->key }}"
                     data-team-key="{{ $s->key }}"
                     data-team-name="{{ $s->name }}"
                     @if ($s->deadline_at && ! $s->finished) data-deadline="{{ $s->deadline_at }}" @endif
                     @if ($s->out_of_time) data-out-of-time="1" @endif
                     @if ($s->stopped_by_crew) data-stopped="1" @endif
                     class="px-3 py-1.5 rounded-lg border text-xs
                            {{ $s->stopped_by_crew
                               ? 'border-amber-300 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200'
                               : ($s->out_of_time
                               ? 'border-red-400 dark:border-red-700 bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-200'
                               : ($s->finished
                                   ? 'border-green-300 dark:border-green-800 bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-200'
                                   : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300')) }}">
                    <span class="font-medium">{{ $s->name }}</span>
                    <span class="mx-1 tabular-nums">{{ $s->cleared }}/{{ $s->needed }} pos</span>
                    @if ($s->out_of_time)
                        <span class="font-medium">· WAKTU HABIS {{ $s->clock }}</span>
                    @elseif ($s->stopped_by_crew)
                        <span class="font-medium">· DIHENTIKAN KRU {{ $s->clock }}</span>
                    @elseif ($s->finished)
                        <span class="font-medium">· selesai {{ $s->clock }}</span>
                    @elseif ($s->started)
                        <span class="text-gray-500 dark:text-gray-400">· {{ $s->clock }} berjalan</span>
                        @if ($s->deadline_at)
                            {{-- Filled in by the script every second; the server value is the fallback
                                 if JavaScript is off. --}}
                            <span class="ml-1 font-medium tabular-nums" data-countdown>·</span>
                        @endif
                    @else
                        <span class="text-gray-400 dark:text-gray-500">· belum scan START</span>
                    @endif
                </div>
            @endforeach
        </div>
    @endif


    {{-- The bell. A desk operated while listening to a radio cannot be expected to watch a clock,
         so the moment a team's time runs out it is announced out loud once (operator, 24 Sep).
         Audio needs a gesture first, so it is armed on the first click anywhere and the banner
         carries the news on its own if the browser still refuses. --}}
    <div id="race-timeup-banner" wire:ignore class="hidden mb-4 rounded-lg border-2 border-red-500 bg-red-50 dark:bg-red-900/30 px-4 py-3">
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm font-semibold text-red-800 dark:text-red-200">
                <span class="mr-1">🔔</span> Waktu habis: <span id="race-timeup-teams"></span>
            </p>
            <button type="button" id="race-timeup-close"
                    class="text-xs px-2 py-1 rounded bg-white dark:bg-gray-800 border border-red-300 dark:border-red-700 text-red-700 dark:text-red-300">
                Tutup
            </button>
        </div>
    </div>

    <script>
    (() => {
        if (window.raceBell) { return; }   // wire:poll re-renders this markup every 10 s

        const rung = new Set();            // one ring per team per desk session
        const plainTitle = document.title;
        let audio = null;
        let owed = false;                  // a bell the browser refused before the first gesture

        // What the crew picked, read at the moment of ringing rather than kept in a variable, so
        // a change in Bell settings applies to the next bell without a reload. No panel on the
        // page (or an older one) means the three-note default.
        const setting = () => {
            const el = document.getElementById('race-bell-config');
            const d = el ? el.dataset : {};

            return {
                tone: d.tone || 'bel',
                volume: Math.max(0, Math.min(100, parseInt(d.volume ?? '70', 10) || 0)) / 100,
                repeat: Math.max(1, Math.min(5, parseInt(d.repeat ?? '1', 10) || 1)),
                src: d.src || null,
            };
        };

        const arm = () => {
            if (audio) { return; }
            try { audio = new (window.AudioContext || window.webkitAudioContext)(); } catch (e) { audio = null; }
            // A team whose time ran out before anyone touched the page is announced now, so the
            // first click on the desk is not a silent one.
            if (audio && owed) { owed = false; ring(); }
        };
        document.addEventListener('click', arm, { once: true });
        document.addEventListener('keydown', arm, { once: true });

        /**
         * Every tone is drawn here rather than loaded: a synthesised bell cannot arrive late, be
         * missing from the cache or 404 in the middle of an event. Each entry returns how long it
         * lasts, so a repeat can be spaced without hard-coding the gap.
         *
         * note(at, hz, seconds, peak, shape) — one note, `at` seconds from now.
         */
        const TONES = {
            // Three even strikes: the default, and the one the crew already knows.
            bel: (note) => { [0, 0.32, 0.64].forEach((at) => note(at, 880, 0.3, 1, 'sine')); return 0.94; },
            // Two long strikes that keep ringing: carries over a noisy room.
            lonceng: (note) => { note(0, 1318, 1.1, 1, 'sine'); note(0.1, 659, 1.3, 0.6, 'sine');
                                 note(0.9, 1318, 1.1, 0.9, 'sine'); note(1.0, 659, 1.3, 0.55, 'sine'); return 2.3; },
            // A sweep, twice — the one nobody mistakes for a notification.
            sirine: (note, ctx) => {
                [0, 0.75].forEach((at) => {
                    const osc = ctx.createOscillator(); const gain = ctx.createGain();
                    const t = ctx.currentTime + at;
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(500, t);
                    osc.frequency.linearRampToValueAtTime(1400, t + 0.35);
                    osc.frequency.linearRampToValueAtTime(500, t + 0.7);
                    gain.gain.setValueAtTime(0.0001, t);
                    gain.gain.exponentialRampToValueAtTime(0.25 * setting().volume, t + 0.05);
                    gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.7);
                    osc.connect(gain).connect(ctx.destination);
                    osc.start(t); osc.stop(t + 0.72);
                });

                return 1.5;
            },
            // Five short, hard beeps: the most piercing of the four in a loud hall.
            bip: (note) => { [0, 0.16, 0.32, 0.48, 0.64].forEach((at) => note(at, 1000, 0.1, 0.8, 'square')); return 0.8; },
        };

        const ring = () => {
            const cfg = setting();

            // An uploaded sound is a file, not an oscillator; it still needs the same gesture
            // before a browser will play it, so a refusal is remembered like any other.
            if (cfg.tone === 'custom' && cfg.src) {
                if (! audio) { owed = true; return false; }
                for (let i = 0; i < cfg.repeat; i++) {
                    const el = new Audio(cfg.src);
                    el.volume = cfg.volume;
                    setTimeout(() => el.play().catch(() => { owed = true; }), i * 1200);
                }

                return true;
            }

            if (! audio) { owed = true; return false; }
            if (audio.state === 'suspended') { audio.resume(); }

            const shape = TONES[cfg.tone] || TONES.bel;
            const note = (at, hz, seconds, peak, type) => {
                const osc = audio.createOscillator();
                const gain = audio.createGain();
                const t = audio.currentTime + at;
                osc.type = type;
                osc.frequency.value = hz;
                gain.gain.setValueAtTime(0.0001, t);
                gain.gain.exponentialRampToValueAtTime(Math.max(0.0002, 0.35 * peak * cfg.volume), t + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, t + seconds);
                osc.connect(gain).connect(audio.destination);
                osc.start(t);
                osc.stop(t + seconds + 0.02);
            };

            const length = shape(note, audio);
            for (let i = 1; i < cfg.repeat; i++) {
                setTimeout(() => shape(note, audio), i * (length + 0.25) * 1000);
            }

            return true;
        };

        // The "Tes bel" button. It arms the audio inside the click, which is also how the desk
        // unlocks the bell at the start of an event instead of discovering it silent at the end.
        window.raceBellTest = () => { owed = false; arm(); ring(); };

        const announce = (name) => {
            ring();   // first: the bell matters even if the banner markup is not on this page

            const banner = document.getElementById('race-timeup-banner');
            const list = document.getElementById('race-timeup-teams');
            if (! banner || ! list) { return; }
            const names = list.textContent ? list.textContent.split(', ') : [];
            if (! names.includes(name)) { names.push(name); }
            list.textContent = names.join(', ');
            banner.classList.remove('hidden');
            document.title = '🔔 Waktu habis — Outpost Access';
        };

        const pad = (n) => String(n).padStart(2, '0');

        const tick = () => {
            document.querySelectorAll('#race-standings [data-team-key]').forEach((chip) => {
                const key = chip.dataset.teamKey;
                const name = chip.dataset.teamName || key;

                // The server already closed this run on its time limit.
                if (chip.dataset.outOfTime === '1') {
                    if (! rung.has(key)) { rung.add(key); announce(name); }

                    return;
                }

                const deadline = chip.dataset.deadline;
                const out = chip.querySelector('[data-countdown]');
                if (! deadline || ! out) { return; }

                const left = Math.floor((Date.parse(deadline) - Date.now()) / 1000);

                if (left <= 0) {
                    out.textContent = '· 00:00';
                    out.classList.remove('text-amber-600', 'dark:text-amber-400');
                    out.classList.add('text-red-600', 'dark:text-red-400');
                    if (! rung.has(key)) { rung.add(key); announce(name); }

                    return;
                }

                // Over an hour reads as 1:09:58, not 69:58 — the clock on the wall, not a count of
                // minutes the crew has to divide in their head.
                const clock = left >= 3600
                    ? Math.floor(left / 3600) + ':' + pad(Math.floor(left % 3600 / 60)) + ':' + pad(left % 60)
                    : pad(Math.floor(left / 60)) + ':' + pad(left % 60);
                out.textContent = '· sisa ' + clock;
                // The last ten minutes are worth noticing before the bell.
                out.classList.toggle('text-amber-600', left <= 600);
                out.classList.toggle('dark:text-amber-400', left <= 600);
            });
        };

        const close = document.getElementById('race-timeup-close');
        if (close) {
            close.addEventListener('click', () => {
                document.getElementById('race-timeup-banner').classList.add('hidden');
                document.title = plainTitle;
            });
        }

        window.raceBell = setInterval(tick, 1000);
        tick();
    })();
    </script>
</div>
