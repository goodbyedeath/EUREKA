<?php

return [
    // Authentication
    'login' => 'Masuk',
    'register' => 'Daftar',
    'logout' => 'Keluar',
    'email' => 'Email',
    'password' => 'Kata Sandi',
    'confirm_password' => 'Konfirmasi Kata Sandi',
    'remember_me' => 'Ingat saya',
    'forgot_password' => 'Lupa kata sandi Anda?',
    'reset_password' => 'Reset Kata Sandi',
    'send_password_reset_link' => 'Kirim Link Reset Kata Sandi',
    'reset_password_notification' => 'Anda menerima email ini karena kami menerima permintaan reset kata sandi untuk akun Anda.',
    'reset_password_action' => 'Reset Kata Sandi',
    'reset_password_expire' => 'Link reset kata sandi ini akan kedaluwarsa dalam :count menit.',
    'reset_password_trouble' => 'Jika Anda mengalami masalah dengan tombol "Reset Kata Sandi", salin dan tempel URL di bawah ini ke browser web Anda:',

    // Registration
    'already_registered' => 'Sudah terdaftar?',
    'register_new_account' => 'Daftarkan akun baru',
    'full_name' => 'Nama Lengkap',
    'create_account' => 'Buat Akun',
    'registration_successful' => 'Pendaftaran berhasil! Silakan verifikasi alamat email Anda.',

    // Email Verification
    'verify_email' => 'Verifikasi Alamat Email',
    'verify_email_sent' => 'Link verifikasi baru telah dikirim ke alamat email Anda.',
    'verify_email_before_continuing' => 'Sebelum melanjutkan, bisakah Anda memverifikasi alamat email Anda dengan mengklik link yang baru saja kami kirimkan?',
    'resend_verification_email' => 'Kirim Ulang Email Verifikasi',
    'email_verified' => 'Alamat email berhasil diverifikasi!',

    // Passwords
    'current_password' => 'Kata Sandi Saat Ini',
    'new_password' => 'Kata Sandi Baru',
    'confirm_new_password' => 'Konfirmasi Kata Sandi Baru',
    'update_password' => 'Perbarui Kata Sandi',
    'password_updated' => 'Kata sandi berhasil diperbarui.',
    'ensure_secure_password' => 'Pastikan akun Anda menggunakan kata sandi yang panjang dan acak agar tetap aman.',

    // Profile
    'profile_information' => 'Informasi Profil',
    'update_profile' => 'Perbarui Profil',
    'profile_updated' => 'Profil berhasil diperbarui.',
    'delete_account' => 'Hapus Akun',
    'delete_account_warning' => 'Setelah akun Anda dihapus, semua sumber daya dan data akan dihapus secara permanen. Sebelum menghapus akun, silakan unduh data atau informasi yang ingin Anda simpan.',
    'delete_account_confirmation' => 'Apakah Anda yakin ingin menghapus akun Anda? Setelah akun dihapus, semua sumber daya dan data akan dihapus secara permanen. Silakan masukkan kata sandi Anda untuk mengkonfirmasi bahwa Anda ingin menghapus akun secara permanen.',

    // Validation Messages
    'failed' => 'Kredensial ini tidak cocok dengan catatan kami.',
    'password' => 'Kata sandi yang diberikan salah.',
    'throttle' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam :seconds detik.',
    'email_required' => 'Alamat email wajib diisi.',
    'email_invalid' => 'Silakan masukkan alamat email yang valid.',
    'password_required' => 'Kata sandi wajib diisi.',
    'password_min' => 'Kata sandi minimal 8 karakter.',
    'password_confirmed' => 'Konfirmasi kata sandi tidak cocok.',
    'name_required' => 'Nama wajib diisi.',

    // Two Factor Authentication (if needed)
    'two_factor_authentication' => 'Autentikasi Dua Faktor',
    'two_factor_code' => 'Kode Autentikasi',
    'use_recovery_code' => 'Gunakan kode pemulihan',
    'recovery_code' => 'Kode Pemulihan',

    // Success Messages
    'login_successful' => 'Login berhasil! Selamat datang kembali.',
    'logout_successful' => 'Anda telah berhasil keluar.',
    'password_reset_sent' => 'Link reset kata sandi telah dikirim ke email Anda.',
    'password_reset_successful' => 'Reset kata sandi berhasil. Anda sekarang dapat login dengan kata sandi baru.',

    // Error Messages
    'login_failed' => 'Login gagal. Silakan periksa kredensial Anda.',
    'registration_failed' => 'Pendaftaran gagal. Silakan coba lagi.',
    'email_not_verified' => 'Alamat email Anda belum diverifikasi.',
    'account_disabled' => 'Akun Anda telah dinonaktifkan.',
    'session_expired' => 'Sesi Anda telah kedaluwarsa. Silakan login lagi.',
];