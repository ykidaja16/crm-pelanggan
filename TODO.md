# TODO - Perbaikan Tanggal Riwayat Perubahan Kelas + Alasan Edit/Hapus Kunjungan

## A. Tanggal Riwayat Perubahan Kelas
- [x] Update `app/Models/Pelanggan.php`
  - [x] Ubah `updateStats()` agar `changed_at` pakai `$visitDate` jika tersedia, fallback `now()`
  - [x] Ubah `recordInitialClass()` agar `changed_at` pakai `$visitDate` jika tersedia, fallback `now()`
  - [x] Ubah `updateBiayaAndClass()` agar `changed_at` pakai `$visitDate` jika tersedia, fallback `now()`

- [x] Update `app/Imports/KunjunganImport.php`
  - [x] Saat class berubah, set `changed_at` ke `$tanggalKedatangan` (tanggal kunjungan dari file), bukan `now()`

- [x] Update `app/Http/Controllers/PelangganController.php`
  - [x] Pada `processCsvImport()`, saat class berubah untuk pelanggan existing, set `changed_at` ke `$data['tanggal_kunjungan']`, bukan `now()`

## B. Wajib Alasan Edit/Hapus Riwayat Kunjungan
- [x] Update `resources/views/pelanggan/edit-kunjungan.blade.php`
  - [x] Tambah field wajib `alasan_perubahan`
- [x] Update `resources/views/pelanggan/show.blade.php`
  - [x] Tambah modal hapus dengan field wajib `alasan_hapus`
- [x] Update `app/Http/Controllers/PelangganController.php`
  - [x] `updateKunjungan`: validasi `alasan_perubahan` wajib + catat ke ActivityLog
  - [x] `destroyKunjungan`: terima `Request`, validasi `alasan_hapus` wajib + catat ke ActivityLog
