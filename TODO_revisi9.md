# TODO Revisi 9 - 5 Revisi Besar

## Revisi 1: Special Day Member → 2 Submenu
- [ ] Update `app/Http/Controllers/SpecialDayController.php` - tambah method birthday(), birthdayExport(), kunjunganTerakhir(), kunjunganTerakhirExport()
- [ ] Buat `resources/views/special-day/birthday.blade.php` - view Birthday Reminder
- [ ] Buat `resources/views/special-day/kunjungan-terakhir.blade.php` - view Kunjungan Terakhir
- [ ] Update `app/Exports/SpecialDayExport.php` - support filter baru
- [ ] Update `routes/web.php` - tambah 4 route baru
- [ ] Update `resources/views/layouts/main.blade.php` - Special Day jadi dropdown 2 submenu

## Revisi 2: Dashboard Pelanggan - Total Biaya Kumulatif
- [ ] Update `app/Http/Controllers/PelangganController.php` - index() pakai subquery kumulatif untuk total_biaya & total_kedatangan per periode

## Revisi 3: Button Naikan Kelas + Approval Naik Kelas
- [ ] Update `resources/views/pelanggan/index.blade.php` - tambah button Naikan Kelas + modal
- [ ] Update `app/Http/Controllers/PelangganController.php` - tambah requestNaikKelas()
- [ ] Update `app/Http/Controllers/ApprovalRequestController.php` - tambah indexNaikKelas() + handle approve naik_kelas
- [ ] Buat `resources/views/approval-requests/naik-kelas.blade.php`
- [ ] Update `routes/web.php` - tambah route naik kelas
- [ ] Update `resources/views/layouts/main.blade.php` - tambah submenu Approval Naik Kelas

## Revisi 4: Konsistensi Design Approval
- [ ] Update `resources/views/approval-requests/pelanggan-khusus.blade.php` - approve/reject di dalam modal
- [ ] Update `resources/views/approval-requests/kunjungan.blade.php` - approve/reject di dalam modal

## Revisi 5: Export Riwayat Kunjungan di Detail Pelanggan
- [ ] Buat `app/Exports/KunjunganExport.php` - class export baru
- [ ] Update `app/Http/Controllers/PelangganController.php` - tambah exportKunjungan()
- [ ] Update `resources/views/pelanggan/show.blade.php` - tambah tombol Export Excel
- [ ] Update `routes/web.php` - tambah route export kunjungan
