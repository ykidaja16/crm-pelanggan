# TODO Revisi - Medical Lab CRM
## Status: ✅ SEMUA 11 POIN SELESAI

---

## Ringkasan Perubahan

### Point 1 ✅ Import Excel - Real-time Progress Bar
**Files:**
- `app/Imports/KunjunganImport.php` - Set cache keys: `import_progress_{userId}`, `import_total_{userId}`, `import_current_{userId}`
- `app/Http/Controllers/PelangganController.php` - `importProgress()` returns `{percent, current, total, status}`
- `resources/views/pelanggan/index.blade.php` - Polling setiap 800ms ke `/import/progress`, reset file input setelah selesai, notif dengan reload on X
- `routes/web.php` - Route `pelanggan.import.progress` → GET `/import/progress`

**Behavior:**
- Progress bar menampilkan % real berdasarkan baris diproses vs total baris
- File input dikosongkan setelah import selesai
- Klik X pada notifikasi berhasil/gagal → reload halaman

---

### Point 2 ✅ Pagination Riwayat Pengajuan Perubahan
**Files:**
- `resources/views/pelanggan/show.blade.php` - Tambah `$approvalHistories->links()` dengan footer "Menampilkan X-Y dari Z"
- `resources/views/approval-requests/index.blade.php` - Pagination footer konsisten
- `app/Http/Controllers/PelangganController.php` - `show()` paginate(10, ['*'], 'approval_page')

---

### Point 3 ✅ Approval Dropdown (Approve/Reject + 1 Textbox Catatan)
**Files:**
- `resources/views/approval-requests/index.blade.php` - Dropdown select (✅ Approve / ❌ Reject) + 1 input catatan
- `app/Http/Controllers/ApprovalRequestController.php` - `process()` validates action + decision_note

---

### Point 4 ✅ Filter Kelompok Pelanggan & Tipe Pelanggan
**Files:**
- `resources/views/pelanggan/index.blade.php` - Select kelompok_pelanggan (mandiri/klinisi) + tipe_pelanggan (biasa/khusus)
- `resources/views/laporan/index.blade.php` - Select kelompok_pelanggan + tipe_pelanggan
- `app/Http/Controllers/PelangganController.php` - `index()` handles both filters
- `app/Http/Controllers/LaporanController.php` - `buildQuery()` handles both filters

---

### Point 5 ✅ Role User Restrictions
**Files:**
- `resources/views/pelanggan/index.blade.php` - Import card hidden, Hapus Terpilih hidden, edit/delete buttons hidden untuk User
- `resources/views/pelanggan/show.blade.php` - Edit/delete kunjungan hidden untuk User (tampil "View Only")

---

### Point 6 ✅ Validasi PID Duplikat di Pelanggan Khusus
**Files:**
- `app/Http/Controllers/ApprovalRequestController.php`:
  - `storeSpecialCustomerRequest()` - Cek PID di database + pending approvals
  - `storeSpecialCustomerImportRequest()` - Cek PID di database + pending approvals per baris

---

### Point 7 ✅ Menu Buka Cabang Baru (Superadmin Only)
**Files:**
- `app/Http/Controllers/CabangController.php` - Full CRUD (index, store, update, destroy)
- `resources/views/cabang/index.blade.php` - Form tambah cabang + daftar cabang + modal edit
- `resources/views/layouts/main.blade.php` - Menu "Manajemen Cabang" untuk Super Admin
- `routes/web.php` - Routes cabang.* di bawah EnsureUserIsSuperAdmin middleware

**Features:**
- Kode cabang 2 huruf, tidak bisa diubah setelah dibuat
- Cabang hanya bisa dihapus jika tidak ada pelanggan terdaftar
- Activity log untuk setiap operasi cabang

---

### Point 8 ✅ Validasi PID Prefix Sesuai Cabang
**Files:**
- `app/Http/Controllers/ApprovalRequestController.php` - Validasi prefix PID vs kode cabang
- `app/Imports/KunjunganImport.php` - Validasi prefix PID vs cabangs yang ada
- `app/Http/Controllers/PelangganController.php` - Validasi prefix PID di store()

**Rule:** PID 2 karakter pertama harus sesuai kode cabang (LX=Ciliwung, LZ=Tangkuban Perahu, dst.)

---

### Point 9 ✅ Kelas Minimum Potensial
**Files:**
- `app/Models/Pelanggan.php` - `calculateClass()` returns 'Potensial' sebagai default minimum

**Rule:**
- Potensial: ≥2 kunjungan ATAU 1 kunjungan dengan biaya ≥1 Juta
- Loyal: ≥5 kunjungan
- Prioritas: ada kunjungan ≥4 Juta ATAU is_pelanggan_khusus=true
- Default (tidak memenuhi syarat apapun): Potensial (bukan kelas lebih rendah)

---

### Point 10 ✅ Hapus Terpilih & Individual Perlu Approval untuk Admin
**Files:**
- `resources/views/pelanggan/index.blade.php`:
  - Super Admin: hapus langsung (form DELETE)
  - Admin: hapus individual → modal dengan catatan_hapus
  - Admin: hapus terpilih → modal bulk dengan catatan_hapus
  - User: tombol Hapus Terpilih tidak ditampilkan
- `app/Http/Controllers/PelangganController.php`:
  - `destroy()`: SA → soft delete langsung, Admin → buat ApprovalRequest
  - `bulkDelete()`: SA → soft delete langsung, Admin → buat ApprovalRequest
- `app/Http/Controllers/ApprovalRequestController.php`:
  - `approve()`: handles type='pelanggan'/action='delete' dan 'bulk_delete'

---

### Point 11 ✅ Redirect ke Detail Pelanggan Setelah Ajukan Perubahan
**Files:**
- `app/Http/Controllers/ApprovalRequestController.php` - `storeKunjunganEditRequest()` redirect ke `pelanggan.show`
- `resources/views/pelanggan/edit-kunjungan.blade.php` - Form submit ke `approval.kunjungan.edit.store`

---

## Verifikasi PHP Syntax
```
✅ app/Http/Controllers/PelangganController.php
✅ app/Http/Controllers/ApprovalRequestController.php
✅ app/Http/Controllers/LaporanController.php
✅ app/Http/Controllers/CabangController.php
✅ app/Imports/KunjunganImport.php
✅ app/Models/Pelanggan.php
```

## Routes Terverifikasi
```
GET  /import/progress          → pelanggan.import.progress
POST /import                   → pelanggan.import (throttle:import)
GET  /pelanggan/{id}/show      → pelanggan.show
DELETE /pelanggan/{id}         → pelanggan.destroy (Admin middleware)
POST /pelanggan/bulk-delete    → pelanggan.bulk-delete (Admin middleware)
GET  /approval-requests        → approval.index (SA middleware)
POST /approval-requests/{id}/process → approval.process (SA middleware)
GET  /cabang                   → cabang.index (SA middleware)
POST /cabang                   → cabang.store (SA middleware)
PUT  /cabang/{id}              → cabang.update (SA middleware)
DELETE /cabang/{id}            → cabang.destroy (SA middleware)
POST /approval/kunjungan/{id}/edit → approval.kunjungan.edit.store (Admin middleware)
