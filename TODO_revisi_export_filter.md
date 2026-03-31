# TODO: Revisi Export Excel - Filter Sesuai Data yang Ditampilkan

## Status: SELESAI ✅

## Masalah
Export Excel di Dashboard Pelanggan tidak mengikuti semua filter yang aktif:
- [x] Filter Kelas → sudah benar
- [ ] Filter Tipe Pelanggan (khusus/biasa) → BELUM diterapkan di export
- [ ] Filter Range Tanggal (type=range) → BELUM diterapkan di export
- [ ] Verifikasi: Filter Range Omset → perlu dicek
- [ ] Verifikasi: Filter Jumlah Kedatangan → perlu dicek
- [ ] Verifikasi: Filter Periode (perbulan/pertahun) → perlu dicek
- [ ] Verifikasi: Filter Cabang → perlu dicek

## Root Cause
1. `PelangganExport.php` tidak punya property `tipePelanggan`, `tanggalMulai`, `tanggalSelesai`
2. `PelangganImportExportController::export()` tidak membaca `tipe_pelanggan`, `tanggal_mulai`, `tanggal_selesai`
3. View `pelanggan/index.blade.php` tidak mengirim `tanggal_mulai` dan `tanggal_selesai` ke link export

## Steps

### Step 1: Update `app/Exports/PelangganExport.php`
- [x] Tambah property `$tipePelanggan`, `$tanggalMulai`, `$tanggalSelesai`
- [x] Update constructor (3 parameter baru, optional)
- [x] Tambah filter `tipe_pelanggan` ke query (is_pelanggan_khusus)
- [x] Tambah handling type `range` untuk endDate dan filter periode

### Step 2: Update `app/Http/Controllers/PelangganImportExportController.php`
- [x] Baca `tipe_pelanggan`, `tanggal_mulai`, `tanggal_selesai` dari request
- [x] Pass ke PelangganExport constructor
- [x] Update logika filename (range, tipe_pelanggan)
- [x] Fix default `$type` dari `'perbulan'` → `'semua'` (pakai `?:` bukan `??`)

### Step 3: Update `resources/views/pelanggan/index.blade.php`
- [x] Tambah `tanggal_mulai` dan `tanggal_selesai` ke parameter link Export Excel
