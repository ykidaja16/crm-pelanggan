# TODO Revisi 8 - Validasi Pelanggan Khusus di Menu Data Pelanggan

## Task
1. Import Excel di menu Data Pelanggan hanya untuk pelanggan biasa
   - Jika format file adalah format pelanggan khusus (12 kolom + Kategori Khusus) → tolak
2. Tambah Pelanggan manual di menu Data Pelanggan hanya untuk pelanggan biasa
   - Pelanggan Lama: jika PID yang dicari adalah pelanggan khusus → tampilkan warning di UI

## Steps

- [x] 1. `app/Http/Controllers/PelangganImportExportController.php`
  - Tambah validasi format file: deteksi 12 kolom (format pelanggan khusus)
  - Jika terdeteksi format khusus → tolak dengan pesan error 422
  - Juga ada per-row check: jika PID sudah ada sebagai pelanggan khusus → tolak baris tersebut

- [x] 2. `app/Imports/KunjunganImport.php`
  - Tambah pengecekan format di processAllRows() sebagai defense in depth
  - Throws \Exception jika 12 kolom terdeteksi

- [x] 3. `resources/views/pelanggan/index.blade.php`
  - Import card header: badge "Pelanggan Biasa" (bg-success) + warning badge (bg-warning)
  - Alert-info di dalam card-body: keterangan import hanya untuk pelanggan biasa (11 kolom)
  - Link ke route('pelanggan.khusus') untuk redirect ke menu yang benar

- [x] 4. `resources/views/pelanggan/create.blade.php`
  - Tambah div `#search_khusus_warning` (alert-warning) setelah `#search_not_found`
  - JS searchPelanggan(): cek `data.pelanggan.is_pelanggan_khusus` → tampilkan warning, kosongkan hidden fields
  - JS toggleMode('new'): reset `#search_khusus_warning` saat switch ke mode baru
  - Backend (PelangganController::store): sudah ada validasi untuk mode existing & new

## Status: ✅ SELESAI
