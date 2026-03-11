# TODO Revisi 5 - Progress Tracker

## Status: ✅ SEMUA SELESAI

---

## Revisi 1: Export Special Day Member ke Excel
- [x] `app/Exports/SpecialDayExport.php` — CREATED ✅
- [x] `app/Http/Controllers/SpecialDayController.php` — buildQuery() + export() ✅
- [x] `resources/views/special-day/index.blade.php` — tombol Export + filter ✅
- [x] `routes/web.php` — route `special-day.export` ✅

## Revisi 2: Hak Akses User per Cabang (user_cabangs pivot)
- [x] `database/migrations/2026_03_15_000001_create_user_cabangs_table.php` ✅
- [x] `app/Models/User.php` — cabangs() belongsToMany + getAccessibleCabangIds() ✅
- [x] `app/Models/Cabang.php` — users() belongsToMany ✅
- [x] `app/Http/Controllers/UserController.php` — CRUD cabang akses ✅
- [x] `resources/views/users/index.blade.php` — tampil cabang akses ✅
- [x] `resources/views/users/create.blade.php` — checkbox cabang ✅
- [x] `resources/views/users/edit.blade.php` — checkbox cabang ✅
- [x] `app/Http/Controllers/LaporanController.php` — filter by accessible cabangs ✅
- [x] `app/Http/Controllers/PelangganController.php` — filter by accessible cabangs ✅
- [x] `app/Http/Controllers/KunjunganController.php` — filter by accessible cabangs ✅
- [x] `app/Http/Controllers/SpecialDayController.php` — filter by accessible cabangs ✅

## Revisi 3: IT Role
- [x] `database/migrations/2026_03_15_000003_add_it_role.php` ✅
- [x] `app/Http/Middleware/EnsureUserIsIT.php` ✅
- [x] `bootstrap/app.php` — alias middleware 'it' ✅
- [x] `routes/web.php` — group IT ✅
- [x] `resources/views/layouts/main.blade.php` — IT: hanya Manajemen User ✅

## Revisi 4: Approval Edit — Data Sebelum & Sesudah di Modal
- [x] `app/Http/Controllers/ApprovalRequestController.php`:
  - storePelangganEditRequest() — simpan original_data ✅
  - storeKunjunganEditRequest() — simpan original_data ✅
  - indexPelanggan() — pass $cabangs ke view ✅ (FIX TERAKHIR)
  - indexPelangganKhusus() — eager load assignedTo ✅
  - indexKunjungan() — eager load assignedTo ✅
  - indexPelanggan() — eager load assignedTo ✅
- [x] `resources/views/approval-requests/pelanggan.blade.php` — modal 3-kolom (Identitas|Sebelum|Sesudah) ✅ (FIX TERAKHIR)
- [x] `resources/views/approval-requests/kunjungan.blade.php` — modal 3-kolom ✅
- [x] `resources/views/approval-requests/pelanggan-khusus.blade.php` ✅

## Revisi 5: Approval Diarahkan ke Superadmin Cabang
- [x] `database/migrations/2026_03_15_000002_add_assigned_to_to_approval_requests.php` ✅
- [x] `app/Models/ApprovalRequest.php` — assignedTo() relation ✅
- [x] `app/Http/Controllers/ApprovalRequestController.php`:
  - getFirstSuperadminForCabang() helper ✅
  - storeSpecialCustomerRequest() — auto-assign ✅
  - storeSpecialCustomerImportRequest() — auto-assign ✅
  - storeKunjunganEditRequest() — assigned_to dari form/auto ✅
  - storeKunjunganDeleteRequest() — auto-assign ✅
  - storePelangganEditRequest() — assigned_to dari form/auto ✅
  - Super Admin filter: hanya lihat yang di-assign ke dirinya ✅
- [x] `resources/views/pelanggan/edit.blade.php` — dropdown superadmin ✅
- [x] `resources/views/pelanggan/edit-kunjungan.blade.php` — dropdown superadmin ✅
- [x] `resources/views/pelanggan/khusus.blade.php` — dropdown superadmin ✅

---

## Ringkasan File yang Diubah/Dibuat

| File | Status |
|------|--------|
| `app/Exports/SpecialDayExport.php` | CREATED ✅ |
| `app/Http/Controllers/SpecialDayController.php` | UPDATED ✅ |
| `app/Http/Controllers/ApprovalRequestController.php` | UPDATED ✅ |
| `app/Http/Controllers/PelangganController.php` | UPDATED ✅ |
| `app/Http/Controllers/KunjunganController.php` | UPDATED ✅ |
| `app/Http/Controllers/LaporanController.php` | UPDATED ✅ |
| `app/Http/Controllers/UserController.php` | UPDATED ✅ |
| `app/Http/Middleware/EnsureUserIsIT.php` | CREATED ✅ |
| `app/Models/User.php` | UPDATED ✅ |
| `app/Models/Cabang.php` | UPDATED ✅ |
| `app/Models/ApprovalRequest.php` | UPDATED ✅ |
| `bootstrap/app.php` | UPDATED ✅ |
| `routes/web.php` | UPDATED ✅ |
| `resources/views/special-day/index.blade.php` | REBUILT ✅ |
| `resources/views/approval-requests/pelanggan.blade.php` | REBUILT ✅ |
| `resources/views/approval-requests/kunjungan.blade.php` | UPDATED ✅ |
| `resources/views/approval-requests/pelanggan-khusus.blade.php` | UPDATED ✅ |
| `resources/views/layouts/main.blade.php` | UPDATED ✅ |
| `resources/views/pelanggan/edit.blade.php` | UPDATED ✅ |
| `resources/views/pelanggan/edit-kunjungan.blade.php` | UPDATED ✅ |
| `resources/views/pelanggan/khusus.blade.php` | UPDATED ✅ |
| `resources/views/users/index.blade.php` | UPDATED ✅ |
| `resources/views/users/create.blade.php` | UPDATED ✅ |
| `resources/views/users/edit.blade.php` | UPDATED ✅ |
| `database/migrations/2026_03_15_000001_*` | CREATED ✅ |
| `database/migrations/2026_03_15_000002_*` | CREATED ✅ |
| `database/migrations/2026_03_15_000003_*` | CREATED ✅ |

---

## Catatan Penting untuk Deploy

1. Jalankan migrasi: `php artisan migrate`
2. Pastikan semua user existing di-assign ke cabang yang sesuai via Manajemen User
3. Superadmin yang akan menerima approval harus sudah di-assign ke cabang terkait
4. Jika belum ada superadmin di cabang tertentu, `assigned_to` akan null (approval tidak akan muncul di siapapun)
