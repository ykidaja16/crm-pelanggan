# Fix: Kelompok Pelanggan Import & Display

## Masalah
1. Import Excel: kelompok_pelanggan_id tidak disimpan → semua jadi Mandiri
2. Tabel kunjungans masih punya kolom lama `kelompok_pelanggan` (string) yang redundant
3. show.blade.php membaca kolom lama bukan relasi
4. edit-kunjungan.blade.php tidak ada field kelompok pelanggan

## TODO

- [x] 1. Fix `app/Imports/KunjunganImport.php` — tambah kelompok_pelanggan_id di Kunjungan::create()
- [x] 2. Buat migration baru — hapus kolom lama `kelompok_pelanggan` dari tabel kunjungans
- [x] 3. Fix `resources/views/pelanggan/show.blade.php` — baca via relasi kelompokPelanggan
- [x] 4. Fix `resources/views/pelanggan/edit-kunjungan.blade.php` — tambah dropdown kelompok pelanggan
- [x] 5. Fix `app/Http/Controllers/PelangganController.php` — eager-load kelompokPelanggan di show()
- [x] 6. Jalankan `php artisan migrate` — DONE (kolom lama berhasil dihapus)
