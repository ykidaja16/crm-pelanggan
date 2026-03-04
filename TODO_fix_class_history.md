# TODO: Fix Tanggal Perubahan di Riwayat Perubahan Kelas

## Masalah
`changed_at` di tabel `pelanggan_class_histories` sebelumnya diisi dari Tanggal Kunjungan.
Harus konsisten menggunakan waktu saat user melakukan perubahan (`now()`).

## Progress

- [x] Buat TODO file
- [x] Fix `app/Models/Pelanggan.php` — 3 method: `updateStats()`, `recordInitialClass()`, `updateBiayaAndClass()`
- [x] Fix `app/Http/Controllers/PelangganController.php` — method `processCsvImport()`
- [x] Fix `app/Imports/KunjunganImport.php` — method `processRow()`
