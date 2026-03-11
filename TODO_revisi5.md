# TODO Revisi 5 - Implementasi 4 Fitur Baru

## Status: IN PROGRESS

---

## Task 1: Export Special Day Member ke Excel
- [ ] Buat `app/Exports/SpecialDayExport.php`
- [ ] Tambah method `export()` di `SpecialDayController`
- [ ] Tambah route `GET /special-day/export`
- [ ] Tambah tombol Export di `special-day/index.blade.php`

## Task 2: Hak Akses User per Cabang + Role IT
- [ ] Buat migration `create_user_cabangs_table` (pivot user_id, cabang_id) + seed semua user ke semua cabang
- [ ] Buat migration `add_it_role` (tambah role IT ke tabel roles)
- [ ] Update `User` model: tambah `cabangs()` belongsToMany + helper `getAccessibleCabangIds()`
- [ ] Update `Cabang` model: tambah `users()` belongsToMany
- [ ] Buat middleware `EnsureUserIsIT`
- [ ] Update `UserController`: restrict ke IT role, tambah cabang sync di create/store/edit/update
- [ ] Update `users/create.blade.php`: tambah checkbox cabang
- [ ] Update `users/edit.blade.php`: tambah checkbox cabang (pre-filled)
- [ ] Update `users/index.blade.php`: tampilkan badge cabang per user
- [ ] Update `layouts/main.blade.php`: menu IT hanya Manajemen User, SuperAdmin tidak bisa akses Manajemen User
- [ ] Update `PelangganController::index()`: filter by accessible cabangs
- [ ] Update `SpecialDayController::index()`: filter by accessible cabangs
- [ ] Update `LaporanController::index()`: filter dropdown cabang by accessible cabangs
- [ ] Update `routes/web.php`: pindahkan user management ke IT middleware

## Task 3: Data Sebelum/Sesudah di Modal Info Approval
- [ ] Update `ApprovalRequestController::storeKunjunganEditRequest()`: simpan `original_data` di payload
- [ ] Update `PelangganController::update()`: ubah ke approval flow, simpan `original_data` di payload
- [ ] Update `approval-requests/kunjungan.blade.php`: tampilkan 3 kolom (Pelanggan | Sebelum | Sesudah)
- [ ] Update `approval-requests/pelanggan.blade.php`: tampilkan data sebelum/sesudah

## Task 4: Routing Approval ke Superadmin Cabang
- [ ] Buat migration: tambah `assigned_to` (nullable FK → users) ke `approval_requests`
- [ ] Update `ApprovalRequest` model: tambah `assignedTo()` relation
- [ ] Update `ApprovalRequestController`:
  - [ ] Filter by `assigned_to = Auth::id()` untuk superadmin
  - [ ] Tambah `assigned_to` saat create approval
  - [ ] Tambah `storePelangganEditRequest()` method
  - [ ] Update `approve()`: apply perubahan saat approved (kunjungan edit, pelanggan edit, pelanggan delete)
- [ ] Update `KunjunganController::edit()`: pass superadmins berdasarkan cabang pelanggan
- [ ] Update `PelangganController::edit()` & `khusus()`: pass superadmins
- [ ] Update `pelanggan/edit-kunjungan.blade.php`: tambah dropdown superadmin
- [ ] Update `pelanggan/edit.blade.php`: tambah dropdown superadmin + alasan + ubah action ke approval
- [ ] Update `pelanggan/khusus.blade.php`: dropdown superadmin dinamis via JS
- [ ] Update approval views: tampilkan info "Ditugaskan ke: [nama]"
- [ ] Update `routes/web.php`: tambah route pelanggan edit approval

---

## Urutan Implementasi:
1. Migrations (3 migration baru)
2. Models (User, Cabang, ApprovalRequest)
3. Middleware (EnsureUserIsIT)
4. Controllers (UserController, PelangganController, KunjunganController, SpecialDayController, LaporanController, ApprovalRequestController)
5. Exports (SpecialDayExport)
6. Views (users, special-day, pelanggan, approval-requests, layouts)
7. Routes
