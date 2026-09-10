{{--
    Panduan setup untuk admin.

    Ditulis untuk orang yang baru pertama kali memegang panel ini, bukan untuk programmer.
    Urutannya sengaja dibuat sama persis dengan urutan menu di sidebar, supaya orang bisa
    mengerjakannya sambil membaca tanpa harus mencari-cari.

    Halaman statis: tidak ada Livewire, tidak ada state. Kalau alur aplikasinya berubah,
    file ini ikut diperbarui.
--}}
@extends('layouts.admin')

@section('title', 'Panduan Setup')
@section('page-title', 'Panduan Setup')

@section('content')
<style>
    /* Nomor langkah yang besar dan jelas. Panduan ini kemungkinan dibaca sambil
       mengerjakan, jadi "saya tadi sampai nomor berapa" harus gampang dilihat. */
    .step-num {
        flex: 0 0 auto;
        width: 2.25rem; height: 2.25rem;
        border-radius: 9999px;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: .95rem;
    }
    .guide-body p { margin-bottom: .6rem; }
    .guide-body ul { list-style: disc; padding-left: 1.25rem; margin-bottom: .6rem; }
    .guide-body ul li { margin-bottom: .3rem; }
    .guide-body strong { font-weight: 600; }
    /* Safari still draws its own triangle even with list-none. */
    .guide-body summary::-webkit-details-marker { display: none; }

    @media print {
        aside, nav, .no-print { display: none !important; }
        .print-break { break-inside: avoid; }
    }
</style>

<div class="max-w-4xl mx-auto guide-body text-gray-700 dark:text-gray-300">

    {{-- ---------------------------------------------------------------- intro --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Panduan Setup Acara</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
            Semua yang perlu Anda siapkan, dari nol sampai acara selesai. Urutannya sama dengan
            urutan menu di sebelah kiri — kerjakan dari atas ke bawah.
        </p>
        <button type="button" onclick="window.print()"
                class="no-print mt-4 inline-flex items-center gap-2 px-3 py-2 text-sm rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">
            <i class="fas fa-print"></i> Cetak panduan ini
        </button>
    </div>

    {{-- ---------------------------------------------------------------- dua mode --}}
    <div class="mb-10 print-break">
        <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-3">Pertama: acara Anda Outdoor atau Indoor?</h2>
        <p>
            Ini keputusan paling awal, karena menentukan menu mana yang perlu Anda isi.
            Bedanya cuma satu hal: <strong>bagaimana sistem tahu sebuah tim sudah sampai di pos.</strong>
        </p>

        <div class="grid md:grid-cols-2 gap-4 mt-4">
            <div class="rounded-xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 p-5">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-tree text-green-600 dark:text-green-400"></i>
                    <h3 class="font-bold text-green-900 dark:text-green-200">Outdoor</h3>
                </div>
                <p class="text-sm text-green-900 dark:text-green-200 mb-2">
                    <strong>GPS yang menentukan.</strong> Tim melihat peta, jalan ke titik pos, dan
                    begitu masuk radius yang Anda tentukan, kamera 3D-nya terbuka sendiri.
                </p>
                <p class="text-sm text-green-800 dark:text-green-300 mb-0">
                    Admin tidak perlu berbuat apa-apa saat acara berjalan.
                </p>
            </div>

            <div class="rounded-xl border border-teal-200 dark:border-teal-800 bg-teal-50 dark:bg-teal-900/20 p-5">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-building text-teal-600 dark:text-teal-400"></i>
                    <h3 class="font-bold text-teal-900 dark:text-teal-200">Indoor</h3>
                </div>
                <p class="text-sm text-teal-900 dark:text-teal-200 mb-2">
                    <strong>Manusia yang menentukan.</strong> GPS tidak tembus atap, jadi kru di
                    lapangan lapor lewat HT, lalu admin membuka kamera 3D untuk tim itu secara manual.
                </p>
                <p class="text-sm text-teal-800 dark:text-teal-300 mb-0">
                    Admin harus duduk di panel <strong>Outpost Access</strong> sepanjang acara.
                </p>
            </div>
        </div>

        <div class="mt-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-4">
            <p class="text-sm text-blue-900 dark:text-blue-200 mb-0">
                <i class="fas fa-info-circle mr-1"></i>
                Selain itu, semuanya sama: cara membuat soal, cara scan QR, cara menghitung poin,
                dan cara jam lomba berjalan — semua identik di kedua mode.
            </p>
        </div>
    </div>

    {{-- ---------------------------------------------------------------- persiapan --}}
    <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-1">Bagian 1 — Persiapan</h2>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
        Dikerjakan jauh-jauh hari sebelum acara. Ikuti urutannya, karena langkah bawah butuh data
        dari langkah atas.
    </p>

    <div class="space-y-4 mb-10">

        @php
            // Data-driven supaya urutan, nomor dan tautannya tidak bisa meleset satu sama lain.
            $steps = [
                [
                    'n' => 1,
                    'route' => 'admin.users',
                    'menu' => 'Account Management',
                    'title' => 'Buat akun login untuk setiap tim',
                    'chip' => 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300',
                    'body' => '<p><strong>Satu akun = satu tim.</strong> Tidak ada pendaftaran mandiri;
                        semua akun Anda yang buatkan di sini. Anggota tim tidak punya akun
                        sendiri — mereka main bersama-sama lewat satu HP dengan satu akun.</p>
                        <p>Di halaman ini juga ada pengaturan <strong>batas waktu akses</strong> per akun.
                        Kalau waktunya habis, akun itu terkunci sampai Anda perpanjang. Berguna supaya
                        tim tidak bisa mengintip soal sehari sebelum acara.</p>',
                ],
                [
                    'n' => 2,
                    'route' => 'admin.team-management',
                    'menu' => 'Team Management',
                    'title' => 'Daftarkan tim dan anggotanya',
                    'chip' => 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300',
                    'body' => '<p>Buat timnya: nama, departemen, dan <strong>poin awal</strong>
                        (bawaannya 1000). Poin awal ini penting kalau permainan Anda memakai sistem
                        taruhan atau denda — poin bisa berkurang dari angka itu.</p>
                        <p>Lalu masukkan nama-nama anggotanya. Anggota di sini hanya <em>data</em>
                        (nama, jabatan, siapa ketuanya) — mereka tidak dapat akun login sendiri.</p>',
                ],
                [
                    'n' => 3,
                    'route' => 'admin.dashboard-management',
                    'menu' => 'Questionnaires',
                    'title' => 'Buat soal untuk setiap pos',
                    'chip' => 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300',
                    'body' => '<p><strong>Satu pos = satu kuesioner.</strong> Ini bagian paling banyak
                        kerjanya, jadi sediakan waktu.</p>
                        <p>Untuk tiap kuesioner Anda mengisi: judul, batas waktu pengerjaan (menit),
                        berapa kali boleh dicoba, dan nilai minimal lulus.</p>
                        <p>Ada satu centang yang sering terlewat: <strong>&ldquo;Counts toward finishing&rdquo;</strong>.
                        Biarkan menyala untuk pos biasa. Matikan kalau itu <strong>pos bonus</strong> —
                        posnya tetap memberi poin, tapi jam lomba tidak menunggu tim mengerjakannya.</p>
                        <p>Setelah kuesionernya jadi, klik <strong>Questions</strong> untuk mengisi
                        pertanyaannya, dan <strong>QR Code</strong> untuk mengambil QR yang akan
                        Anda cetak dan tempel di pos.</p>',
                ],
                [
                    'n' => 4,
                    'route' => 'admin.games',
                    'menu' => 'AR Outposts',
                    'title' => 'Siapkan pos kamera 3D',
                    'chip' => 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300',
                    'body' => '<p>Ini pos tempat tim mencari <strong>objek 3D lewat kamera HP</strong>.
                        Objeknya diketuk, lalu muncul gambar atau tulisan yang jadi <em>petunjuk letak
                        QR fisiknya</em>.</p>
                        <p>Di tiap pos ada pilihan <strong>Access mode</strong>:</p>
                        <ul>
                            <li><strong>Geofence</strong> — untuk outdoor. Kamera terbuka sendiri saat
                                tim masuk radius.</li>
                            <li><strong>Manual</strong> — untuk indoor. Kamera hanya terbuka kalau admin
                                membukanya dari panel Outpost Access.</li>
                        </ul>
                        <p><strong>Penting:</strong> menaruh objek 3D-nya harus dilakukan
                        <strong>di lokasi pos itu sendiri</strong>, sambil membawa HP. Anda berdiri di
                        tempatnya, arahkan kamera, lalu ketuk untuk meletakkan objek. Saat itu juga
                        sistem otomatis merekam titik GPS posnya.</p>',
                ],
            ];
        @endphp

        @foreach ($steps as $s)
            <div class="print-break flex gap-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5">
                <div class="step-num {{ $s['chip'] }}">
                    {{ $s['n'] }}
                </div>
                <div class="min-w-0">
                    <h3 class="font-bold text-gray-900 dark:text-gray-100">{{ $s['title'] }}</h3>
                    <a href="{{ route($s['route']) }}"
                       class="inline-flex items-center gap-1 text-xs text-indigo-600 dark:text-indigo-400 hover:underline mb-2">
                        Menu: {{ $s['menu'] }} <i class="fas fa-arrow-right text-[10px]"></i>
                    </a>
                    <div class="text-sm">{!! $s['body'] !!}</div>
                </div>
            </div>
        @endforeach

        {{-- Langkah 5 bercabang, jadi ditulis terpisah dari daftar di atas. --}}
        <div class="print-break flex gap-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5">
            <div class="step-num bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300">5</div>
            <div class="min-w-0 w-full">
                <h3 class="font-bold text-gray-900 dark:text-gray-100">Tentukan lokasi pos</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                    Langkah ini beda tergantung mode acara Anda. Kerjakan salah satu saja.
                </p>

                <div class="grid md:grid-cols-2 gap-3">
                    <div class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 p-4">
                        <div class="text-xs font-bold uppercase tracking-wide text-green-700 dark:text-green-300 mb-1">
                            Kalau Outdoor
                        </div>
                        <a href="{{ route('admin.quest-locations') }}"
                           class="text-sm font-semibold text-green-900 dark:text-green-200 hover:underline">
                            Quest Locations <i class="fas fa-arrow-right text-[10px]"></i>
                        </a>
                        <p class="text-sm text-green-900 dark:text-green-200 mt-2 mb-0">
                            Tandai titik tiap pos di peta, lalu tentukan <strong>radius</strong>-nya
                            (dalam meter). Radius inilah yang membuka pos otomatis saat tim mendekat.
                            Jangan terlalu kecil — GPS di dekat gedung bisa meleset 5–20 meter.
                        </p>
                    </div>

                    <div class="rounded-lg border border-teal-200 dark:border-teal-800 bg-teal-50 dark:bg-teal-900/20 p-4">
                        <div class="text-xs font-bold uppercase tracking-wide text-teal-700 dark:text-teal-300 mb-1">
                            Kalau Indoor
                        </div>
                        <a href="{{ route('admin.indoor-maps') }}"
                           class="text-sm font-semibold text-teal-900 dark:text-teal-200 hover:underline">
                            Indoor Maps <i class="fas fa-arrow-right text-[10px]"></i>
                        </a>
                        <p class="text-sm text-teal-900 dark:text-teal-200 mt-2 mb-0">
                            Unggah <strong>denah gedung tampak atas</strong> (foto atau gambar), lalu
                            klik di atas denah itu untuk menaruh penanda tiap pos. Anda bisa atur nama,
                            bentuk, warna, dan isi yang muncul saat penanda diketuk.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Langkah 6–8 --}}
        <div class="print-break flex gap-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5">
            <div class="step-num bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300">6</div>
            <div class="min-w-0">
                <h3 class="font-bold text-gray-900 dark:text-gray-100">Buat kode START</h3>
                <a href="{{ route('admin.race-start') }}"
                   class="inline-flex items-center gap-1 text-xs text-indigo-600 dark:text-indigo-400 hover:underline mb-2">
                    Menu: Race Start <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
                <div class="text-sm">
                    <p>Ini QR yang dipasang di <strong>garis start</strong>. Begitu sebuah tim
                    memindainya, <strong>jam lomba tim itu mulai berjalan</strong>.</p>
                    <p>Saat membuatnya, Anda memilih <strong>Mode</strong>:</p>
                    <ul>
                        <li><strong>Outdoor</strong> — setelah scan, tim langsung ke peta GPS.</li>
                        <li><strong>Indoor &mdash; (nama denah)</strong> — setelah scan, tim dapat
                            pertanyaan petunjuk dulu, baru denahnya terbuka.</li>
                    </ul>
                    <p>Klik tombol <strong>QR</strong> di daftarnya untuk melihat, mengunduh (PNG/SVG),
                    atau langsung mencetak kodenya.</p>
                </div>
            </div>
        </div>

        <div class="print-break flex gap-4 rounded-xl border border-orange-300 dark:border-orange-700 bg-orange-50 dark:bg-orange-900/20 p-5">
            <div class="step-num bg-orange-200 dark:bg-orange-900/60 text-orange-800 dark:text-orange-200">7</div>
            <div class="min-w-0">
                <h3 class="font-bold text-orange-900 dark:text-orange-200">Nyalakan fitur yang dipakai</h3>
                <a href="{{ route('admin.feature-management') }}"
                   class="inline-flex items-center gap-1 text-xs text-orange-700 dark:text-orange-300 hover:underline mb-2">
                    Menu: Feature Control <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
                <div class="text-sm text-orange-900 dark:text-orange-200">
                    <p><strong>Jangan lewati langkah ini.</strong> Ini penyebab nomor satu orang
                    mengira aplikasinya rusak.</p>
                    <p>Ada 21 sakelar di sini yang mengatur apa yang <em>terlihat</em> di layar tim.
                    Kalau sebuah sakelar mati, menunya tidak muncul di HP tim sama sekali — walaupun
                    datanya sudah Anda isi lengkap. Dashboard tim yang kosong melompong hampir selalu
                    berarti sakelarnya masih mati, bukan datanya yang hilang.</p>
                    <p class="mb-0">Nyalakan minimal: kuis, quest locations, dan (kalau outdoor) GPS tracking.</p>
                </div>
            </div>
        </div>

        <div class="print-break flex gap-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5">
            <div class="step-num bg-pink-100 dark:bg-pink-900/40 text-pink-700 dark:text-pink-300">8</div>
            <div class="min-w-0">
                <h3 class="font-bold text-gray-900 dark:text-gray-100">Rapikan tampilan depan</h3>
                <a href="{{ route('admin.hero-slides') }}"
                   class="inline-flex items-center gap-1 text-xs text-indigo-600 dark:text-indigo-400 hover:underline mb-2">
                    Menu: Hero Slides <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
                <div class="text-sm">
                    <p class="mb-0">Gambar dan tulisan yang dilihat tim di halaman pembuka. Opsional,
                    tapi ini yang membuat acaranya terasa milik klien Anda — pasang logo dan sambutan
                    perusahaannya di sini.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ---------------------------------------------------------------- cetak --}}
    <div class="mb-10 print-break">
        <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-3">Bagian 2 — Yang harus dicetak</h2>
        <p>Ini pekerjaan fisik yang sering ketinggalan sampai H-1. Semua QR bisa diunduh dari panel.</p>

        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 mt-3">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="text-left px-4 py-2 font-semibold text-gray-600 dark:text-gray-300">Cetak apa</th>
                        <th class="text-left px-4 py-2 font-semibold text-gray-600 dark:text-gray-300">Berapa</th>
                        <th class="text-left px-4 py-2 font-semibold text-gray-600 dark:text-gray-300">Ambil dari</th>
                        <th class="text-left px-4 py-2 font-semibold text-gray-600 dark:text-gray-300">Tempel di mana</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr>
                        <td class="px-4 py-2 font-medium text-gray-900 dark:text-gray-100">QR START</td>
                        <td class="px-4 py-2">1 lembar</td>
                        <td class="px-4 py-2">Race Start &rarr; tombol QR</td>
                        <td class="px-4 py-2">Garis start</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-medium text-gray-900 dark:text-gray-100">QR tiap pos</td>
                        <td class="px-4 py-2">1 per pos</td>
                        <td class="px-4 py-2">Questionnaires &rarr; tombol QR Code</td>
                        <td class="px-4 py-2">
                            Disembunyikan di pos — sesuai petunjuk yang muncul dari objek 3D-nya
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-4">
            <p class="text-sm text-amber-900 dark:text-amber-200 mb-0">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                <strong>Jangan pasang QR pos di tempat terbuka yang gampang terlihat dari jauh.</strong>
                QR itulah satu-satunya bukti bahwa tim benar-benar sampai ke pos. Kalau bisa dipindai
                dari kejauhan, seluruh permainan bisa diselesaikan tanpa berjalan ke mana-mana.
            </p>
        </div>
    </div>

    {{-- ---------------------------------------------------------------- hari H --}}
    <div class="mb-10 print-break">
        <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-3">Bagian 3 — Hari-H</h2>

        <p class="mb-4">Yang dialami tim, dari awal sampai satu pos selesai:</p>

        <ol class="space-y-2 mb-6">
            @foreach ([
                'Login pakai akun timnya.',
                'Pindai <strong>QR START</strong> &mdash; jam lomba mulai berjalan.',
                'Menuju pos: lewat peta GPS (outdoor) atau denah gedung (indoor).',
                'Sampai di pos &rarr; <strong>kamera 3D terbuka</strong> (otomatis kalau outdoor, dibukakan admin kalau indoor).',
                'Cari objek 3D lewat kamera, ketuk objeknya &rarr; muncul petunjuk letak QR fisiknya.',
                'Temukan QR kertasnya, pindai &mdash; soal langsung terbuka.',
                'Kerjakan soal. Hitung mundur berjalan dan <strong>tim tidak bisa keluar</strong> sampai selesai.',
                'Selesai &rarr; kembali ke dashboard, lanjut ke pos berikutnya.',
            ] as $i => $line)
                <li class="flex gap-3 text-sm">
                    <span class="flex-none w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-bold flex items-center justify-center">{{ $i + 1 }}</span>
                    <span class="pt-0.5">{!! $line !!}</span>
                </li>
            @endforeach
        </ol>

        <h3 class="font-bold text-gray-900 dark:text-gray-100 mb-2">Tugas admin selama acara</h3>
        <div class="grid sm:grid-cols-2 gap-3">
            @foreach ([
                ['admin.outpost-access', 'fa-unlock', 'text-amber-500', 'Outpost Access', 'Wajib kalau indoor. Kru lapor lewat HT &ldquo;Tim Merah sudah sampai Pos 3&rdquo;, Anda klik, kamera 3D tim itu terbuka.'],
                ['admin.gps-tracking', 'fa-map-marked-alt', 'text-red-500', 'GPS Tracking Map', 'Kalau outdoor. Melihat posisi semua tim di peta secara langsung.'],
                ['admin.user-progress', 'fa-chart-line', 'text-cyan-500', 'Team Progress', 'Siapa sudah menyelesaikan pos apa, berapa poinnya.'],
                ['kiosk.led', 'fa-tv', 'text-slate-500', 'LED Screen', 'Halaman papan skor untuk proyektor. Buka di tab terpisah, tampilkan di layar besar.'],
            ] as [$route, $icon, $colour, $label, $desc])
                <a href="{{ route($route) }}" @if($route === 'kiosk.led') target="_blank" rel="noopener" @endif
                   class="block rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 hover:border-indigo-300 dark:hover:border-indigo-600 transition-colors">
                    <div class="flex items-center gap-2 mb-1">
                        <i class="fas {{ $icon }} {{ $colour }}"></i>
                        <span class="font-semibold text-gray-900 dark:text-gray-100 text-sm">{{ $label }}</span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-0">{!! $desc !!}</p>
                </a>
            @endforeach
        </div>
    </div>

    {{-- ---------------------------------------------------------------- jam --}}
    <div class="mb-10 print-break">
        <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-3">Bagian 4 — Dua jam yang perlu Anda pahami</h2>
        <p>Orang sering tertukar antara keduanya. Keduanya berjalan bersamaan dan tidak saling mengganggu.</p>

        <div class="grid md:grid-cols-2 gap-4 mt-3">
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5">
                <h3 class="font-bold text-gray-900 dark:text-gray-100 mb-1">
                    <i class="fas fa-stopwatch text-indigo-500 mr-1"></i> Jam lomba
                </h3>
                <p class="text-sm mb-2">Total waktu satu tim, dari start sampai finish.</p>
                <ul class="text-sm">
                    <li><strong>Mulai:</strong> saat tim memindai QR START.</li>
                    <li><strong>Berhenti:</strong> otomatis, begitu tim menyelesaikan pos terakhir yang
                        dihitung (yang centang &ldquo;counts toward finishing&rdquo;-nya menyala).</li>
                </ul>
                <p class="text-sm mb-0 text-gray-500 dark:text-gray-400">
                    Salah pindai dua kali tidak mengulang jamnya dari nol. Jam ini yang tampil di layar LED.
                </p>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5">
                <h3 class="font-bold text-gray-900 dark:text-gray-100 mb-1">
                    <i class="fas fa-hourglass-half text-rose-500 mr-1"></i> Hitung mundur per pos
                </h3>
                <p class="text-sm mb-2">Batas waktu mengerjakan soal di satu pos.</p>
                <ul class="text-sm">
                    <li><strong>Mulai:</strong> saat soal terbuka setelah QR pos dipindai.</li>
                    <li><strong>Berhenti:</strong> saat soal selesai atau waktunya habis.</li>
                </ul>
                <p class="text-sm mb-0 text-gray-500 dark:text-gray-400">
                    Panjangnya Anda atur per kuesioner, di kolom batas waktu.
                </p>
            </div>
        </div>
    </div>

    {{-- ---------------------------------------------------------------- setelah --}}
    <div class="mb-10 print-break">
        <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-3">Bagian 5 — Setelah acara selesai</h2>

        <div class="rounded-xl border border-violet-200 dark:border-violet-800 bg-violet-50 dark:bg-violet-900/20 p-5 mb-3">
            <h3 class="font-bold text-violet-900 dark:text-violet-200 mb-1">
                <i class="fas fa-clipboard-check mr-1"></i> Nilai permainan fisiknya
            </h3>
            <a href="{{ route('admin.game-assessments') }}"
               class="inline-flex items-center gap-1 text-xs text-violet-700 dark:text-violet-300 hover:underline mb-2">
                Menu: Game Assessments <i class="fas fa-arrow-right text-[10px]"></i>
            </a>
            <p class="text-sm text-violet-900 dark:text-violet-200 mb-0">
                Soal bertipe <strong>Fun Game</strong> tidak bisa dinilai komputer — itu permainan
                fisik yang dilihat langsung oleh pendamping tim. Sistem menyimpannya dengan
                <strong>nilai 0 dulu</strong>, dan Anda yang mengisikan nilainya di sini.
                Selama belum diisi, poin tim akan terlihat lebih kecil dari seharusnya.
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5">
            <h3 class="font-bold text-gray-900 dark:text-gray-100 mb-1">
                <i class="fas fa-file-export text-cyan-500 mr-1"></i> Ambil laporannya
            </h3>
            <a href="{{ route('admin.user-progress') }}"
               class="inline-flex items-center gap-1 text-xs text-indigo-600 dark:text-indigo-400 hover:underline mb-2">
                Menu: Team Progress <i class="fas fa-arrow-right text-[10px]"></i>
            </a>
            <p class="text-sm mb-0">
                Hasil akhir semua tim, bisa diekspor untuk diserahkan ke klien.
                Lakukan <strong>setelah</strong> semua Fun Game dinilai, supaya angkanya sudah final.
            </p>
        </div>
    </div>

    {{-- ---------------------------------------------------------------- jaringan --}}
    <div class="mb-10 print-break">
        <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-3">Bagian 6 &mdash; Jaringan di hari-H</h2>
        <p class="mb-4">
            Satu hal teknis yang benar-benar bisa menghentikan acara, jadi tolong dibaca sekali.
        </p>

        <div class="rounded-xl border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 p-5 mb-4">
            <h3 class="font-bold text-amber-900 dark:text-amber-200 mb-1">
                <i class="fas fa-wifi mr-1"></i> Jangan sambungkan semua tim ke satu WiFi
            </h3>
            <p class="text-sm text-amber-900 dark:text-amber-200 mb-2">
                Kalau 20 tim memakai WiFi venue yang sama, server melihat <strong>satu alamat</strong>
                untuk semuanya. Penyedia hosting membatasi permintaan per alamat, jadi seluruh
                ruangan bisa terkena batas itu bersamaan &mdash; dan aplikasinya berhenti untuk
                semua orang sekaligus, walau servernya sendiri sehat.
            </p>
            <p class="text-sm text-amber-900 dark:text-amber-200 mb-0">
                <strong>Pakai kuota data masing-masing HP.</strong> Tiap HP dapat alamatnya
                sendiri, dan masalah ini hilang. Gratis, dan ini pencegahan yang paling ampuh.
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 mb-4">
            <h3 class="font-bold text-gray-900 dark:text-gray-100 mb-1">Kalau tetap harus pakai WiFi venue</h3>
            <ul class="tight mb-0">
                <li>Hubungi Hostinger <strong>sebelum acara</strong>. Sebutkan tanggalnya, perkiraan
                    jumlah peserta, dan minta batas laju untuk domain ini dinaikkan pada hari itu.</li>
                <li>Jangan buka layar LED, papan skor, dan peta kiosk sekaligus di jaringan yang
                    sama kalau tidak dipakai &mdash; tiap layar menambah beban.</li>
                <li>Sediakan satu HP dengan kuota data sebagai jalur cadangan untuk admin.</li>
            </ul>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5">
            <h3 class="font-bold text-gray-900 dark:text-gray-100 mb-1">Kalau tetap terjadi saat bermain</h3>
            <p class="text-sm mb-2">
                Aplikasinya sudah dirancang untuk bertahan, bukan mati. Yang akan tim lihat:
                spanduk <em>&ldquo;Server sedang sibuk &mdash; mencoba lagi dalam N detik&rdquo;</em>,
                lalu aplikasi jalan kembali sendiri.
            </p>
            <ul class="tight mb-0">
                <li><strong>Suruh tim menunggu, jangan menekan-nekan tombol.</strong> Menekan
                    berulang justru memperpanjang blokirnya.</li>
                <li>Jam lomba dan hitung mundur tetap berjalan &mdash; keduanya dihitung di server,
                    tidak terpengaruh.</li>
                <li>Jawaban yang sudah tersimpan tidak hilang.</li>
                <li>Kalau satu tim macet lebih dari dua menit, minta mereka pindah ke kuota data.</li>
            </ul>
        </div>
    </div>

    {{-- ---------------------------------------------------------------- gotchas --}}
    <div class="mb-6 print-break">
        <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-3">Bagian 7 — Kalau ada yang terasa aneh</h2>
        <p class="mb-4">Daftar keluhan yang paling sering muncul, dan penyebabnya yang paling sering.</p>

        <div class="space-y-2">
            @foreach ([
                ['Dashboard tim kosong, tidak ada menu apa-apa',
                 'Sakelarnya masih mati di <strong>Feature Control</strong>. Ini bukan kerusakan &mdash; menu memang tidak muncul kalau fiturnya belum dinyalakan.'],
                ['Tim bilang soalnya tidak mau terbuka',
                 'Mereka belum memindai QR kertas di pos itu. Soal memang sengaja dikunci sampai QR-nya dipindai &mdash; itu satu-satunya bukti mereka benar-benar sampai ke sana.'],
                ['Kamera 3D tidak mau terbuka (outdoor)',
                 'Tim masih di luar radius pos. Cek nilai radiusnya di <strong>Quest Locations</strong>; kalau lokasinya rapat gedung, perbesar sedikit &mdash; GPS bisa meleset 5&ndash;20 meter.'],
                ['Kamera 3D tidak mau terbuka (indoor)',
                 'Memang begitu sampai admin membukanya. Buka <strong>Outpost Access</strong>, pilih timnya, klik posnya.'],
                ['Objek 3D ikut ke mana-mana / muncul di kantor',
                 'Objeknya digambar relatif terhadap HP, bukan dipaku ke tanah. Yang mengunci tempat adalah <strong>GPS + radius</strong> pos, bukan kameranya. Pastikan pos itu sudah punya titik lokasi.'],
                ['Jam lomba tidak mau berhenti padahal tim sudah selesai',
                 'Masih ada kuesioner aktif dengan centang <strong>&ldquo;counts toward finishing&rdquo;</strong> yang belum mereka kerjakan. Matikan centangnya kalau itu pos bonus, atau nonaktifkan kuesionernya.'],
                ['Poin sebuah tim terlihat terlalu kecil',
                 'Kemungkinan besar soal <strong>Fun Game</strong>-nya belum dinilai. Buka <strong>Game Assessments</strong>.'],
                ['Tim tidak bisa login, katanya terkunci',
                 'Batas waktu aksesnya sudah lewat. Perpanjang dari <strong>Account Management</strong>.'],
                ['Layar LED kosong padahal acaranya indoor',
                 'Petanya memang kosong tanpa sinyal GPS. Yang muncul di indoor adalah <strong>nama pos</strong> tempat tim itu sedang dibukakan aksesnya &mdash; jadi pastikan Anda membukanya lewat Outpost Access.'],
            ] as $i => [$q, $a])
                <details class="group rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
                    <summary class="cursor-pointer list-none px-4 py-3 flex items-start gap-3 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <i class="fas fa-chevron-right text-xs text-gray-400 mt-1 transition-transform group-open:rotate-90"></i>
                        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $q }}</span>
                    </summary>
                    <div class="px-4 pb-3 pl-10 text-sm text-gray-600 dark:text-gray-400">{!! $a !!}</div>
                </details>
            @endforeach
        </div>
    </div>

    <p class="text-xs text-gray-400 dark:text-gray-500 border-t border-gray-200 dark:border-gray-700 pt-4">
        Panduan ini menjelaskan sistem sebagaimana adanya saat ini. Kalau ada langkah yang tidak
        cocok dengan yang Anda lihat di layar, kemungkinan aplikasinya sudah berubah dan panduan ini
        perlu diperbarui.
    </p>
</div>
@endsection
