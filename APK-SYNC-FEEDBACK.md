# Umpan balik untuk tim APK — sinkronisasi dengan server

Tanggal: 10 September 2026 · Ditulis setelah membaca `APK/Eureka Client APK/` dan membandingkan
baris demi baris dengan pengendali Laravel yang berjalan di `https://questerra-series.com`.

Ringkas: **klien Anda sudah selaras dengan server pada hampir semua hal.** Yang di bawah ini
dibagi menjadi empat: apa yang sudah benar, apa yang kami ubah di server sehingga Anda perlu
menyesuaikan, kesalahan dokumentasi kami, dan temuan di repo Anda.

---

## 1. Sudah selaras — tidak perlu diubah

Diverifikasi, bukan diasumsikan:

- **Seluruh 17 kunci `error` yang dikenali `ApiError.kt` benar-benar dikirim server.** Kami cek
  satu per satu terhadap pengendali. Tidak ada yang mengada-ada, tidak ada yang tertinggal.
- `postPosition()` sudah mengirim `accuracy` dan `device_info`, dan keduanya memang disimpan.
- `checkIn()` sudah memakai `user_latitude` / `user_longitude` — nama yang benar, bukan
  `latitude` / `longitude`.
- `submitQuiz()` sudah mengirim `verification_photo`. Server memang mewajibkannya.
- `questLocations(lat, lng)` sudah mengirim posisi sebagai query, sehingga `distance` dan
  `within_radius` terisi.
- Token di `EncryptedSharedPreferences`, bukan penyimpanan yang bisa diperiksa.
- `LenientDouble.kt` menangani lintang/bujur yang bertipe string di satu endpoint dan angka di
  endpoint lain. Itu memang perlu.

---

## 2. Perubahan server yang perlu Anda sesuaikan

### a. `POST /quest-locations/checkin` — penolakan kini bisa dibaca mesin

**Ini yang paling berdampak bagi Anda.** Sebelumnya penolakan menjawab **HTTP 200** dengan
`success: false` dan hanya kalimat prosa — tanpa `error`, tanpa angka. Artinya gate
`OUT_OF_RANGE` dan `MAX_ATTEMPTS_REACHED` di `ApiError.kt` **tidak pernah bisa menyala**, dan
klien yang bercabang berdasarkan status HTTP justru membaca check-in gagal sebagai berhasil.

Sekarang:

```
HTTP 403
{ "success": false, "error": "out_of_range",
  "distance": 111194.9, "radius": 20,
  "message": "You're 111195m away. Get within 20m to check in." }

HTTP 409
{ "success": false, "error": "max_attempts_reached",
  "limit": 20, "message": "Maximum check-ins reached for this location." }
```

Bentuknya kini sama dengan `out_of_range` di endpoint AR, jadi gate matrix Anda yang sudah ada
langsung bekerja. Komentar di `ApiError.kt` yang menyebut *"Body carries `distance` and `radius`"*
kini benar — sebelumnya belum.

### b. `POST /tracking/position` — batas turun menjadi 30/menit

Dari `throttle:api` (120/menit) menjadi `throttle:tracking` (**30/menit per pemain**). Komentar di
`EurekaApi.kt` sudah menyebut angka ini, jadi tampaknya sudah Anda ketahui — mohon pastikan
`TrackingService` benar-benar menerapkan jeda minimum **10 detik** antar pengiriman. Satu fix per
detik menghabiskan kuota dalam dua menit.

### c. `accuracy` di check-in sekarang divalidasi

`accuracy` dan `device_info` kini punya aturan validasi (`nullable|numeric|min:0` dan
`nullable|string|max:255`). Sebelumnya `accuracy` masuk langsung ke kolom desimal tanpa aturan,
sehingga nilai non-numerik menghasilkan galat database yang muncul sebagai **500**. Sekarang
menjadi **422** yang menyebut nama field-nya.

### d. Endpoint baru

- **`POST /api/v1/quiz/complete-game`** — sudah Anda bungkus, bagus. Tanpa ini soal `fun_game`
  tidak bisa diselesaikan.
- **`POST /api/v1/quest-locations/get-route`** — belum Anda bungkus, dan itu wajar: fitur petunjuk
  arah berjalan kaki ini **mati** sampai `OPENROUTE_API_KEY` diisi di server. Saat dipanggil
  sekarang ia menjawab `success: false`, bukan error. Tunda saja.

### e. `POST /auth/login` kini juga dibatasi per alamat IP

`TokenController` sudah membatasi 5 percobaan per email+IP sendiri. Yang ditambahkan adalah plafon
per alamat (`throttle:auth`, 60/menit per IP), supaya satu koneksi tidak bisa menyebar tebakan ke
banyak akun. Dampak bagi Anda: di venue dengan satu WiFi bersama, percobaan login berulang dari
banyak perangkat kini bisa kena 429 — tangani dengan `Retry-After`.

### f. Respons access window kini punya kunci `error`

`EnsureAccessWindow` sekarang mengirim `error: "access_window_expired"` **berdampingan** dengan dua
boolean lama (`expired`, `access_window_expired`). Aditif — build Anda yang sekarang tetap jalan.

---

## 3. Kesalahan pada dokumen yang kami serahkan

Mohon perbarui salinan Anda.

**`POST /quiz/submit` mewajibkan `verification_photo`.** Kami menuliskannya sebagai
`{ attempt_id }` saja di `API-V1-CONTRACT.md` dan `APK-BUILD-GUIDE.md`. Itu salah: aturannya
`required|string` (base64). Klien yang mengikuti dokumen kami akan mendapat 422 dan **tim tidak
bisa mengumpulkan jawaban sama sekali**. Anda sudah menemukannya sendiri dari pengendali — kebijakan
"controllers win" di `CLAUDE.md` Anda terbukti tepat.

Kedua dokumen sudah diperbaiki dan kini memuat tabel *"Request bodies that are easy to get wrong"*.

**Catatan tentang ukuran foto:** kolomnya `longtext` dan `post_max_size` server longgar, jadi
database bukan batasnya — **jaringan venue yang jadi batas.** Tidak ada batas maksimum di server
hari ini, jadi klien adalah satu-satunya yang menentukan besar unggahan itu, dan unggahan itu
terjadi pada saat paling buruk: satu tim sedang menunggu. Mohon perkecil resolusi sebelum
meng-encode. Kalau Anda ingin kami memasang batas di server, beri tahu angkanya — kami tidak
memasangnya sendiri karena menolak submit di tengah acara jauh lebih merugikan daripada baris
database yang besar.

---

## 4. Temuan di repo Anda

### a. Kunci penandatanganan rilis berisiko ter-commit — sudah kami tangani

Proyek APK berada **di dalam repo git Laravel**. `.gitignore` Laravel punya `/node_modules`, tetapi
garis miring di depan mengikat aturan itu ke akar repo sehingga tidak pernah cocok dengan
`APK/<proyek>/node_modules`. Satu `git add .` akan mengikutkan:

```
android/keystore.properties          ← storePassword, keyPassword
android/keystore/eureka-release.jks  ← kunci penandatanganannya
1.829 berkas node_modules (30 MB)
```

Kunci penandatanganan yang bocor **tidak bisa dirotasi**: begitu diganti, setiap salinan yang sudah
terpasang tidak bisa diperbarui, karena Android menolak pembaruan bertanda tangan berbeda.

Kami menambahkan `APK/.gitignore` dan mengaktifkan aturan keystore di `android/.gitignore` Anda —
dua lapis, supaya perlindungannya ikut bila proyek dikeluarkan dari repo ini. Hasilnya: dari 1.923
berkas menjadi 93, tanpa satu pun rahasia.

Berkas itu **tidak pernah terbuka dari web** (`keystore.properties` menjawab 404), jadi yang
berisiko hanya jalur git. **Mohon terapkan aturan yang sama di repo asli Anda** — perbaikan kami
hanya berlaku pada salinan di server ini.

### b. Perancah Capacitor sudah jadi bangkai, dan satu skrip rusak

`CLAUDE.md` Anda menyatakan *"Capacitor was removed deliberately"*, dan kami konfirmasi: tidak ada
rujukan Capacitor di Gradle, di Kotlin, maupun di `AndroidManifest.xml`. Tetapi masih ada
`capacitor.config.json`, `package.json`, `package-lock.json`, `www/index.html`, dan `node_modules`.

Lebih dari sekadar berat: **`npm run build` memanggil `scripts/build-web.mjs` yang tidak ada**,
jadi `npm run build` dan `npm run sync` keduanya gagal. Satu-satunya skrip yang berfungsi adalah
`scripts/publish-apk.sh`.

Kami tidak menghapusnya — itu salinan repo Anda, dan repo aslinya ada di mesin lain. Saran: hapus
kelima item itu, atau rapikan `package.json` agar hanya menyisakan skrip yang benar-benar ada.

---

## 5. Hal yang perlu dijaga ke depan

- **Jangan pernah bercabang hanya berdasarkan status HTTP.** Beberapa kondisi berbeda berbagi 403.
  Selalu baca `error`. Prinsip ini sudah benar di `ApiError.kt`; pertahankan.
- **Jam adalah milik server.** Baca `/quiz/timer/{attemptId}`; jangan putuskan sendiri bahwa waktu
  habis. `save-answer` menolak setelah lewat waktu, tetapi `submit` **selalu** diterima — jadi pada
  `time_expired`, langsung submit, jangan tampilkan error.
- **Jangan menjumlahkan poin di sisi klien.** Jawaban `fun_game` disimpan benar dengan nilai **nol**
  karena fasilitator menilainya kemudian. Menjumlahkan sendiri akan menghitung ganda.
- **Token kedaluwarsa bersama access window.** `expires_at` pada respons login memberi waktunya.
  Login ulang adalah jalur normal, bukan keadaan galat.
- **Nama merek dan logo dibaca dari `/branding`**, jangan ditanam di kode. Kolom `version` berubah
  setiap kali merek diganti — pakai itu sebagai kunci cache.

Bila ada endpoint yang terasa tidak sesuai dokumen, **pengendali yang menang** — dan beri tahu
kami, karena itu berarti dokumennya yang salah.
