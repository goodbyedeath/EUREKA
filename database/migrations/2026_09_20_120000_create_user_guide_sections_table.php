<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The player's how-to, in plain language (operator, 20 Sep).
 *
 * Not the same thing as `guidances`, which are briefings an admin writes for particular teams
 * about this event. This is "how the app works", the same for everyone, shown on the app's Panduan
 * page and on /admin/panduan/user, and editable because the crew learns what people actually ask.
 *
 * Seeded with the whole guide so it is useful the moment it exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_guide_sections', function (Blueprint $table) {
            $table->id();
            $table->string('icon', 16)->nullable();
            $table->string('title');
            // Plain sentences, one per line. Rendered as a list; no markup to learn.
            $table->text('body');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $now = now();
        $order = 0;
        $rows = array_map(fn ($s) => [
            'icon' => $s[0],
            'title' => $s[1],
            'body' => $s[2],
            'sort_order' => ++$order * 10,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], self::sections());

        DB::table('user_guide_sections')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('user_guide_sections');
    }

    /** @return array<int, array{0: string, 1: string, 2: string}> */
    private static function sections(): array
    {
        return [
            ['🔑', 'Masuk ke aplikasi',
                "Buka aplikasi, lalu tekan \"Scan kartu login\".\n".
                "Arahkan kamera ke QR pada kartu tim Anda sampai berbunyi atau bergetar.\n".
                "Tidak punya kartu? Masukkan email dan kata sandi yang tertulis di kartu.\n".
                "Satu kartu untuk satu tim. Cukup satu HP yang dipakai bermain."],

            ['👥', 'Daftarkan anggota tim',
                "Setelah masuk pertama kali, tuliskan nama tim dan anggotanya.\n".
                "Pilih satu orang sebagai ketua tim.\n".
                "Kalau nama Anda sudah terdaftar panitia, cari saja namanya lalu pilih.\n".
                "Isian ini hanya sekali di awal dan tidak bisa diubah sendiri. Kalau keliru, minta bantuan panitia."],

            ['🚩', 'Mulai permainan: scan QR START',
                "Permainan baru berjalan setelah Anda memindai QR START di lokasi start.\n".
                "Waktu tim mulai berjalan begitu QR itu dipindai.\n".
                "Kalau tidak sengaja memindai dua kali, waktu tidak akan diulang.\n".
                "Sebelum scan START, pos dan pertanyaan belum bisa dibuka."],

            ['🧩', 'Jawab teka-teki pembuka',
                "Untuk permainan di dalam ruangan, setelah START akan muncul satu teka-teki.\n".
                "Jawab dengan huruf besar atau kecil, bebas; spasi berlebih tidak masalah.\n".
                "Salah menjawab tidak menghukum apa pun. Coba lagi.\n".
                "Setelah benar, denah lokasi terbuka dan pos-posnya bisa dilihat."],

            ['🗺️', 'Cari pos',
                "Di dalam ruangan: buka denah, lalu datangi pos yang ditandai terbuka.\n".
                "Di luar ruangan: buka peta, jalan ke pos terdekat, lalu tekan check-in saat sudah sampai.\n".
                "Pos yang masih terkunci berarti panitia belum membukanya untuk tim Anda. Tunggu aba-aba panitia."],

            ['📷', 'Scan QR di pos lalu jawab pertanyaan',
                "Sampai di pos, tekan Scan lalu arahkan kamera ke QR pos tersebut.\n".
                "Pertanyaan akan muncul satu per satu. Jawab sampai selesai.\n".
                "Selama sesi pertanyaan berlangsung, Anda tidak bisa keluar. Selesaikan dulu.\n".
                "Kalau ada batas waktu, waktu habis berarti sesi selesai dan jawaban yang sudah masuk tetap dihitung."],

            ['🎮', 'Pos permainan',
                "Sebagian pos berisi permainan, bukan pertanyaan tertulis.\n".
                "Mainkan sesuai aba-aba panitia di pos itu.\n".
                "Nilainya diisi panitia di HP tim Anda, memakai PIN mereka.\n".
                "Foto bukti diambil otomatis saat penilaian. Itu normal."],

            ['🧊', 'Kamera 3D',
                "Di pos tertentu akan ada kamera 3D: arahkan HP ke sekeliling, lalu cari benda yang muncul di layar.\n".
                "Ketuk benda itu untuk melihat petunjuk atau mendapat poin.\n".
                "Berdirilah di tempat yang panitia tunjukkan supaya bendanya muncul di posisi yang benar.\n".
                "Kalau layar gelap, pastikan izin kamera sudah diberikan."],

            ['🏆', 'Skor tim',
                "Skor yang tampil adalah skor tim, bukan skor perorangan.\n".
                "Skor berasal dari jawaban benar ditambah nilai dari panitia di pos permainan.\n".
                "Nilai dari panitia bisa mengurangi skor kalau ada pelanggaran.\n".
                "Riwayat menampilkan tiap pos yang sudah diselesaikan beserta poinnya."],

            ['📶', 'Kalau sinyal hilang',
                "Sebelum berangkat, tekan Sync di aplikasi selagi masih ada sinyal.\n".
                "Jawaban dan check-in yang dibuat tanpa sinyal akan disimpan dan terkirim sendiri saat sinyal kembali.\n".
                "Jangan menutup paksa aplikasi saat menunggu terkirim.\n".
                "Peta dan gambar yang sudah di-sync tetap bisa dibuka tanpa sinyal."],

            ['🛠️', 'Kalau ada masalah',
                "Layar diam atau aneh: tutup aplikasi lalu buka lagi. Kemajuan tim tidak hilang.\n".
                "QR tidak terbaca: bersihkan lensa, jauhkan sedikit, dan hindari pantulan cahaya.\n".
                "Tertulis \"scan START dulu\": berarti tim Anda belum memindai QR START.\n".
                "Tertulis \"tunggu panitia\": pos itu belum dibuka untuk tim Anda.\n".
                "Kalau tetap tidak bisa, panggil panitia terdekat. Jangan memakai HP lain, karena satu tim satu HP."],
        ];
    }
};
