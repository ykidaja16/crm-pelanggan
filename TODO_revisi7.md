# TODO Revisi 7 - Medical Lab CRM

## Status: IN PROGRESS

---

## 1. Fix Notifikasi Double
- [ ] `resources/views/layouts/main.blade.php` - Tambah handling import_errors
- [ ] `resources/views/pelanggan/index.blade.php` - Hapus duplikat session alerts
- [ ] `resources/views/pelanggan/khusus.blade.php` - Hapus duplikat session alerts
- [ ] `resources/views/pelanggan/create.blade.php` - Hapus duplikat session alerts
- [ ] `resources/views/approval-requests/pelanggan-khusus.blade.php` - Hapus duplikat session alerts
- [ ] `resources/views/approval-requests/kunjungan.blade.php` - Hapus duplikat session alerts
- [ ] `resources/views/approval-requests/pelanggan.blade.php` - Hapus duplikat session alerts
- [ ] `resources/views/approval-requests/index.blade.php` - Hapus duplikat session alerts
- [ ] `resources/views/cabang/index.blade.php` - Hapus duplikat session alerts
- [ ] `resources/views/users/index.blade.php` - Hapus duplikat session alerts (tanpa X button)

## 2. Pelanggan Khusus Import - Existing = Tambah Kunjungan
- [ ] `app/Http/Controllers/ApprovalRequestController.php`
  - [ ] `storeSpecialCustomerImportRequest()` - Handle existing PID sebagai add_visit
  - [ ] `storeSpecialCustomerRequest()` - Tambah mode existing
  - [ ] `approve()` - Handle action add_visit untuk pelanggan_khusus
- [ ] `resources/views/pelanggan/khusus.blade.php` - Tambah mode "Pelanggan Lama"
- [ ] `app/Http/Controllers/PelangganController.php` - Update searchByPid (tambah is_pelanggan_khusus)

## 3. Data Pelanggan Restrictions
- [ ] `app/Http/Controllers/PelangganImportExportController.php` - Reject special customer PID
- [ ] `app/Http/Controllers/PelangganController.php` - Reject existing special customer di store()
- [ ] `resources/views/pelanggan/create.blade.php` - Tambah info/restriction

## 4. Button Import/Download Template Layout
- [ ] `resources/views/pelanggan/index.blade.php` - Fix horizontal layout

## 5. Pagination
- [ ] `app/Http/Controllers/CabangController.php` - get() → paginate(15)
- [ ] `resources/views/cabang/index.blade.php` - Tambah pagination links
- [ ] `resources/views/approval-requests/pelanggan-khusus.blade.php` - Tambah pagination links
- [ ] `resources/views/approval-requests/kunjungan.blade.php` - Tambah pagination links

## 6. Force Delete
- [ ] `app/Http/Controllers/PelangganController.php` - destroy() & bulkDelete() → forceDelete()
- [ ] `app/Http/Controllers/UserController.php` - destroy() → forceDelete()
