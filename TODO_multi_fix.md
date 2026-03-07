# TODO: Multi-Feature Fix

## Tasks

- [ ] 1. `resources/views/pelanggan/khusus.blade.php` — Tambah field DOB, Alamat, Kota, Biaya, Tanggal Kunjungan; tambah "Lainnya" di Kategori Khusus; tambah tombol download template; update info format import
- [ ] 2. `app/Http/Controllers/ApprovalRequestController.php` — Update validasi storeSpecialCustomerRequest (biaya, tanggal); update storeSpecialCustomerImportRequest (12 kolom); tambah downloadTemplateKhusus()
- [ ] 3. `routes/web.php` — Tambah route GET /download-template-khusus
- [ ] 4. `resources/views/pelanggan/create.blade.php` — Hapus field Kelompok Pelanggan dari section Data Pelanggan Baru; update JS toggleMode()
- [ ] 5. `resources/views/pelanggan/show.blade.php` — Tambah kolom "Diubah Tanggal" di Riwayat Kelas; tambah status pending di Riwayat Kunjungan; kunci tombol jika pending; tambah section Riwayat Pengajuan Perubahan
- [ ] 6. `app/Http/Controllers/PelangganController.php` — Update show() untuk load pending approvals dan semua approval requests per kunjungan
