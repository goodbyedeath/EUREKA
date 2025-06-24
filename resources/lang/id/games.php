<?php

return [
    // Page titles and headers
    'title' => 'Lokasi Permainan',
    'management' => 'Manajemen Permainan',
    'create' => 'Buat Lokasi Permainan',
    'edit' => 'Edit Lokasi Permainan',
    'view' => 'Lihat Lokasi Permainan',
    
    // Actions
    'add_location' => 'Tambah Lokasi Permainan',
    'save_location' => 'Simpan Lokasi',
    'update_location' => 'Perbarui Lokasi',
    'delete_location' => 'Hapus Lokasi',
    'view_details' => 'Lihat Detail',
    'activate' => 'Aktifkan',
    'deactivate' => 'Nonaktifkan',
    'bulk_activate' => 'Aktifkan Massal',
    'bulk_deactivate' => 'Nonaktifkan Massal',
    'bulk_delete' => 'Hapus Massal',
    
    // Form fields
    'name' => 'Nama Permainan',
    'name_placeholder' => 'Masukkan nama lokasi permainan yang unik',
    'description' => 'Deskripsi',
    'description_placeholder' => 'Jelaskan lokasi dan apa yang dapat diharapkan pemain (minimal 10 karakter)',
    'what_to_do' => 'Apa yang harus dilakukan',
    'what_to_do_placeholder' => 'Berikan instruksi yang jelas untuk pemain tentang apa yang perlu mereka lakukan di lokasi ini (minimal 10 karakter)',
    'google_maps_url' => 'URL Embed Google Maps',
    'google_maps_url_placeholder' => 'Tempel URL embed Google Maps atau kode iframe',
    'latitude' => 'Lintang',
    'longitude' => 'Bujur',
    'coordinate_x' => 'Koordinat X Peta',
    'coordinate_y' => 'Koordinat Y Peta',
    'radius' => 'Radius (meter)',
    'quest_points' => 'Poin Quest',
    'max_check_ins' => 'Maksimal Check-in per Pengguna',
    'regular_image' => 'Gambar Biasa',
    'map_image' => 'Gambar Peta',
    'is_active' => 'Aktif',
    
    // Table headers
    'game' => 'Permainan',
    'images' => 'Gambar',
    'coordinates' => 'Koordinat',
    'points' => 'Poin',
    'status' => 'Status',
    'actions' => 'Aksi',
    
    // Status
    'active' => 'Aktif',
    'inactive' => 'Tidak Aktif',
    'regular' => 'Biasa',
    'map' => 'Peta',
    
    // Coordinates
    'gps_coordinates' => 'Koordinat GPS',
    'map_coordinates' => 'Koordinat Peta',
    'extract' => 'Ekstrak',
    'extract_coordinates' => 'Ekstrak Koordinat',
    
    // Messages
    'no_locations_found' => 'Tidak ada lokasi permainan ditemukan',
    'create_first_location' => 'Buat lokasi permainan pertama Anda untuk memulai.',
    'location_created' => 'Lokasi permainan berhasil dibuat!',
    'location_updated' => 'Lokasi permainan berhasil diperbarui!',
    'location_deleted' => 'Lokasi permainan berhasil dihapus!',
    'locations_activated' => ':count lokasi permainan berhasil diaktifkan!',
    'locations_deactivated' => ':count lokasi permainan berhasil dinonaktifkan!',
    'locations_deleted' => ':count lokasi permainan berhasil dihapus!',
    'select_at_least_one' => 'Silakan pilih setidaknya satu lokasi permainan.',
    'selected_count' => ':count lokasi permainan dipilih',
    
    // Image management
    'regular_image_removed' => 'Gambar biasa berhasil dihapus!',
    'map_image_removed' => 'Gambar peta berhasil dihapus!',
    'regular_image_marked_for_removal' => 'Gambar biasa ditandai untuk dihapus (akan dihapus saat Anda menyimpan)',
    'map_image_marked_for_removal' => 'Gambar peta ditandai untuk dihapus (akan dihapus saat Anda menyimpan)',
    'regular_image_removal_cancelled' => 'Penghapusan gambar biasa dibatalkan',
    'map_image_removal_cancelled' => 'Penghapusan gambar peta dibatalkan',
    'remove' => 'Hapus',
    'undo_remove' => 'Batal Hapus',
    'current_image' => 'Gambar Saat Ini',
    'current_map' => 'Peta Saat Ini',
    
    // Coordinate extraction
    'coordinates_extracted' => 'Koordinat berhasil diekstrak: :coordinates',
    'coordinates_extract_error' => 'Tidak dapat mengekstrak koordinat dari URL. Silakan periksa format URL atau masukkan koordinat secara manual.',
    'enter_url_first' => 'Silakan masukkan URL Google Maps terlebih dahulu.',
    'invalid_url_format' => 'Format URL tidak valid.',
    'coordinates_out_of_range' => 'Koordinat yang diekstrak berada di luar rentang yang valid.',
    'url_processing_error' => 'Kesalahan memproses URL: :error',
    
    // Search and filter
    'search_locations' => 'Cari lokasi...',
    'all_status' => 'Semua Status',
    'filter_active' => 'Aktif',
    'filter_inactive' => 'Tidak Aktif',
    
    // Interactive map
    'interactive_map' => 'Peta Interaktif',
    'interactive_positioning' => 'Posisi Interaktif',
    'interactive_map_controls' => 'Kontrol Peta Interaktif',
    'no_map_available' => 'Tidak ada peta interaktif tersedia',
    'map_instructions_1' => 'Klik dan seret untuk menggeser • Gulir untuk zoom • Klik dua kali untuk zoom',
    'map_instructions_2' => 'Masuk layar penuh untuk kontrol yang ditingkatkan dengan indikator zoom',
    'map_instructions_3' => 'Keyboard: Spasi = layar penuh, + = zoom in, - = zoom out, R = reset',
    'zoom_in' => 'Perbesar',
    'zoom_out' => 'Perkecil',
    'reset_view' => 'Reset Tampilan',
    'toggle_fullscreen' => 'Toggle Layar Penuh',
    
    // Location details
    'location_information' => 'Informasi Lokasi',
    'gps_label' => 'GPS',
    'map_coordinates_label' => 'Koordinat Peta',
    'quest_points_label' => 'Poin Quest',
    'checkin_radius' => 'radius check-in',
    'open_in_google_maps' => 'Buka di Google Maps',
    'close_details' => 'Tutup Detail',
    
    // Validation messages
    'name_required' => 'Nama permainan wajib diisi.',
    'name_unique' => 'Lokasi permainan dengan nama ini sudah ada.',
    'description_min' => 'Deskripsi harus minimal 10 karakter.',
    'what_to_do_min' => 'Apa yang harus dilakukan harus minimal 10 karakter.',
    'latitude_required' => 'Lintang wajib diisi.',
    'longitude_required' => 'Bujur wajib diisi.',
    'latitude_between' => 'Lintang harus antara -90 dan 90.',
    'longitude_between' => 'Bujur harus antara -180 dan 180.',
    'radius_required' => 'Radius wajib diisi.',
    'radius_min' => 'Radius harus minimal 10 meter.',
    'radius_max' => 'Radius tidak boleh melebihi 1000 meter.',
    'quest_points_required' => 'Poin quest wajib diisi.',
    'quest_points_min' => 'Poin quest tidak boleh negatif.',
    'quest_points_max' => 'Poin quest tidak boleh melebihi 1000.',
    'image_max' => 'Gambar biasa tidak boleh lebih dari 2MB.',
    'map_image_max' => 'Gambar peta tidak boleh lebih dari 5MB.',
    'image_mimes' => 'Gambar harus berupa file JPEG, PNG, JPG, atau WebP.',
    'coordinate_positive' => 'Koordinat harus positif.',
    'max_check_ins_min' => 'Maksimal check-in harus minimal 1.',
    'max_check_ins_max' => 'Maksimal check-in tidak boleh melebihi 100.',
    
    // Error messages
    'upload_failed' => 'Gagal mengunggah gambar: :error',
    'save_failed' => 'Gagal menyimpan lokasi permainan: :error',
    'delete_failed' => 'Gagal menghapus lokasi permainan: :error',
    'remove_image_failed' => 'Gagal menghapus gambar: :error',
    'activate_failed' => 'Gagal mengaktifkan lokasi permainan: :error',
    'deactivate_failed' => 'Gagal menonaktifkan lokasi permainan: :error',
    'bulk_delete_failed' => 'Gagal menghapus lokasi permainan: :error',
    
    // Confirmation messages
    'confirm_delete' => 'Apakah Anda yakin ingin menghapus lokasi permainan ini?',
    'confirm_bulk_delete' => 'Apakah Anda yakin ingin menghapus lokasi permainan yang dipilih?',
    
    // Additional user-facing messages
    'explore_outdoor_locations' => 'Jelajahi lokasi luar ruangan dengan peta interaktif dan informasi detail.',
    'admin_will_create_locations' => 'Lokasi permainan akan muncul di sini ketika dibuat oleh administrator.',
    'outdoor_game_locations' => 'Lokasi Permainan Luar Ruangan',
];