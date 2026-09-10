{{--
    Halaman informasi untuk klien.

    Berbeda audiens dari admin/guide.blade.php: yang itu panduan operasional untuk orang yang
    memegang panel, yang ini penjelasan umum untuk calon klien atau peserta. Karena itu bahasanya
    resmi, tanpa istilah teknis, dan tidak menyebut nama menu mana pun.

    Sengaja berdiri sendiri: tidak memakai layout admin dan tidak memanggil @vite, supaya bisa
    dibuka tanpa login, tetap tampil benar bila proses build belum dijalankan, dan enak dicetak
    menjadi PDF. Nama serta logo dibaca dari pengaturan merek, jadi ikut berubah bila diganti.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Informasi Acara &mdash; {{ \App\Models\BrandSetting::appName() }}</title>
    <link rel="icon" type="image/png" href="{{ \App\Models\BrandSetting::iconUrl() }}">
    <meta name="description" content="Penjelasan umum mengenai rangkaian acara team building {{ \App\Models\BrandSetting::appName() }}.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet">

    <style>
        :root {
            --ink: #1f2430;
            --ink-soft: #4b5565;
            --line: #e3e7ee;
            --accent: #6777ef;
            --tint: #f5f6fb;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Figtree, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--ink);
            background: #eef0f6;
            line-height: 1.75;
            font-size: 16px;
        }

        .sheet {
            max-width: 860px;
            margin: 40px auto;
            background: #fff;
            padding: 56px 64px 64px;
            border-radius: 10px;
            box-shadow: 0 2px 24px rgba(31, 36, 48, .08);
        }

        header { border-bottom: 2px solid var(--line); padding-bottom: 28px; margin-bottom: 40px; }
        header img { height: 46px; width: auto; display: block; margin-bottom: 24px; }

        h1 { font-size: 30px; line-height: 1.3; margin: 0 0 8px; font-weight: 700; letter-spacing: -.01em; }
        .subtitle { color: var(--ink-soft); margin: 0; font-size: 17px; }

        h2 {
            font-size: 20px; font-weight: 600; margin: 44px 0 14px;
            padding-top: 28px; border-top: 1px solid var(--line);
        }
        h2:first-of-type { border-top: none; padding-top: 0; margin-top: 0; }
        h3 { font-size: 16px; font-weight: 600; margin: 26px 0 8px; }

        p { margin: 0 0 16px; }
        .lead { font-size: 17.5px; color: var(--ink-soft); }

        ul, ol { margin: 0 0 16px; padding-left: 24px; }
        li { margin-bottom: 9px; }
        li::marker { color: var(--accent); }

        .flow { list-style: none; padding: 0; margin: 0 0 8px; counter-reset: langkah; }
        .flow > li {
            counter-increment: langkah;
            position: relative;
            padding: 0 0 22px 52px;
            margin: 0;
            border-left: 1px solid var(--line);
        }
        .flow > li:last-child { border-left-color: transparent; padding-bottom: 0; }
        .flow > li::before {
            content: counter(langkah);
            position: absolute; left: -15px; top: 0;
            width: 30px; height: 30px; border-radius: 50%;
            background: var(--accent); color: #fff;
            font-size: 14px; font-weight: 600;
            display: flex; align-items: center; justify-content: center;
        }
        .flow strong { display: block; margin-bottom: 2px; }

        .cards { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0 8px; }
        .card { background: var(--tint); border: 1px solid var(--line); border-radius: 8px; padding: 22px 24px; }
        .card h3 { margin-top: 0; }
        .card p:last-child, .card ul:last-child { margin-bottom: 0; }

        table { width: 100%; border-collapse: collapse; margin: 18px 0; font-size: 15.5px; }
        th, td { text-align: left; padding: 11px 14px; border-bottom: 1px solid var(--line); vertical-align: top; }
        th { background: var(--tint); font-weight: 600; }
        td:first-child { width: 34%; font-weight: 500; }

        .note {
            background: var(--tint); border-left: 3px solid var(--accent);
            padding: 18px 22px; margin: 24px 0; border-radius: 0 6px 6px 0;
        }
        .note p:last-child { margin-bottom: 0; }

        footer {
            margin-top: 52px; padding-top: 26px;
            border-top: 2px solid var(--line);
            color: var(--ink-soft); font-size: 14.5px;
        }

        @media (max-width: 700px) {
            .sheet { margin: 0; border-radius: 0; padding: 36px 24px 48px; }
            .cards { grid-template-columns: 1fr; }
            h1 { font-size: 25px; }
            td:first-child { width: auto; }
        }

        /* Dicetak atau disimpan sebagai PDF: buang latar dan bayangan, pertahankan struktur,
           dan jangan biarkan judul bagian terpisah dari isinya di ujung halaman. */
        @media print {
            body { background: #fff; font-size: 11.5pt; line-height: 1.6; }
            .sheet { margin: 0; max-width: none; box-shadow: none; border-radius: 0; padding: 0; }
            h2 { page-break-after: avoid; break-after: avoid; }
            .card, .note, table, .flow > li { page-break-inside: avoid; break-inside: avoid; }
            h2 { margin-top: 28px; padding-top: 18px; }
        }
    </style>
</head>
<body>
<div class="sheet">

    <header>
        <img src="{{ \App\Models\BrandSetting::horizontalUrl() }}" alt="{{ \App\Models\BrandSetting::appName() }}">
        <h1>Informasi Acara</h1>
        <p class="subtitle">{{ \App\Models\BrandSetting::appName() }}@if (\App\Models\BrandSetting::tagline()) &mdash; {{ \App\Models\BrandSetting::tagline() }}@endif</p>
    </header>

    <h2>Ringkasan</h2>

    <p class="lead">
        {{ \App\Models\BrandSetting::appName() }} adalah rangkaian permainan pencarian harta karun
        untuk kegiatan <em>team building</em>. Peserta dibagi menjadi beberapa tim, lalu bersama-sama
        menelusuri sejumlah pos yang telah disiapkan di lokasi acara.
    </p>

    <p>
        Pada setiap pos, tim menyelesaikan tantangan berupa kuis atau permainan kelompok. Setiap
        tantangan yang diselesaikan menambah perolehan nilai tim. Perolehan nilai seluruh tim
        ditampilkan pada layar besar dan diperbarui sepanjang acara berlangsung, sehingga peserta
        maupun penonton dapat mengikuti jalannya persaingan secara langsung.
    </p>

    <p>
        Seluruh rangkaian dijalankan melalui sebuah aplikasi pada telepon seluler. Peserta tidak
        perlu membawa berkas, formulir, maupun alat tulis, dan perhitungan nilai berlangsung secara
        otomatis tanpa rekapitulasi manual.
    </p>

    <h2>Jalannya Acara</h2>

    <p>Dari sisi peserta, rangkaian acara berlangsung dalam urutan berikut.</p>

    <ol class="flow">
        <li>
            <strong>Pembagian tim dan akun</strong>
            Setiap tim menerima satu akun untuk masuk ke dalam aplikasi. Pembagian tim dapat
            ditentukan sebelumnya oleh pihak penyelenggara.
        </li>
        <li>
            <strong>Pengarahan dan tanda mulai</strong>
            Setelah pengarahan singkat, tim memindai kode mulai. Penghitung waktu tim aktif pada
            saat tersebut.
        </li>
        <li>
            <strong>Menuju pos berikutnya</strong>
            Aplikasi menunjukkan pos yang harus dituju. Pada acara di dalam ruangan, petunjuk
            berupa denah lokasi; pada acara di luar ruangan, berupa peta beserta jarak menuju pos.
        </li>
        <li>
            <strong>Menyelesaikan tantangan</strong>
            Setibanya di pos, tim memindai kode yang tersedia untuk membuka tantangan, kemudian
            mengerjakannya sesuai batas waktu yang ditetapkan.
        </li>
        <li>
            <strong>Penilaian</strong>
            Tantangan berupa kuis dinilai secara otomatis. Tantangan berupa permainan kelompok
            dinilai oleh fasilitator yang bertugas di pos tersebut.
        </li>
        <li>
            <strong>Penutupan</strong>
            Setelah seluruh pos diselesaikan, perolehan nilai akhir dan waktu tempuh masing-masing
            tim ditampilkan, dan pemenang diumumkan.
        </li>
    </ol>

    <h2>Dua Pilihan Format</h2>

    <p>
        Rangkaian acara dapat diselenggarakan dalam dua format. Pemilihan format menyesuaikan
        ketersediaan lokasi dan rencana kegiatan.
    </p>

    <div class="cards">
        <div class="card">
            <h3>Di dalam ruangan</h3>
            <p>
                Pos-pos ditempatkan di dalam gedung, misalnya ballroom, ruang rapat, atau area
                pameran. Petunjuk lokasi menggunakan denah ruangan.
            </p>
            <p>
                Sesuai untuk acara yang tidak bergantung pada cuaca serta kegiatan yang
                diselenggarakan menyatu dengan rangkaian acara lain di satu tempat.
            </p>
        </div>
        <div class="card">
            <h3>Di luar ruangan</h3>
            <p>
                Pos-pos ditempatkan pada titik sebenarnya di area terbuka. Aplikasi memandu tim
                menuju setiap pos dan mencatat kehadiran tim setibanya di lokasi.
            </p>
            <p>
                Sesuai untuk acara dengan area luas yang mengutamakan perpindahan dan aktivitas
                fisik peserta.
            </p>
        </div>
    </div>

    <p>
        Kedua format dapat memuat tantangan interaktif berupa objek tiga dimensi yang tampak melalui
        kamera telepon seluler, sebagai variasi tambahan pada pos tertentu.
    </p>

    <h2>Penilaian dan Hasil</h2>

    <p>
        Setiap tim memulai acara dengan sejumlah nilai awal yang sama. Nilai bertambah melalui
        tantangan yang diselesaikan dengan baik, dan dapat berkurang apabila terdapat pelanggaran
        ketentuan permainan.
    </p>

    <table>
        <tr>
            <td>Selama acara</td>
            <td>Perolehan nilai seluruh tim ditampilkan pada layar besar dan diperbarui secara
                berkala.</td>
        </tr>
        <tr>
            <td>Penentuan pemenang</td>
            <td>Berdasarkan perolehan nilai akhir. Waktu tempuh menjadi penentu apabila terdapat
                perolehan nilai yang sama.</td>
        </tr>
        <tr>
            <td>Setelah acara</td>
            <td>Laporan hasil per tim beserta rinciannya dapat diunduh oleh pihak penyelenggara.</td>
        </tr>
    </table>

    <h2>Persiapan yang Diperlukan</h2>

    <h3>Dari pihak penyelenggara acara</h3>
    <ul>
        <li>Daftar peserta beserta pembagian tim.</li>
        <li>Lokasi acara, termasuk izin penggunaan area untuk penempatan pos.</li>
        <li>Jadwal acara serta perkiraan durasi permainan.</li>
        <li>Satu layar atau proyektor untuk menampilkan perolehan nilai, apabila dikehendaki.</li>
    </ul>

    <h3>Dari peserta</h3>
    <ul>
        <li>Satu telepon seluler per tim, dengan kamera yang berfungsi.</li>
        <li>Aplikasi telah dipasang sebelum acara dimulai.</li>
    </ul>

    <div class="note">
        <p>
            <strong>Mengenai jaringan.</strong> Aplikasi membutuhkan sambungan internet. Penggunaan
            paket data masing-masing peserta lebih dianjurkan dibandingkan satu jaringan nirkabel
            bersama, karena sambungan yang terbagi di antara banyak perangkat pada waktu yang sama
            cenderung menurun kualitasnya. Materi permainan diunduh lebih dahulu pada saat aplikasi
            pertama kali dibuka, sehingga gangguan sambungan yang singkat di tengah permainan tidak
            menghentikan kegiatan.
        </p>
    </div>

    <h2>Perkiraan Susunan Hari Pelaksanaan</h2>

    <table>
        <tr><td>Sebelum acara</td><td>Pemasangan kode pada setiap pos dan penempatan fasilitator.</td></tr>
        <tr><td>Pembukaan</td><td>Pengarahan kepada peserta dan pembagian akun tim.</td></tr>
        <tr><td>Permainan</td><td>Tim menelusuri pos-pos yang telah disiapkan.</td></tr>
        <tr><td>Penutupan</td><td>Penyampaian hasil akhir dan pengumuman pemenang.</td></tr>
    </table>

    <p>
        Durasi permainan disesuaikan dengan jumlah pos, jumlah tim, dan waktu yang tersedia pada
        rangkaian acara secara keseluruhan.
    </p>

    <h2>Pendampingan</h2>

    <p>
        Penyiapan materi permainan, penempatan pos, pengawasan selama acara berlangsung, serta
        penyusunan laporan hasil ditangani oleh tim penyelenggara. Pihak klien tidak perlu
        mengoperasikan sistem mana pun.
    </p>

    <footer>
        <p>
            Dokumen ini merupakan penjelasan umum. Ketentuan pelaksanaan, jumlah pos, bentuk
            tantangan, dan susunan acara dapat disesuaikan dengan kebutuhan.
        </p>
        <p>
            {{ \App\Models\BrandSetting::appName() }} &middot; Diperbarui
            {{ now()->translatedFormat('j F Y') }}
        </p>
    </footer>

</div>
</body>
</html>
