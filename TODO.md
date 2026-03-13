# TODO: Restrukturisasi Menu Data Pelanggan

## Steps

- [x] Analisis file `resources/views/layouts/main.blade.php`
- [x] Cek semua link yang terdampak di views lain
- [x] Edit `resources/views/layouts/main.blade.php`:
  - [x] Tambah CSS untuk `submenu-pelanggan` dan `pelanggan-toggle`
  - [x] Ganti menu flat `Data Pelanggan` + `Pelanggan Khusus` menjadi parent collapsible dengan 3 sub-menu:
    1. Dashboard Pelanggan → `pelanggan.index`
    2. Input Data Pelanggan → `pelanggan.create` (Admin/Super Admin)
    3. Pelanggan Khusus → `pelanggan.khusus.index` (Admin/Super Admin)
- [x] Verifikasi tampilan di browser

## Notes
- Route names tidak berubah, hanya struktur sidebar
- Links di `pelanggan/index.blade.php`, `pelanggan/create.blade.php`, `pelanggan/show.blade.php` tidak perlu diubah
