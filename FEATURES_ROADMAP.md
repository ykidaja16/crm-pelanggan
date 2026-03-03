# 🚀 Fitur & Pengembangan CRM Medical Lab - Roadmap

Dokumen ini berisi daftar fitur yang sudah dikerjakan dan saran pengembangan untuk project CRM Medical Lab ke depannya.

---

## ✅ Fitur yang Sudah Dikerjakan

### 1. **Manajemen Pelanggan**
- [x] CRUD Pelanggan (Create, Read, Update, Delete)
- [x] Sistem Klasifikasi Otomatis (Prioritas, Loyal, Potensial)
- [x] Riwayat Kunjungan per Pelanggan dengan Pagination
- [x] Detail Pelanggan dengan Total Transaksi
- [x] Riwayat Perubahan Kelas (Class History) dengan tracking siapa yang mengubah
- [x] Auto-generate PID berdasarkan Kode Cabang

### 2. **Import/Export Data**
- [x] Import Excel (.xlsx, .xls)
- [x] Import CSV dengan berbagai delimiter (auto-detect)
- [x] Export Excel dengan filter
- [x] **Template Import Download** - Template Excel dengan data dummy
- [x] Validasi data saat import (cek duplikat PID, validasi nama & alamat)
- [x] **Bulk Export** - Export multiple pelanggan terpilih

### 3. **Pencarian & Filter**
- [x] Search by PID/Nama
- [x] Filter per Bulan/Tahun/Semua Data
- [x] **Filter Tanggal Range** - Dari tanggal X sampai tanggal Y
- [x] Filter by Cabang
- [x] Filter by Kelas (Prioritas, Loyal, Potensial)
- [x] Filter by Range Omset (< 1jt, 1-4jt, > 4jt)
- [x] Filter by Range Kedatangan (≤2, 3-4, >4)
- [x] Sorting (PID, Nama, Tanggal Kunjungan, Klasifikasi, Total Biaya)
- [x] Pagination (30 data per halaman)

### 4. **Bulk Actions (Aksi Massal)**
- [x] Checkbox di setiap baris tabel
- [x] Hapus multiple pelanggan sekaligus
- [x] Export multiple pelanggan terpilih
- [x] API Search Pelanggan by PID (untuk mode tambah kunjungan ke pelanggan lama)

### 5. **Keamanan**
- [x] Autentikasi & Autorisasi (Role: Super Admin, Admin, User)
- [x] Rate Limiting (30 request/minute + throttle khusus login & import)
- [x] Session Timeout
- [x] **Audit Logging** - Pencatatan semua aktivitas user (login, logout, CRUD, import)
- [x] Security Headers (X-Frame-Options, X-Content-Type-Options, etc)
- [x] **Password Reset Request** - User request, admin approve/reject
- [x] Middleware role-based access (EnsureUserIsAdmin, EnsureUserIsSuperAdmin)

### 6. **Dashboard & Analytics**
- [x] **Quick Stats Widget**:
  - Total Pelanggan Aktif
  - Kunjungan Bulan Ini
  - Kunjungan Tahun Ini
  - Pelanggan Baru Bulan Ini
- [x] **Grafik Statistik**:
  - Grafik batang: Pertumbuhan pelanggan per bulan
  - Grafik batang: Pertumbuhan pelanggan per tahun (5 tahun terakhir)
  - Grafik pie: Distribusi klasifikasi (Prioritas, Loyal, Potensial)
- [x] Filter grafik: Monthly, Yearly, By Class
- [x] Caching dashboard stats (5 menit) untuk performa

### 7. **Laporan Lengkap**
- [x] Halaman khusus laporan dengan berbagai filter
- [x] Preview laporan sebelum export
- [x] Summary statistics (total pelanggan, total omset, rata-rata, total kunjungan)
- [x] Export Excel
- [x] **Print View** - Tampilan khusus untuk print
- [x] Filter by periode, cabang, kelas, omset range, kedatangan range

### 8. **User Experience**
- [x] Disable autocomplete di login
- [x] Tombol kembali ke dashboard pelanggan (konsisten)
- [x] Filter nonaktif saat search dengan tetap bisa export
- [x] Loading indicator saat import
- [x] Pagination dengan informasi jumlah data
- [x] Sorting indicator (asc/desc) di header tabel

### 9. **Manajemen Pengguna**
- [x] CRUD User (Super Admin only)
- [x] Role management (Super Admin, Admin, User)
- [x] Status aktif/non-aktif user
- [x] **Password Reset Management** - List request, approve, reject
- [x] **Activity Log** - Lihat log aktivitas semua user dengan filter, export, dan pagination (50 entri per halaman)


### 10. **Performance Optimization** ⚡
- [x] **Database Indexes** - 6 index baru untuk optimasi query
- [x] **Query Optimization**:
  - Dashboard: dari 12 query → 1 query (GROUP BY)
  - Laporan: dari multiple query → single aggregate query
  - Pelanggan index: DB-level filtering & pagination
- [x] **Caching**:
  - Dashboard stats di-cache 5 menit
  - Cabang list di-cache 1 jam
- [x] **Efficient Algorithms**:
  - `updateStats()`: dari 2 query → 1 query
  - `generatePid()`: dari orderBy→first → max() (lebih efisien)

---

## 🎯 Prioritas Tinggi (Rekomendasi Segera Dikerjakan)

### 1. **Export PDF** 📄
**Status:** 🔴 Belum Dikerjakan  
**Deskripsi:**
- Export laporan pelanggan ke format PDF
- Export detail pelanggan + riwayat kunjungan
- Template laporan yang rapi dengan logo
- Export hasil filter/pencarian

**Package yang dibutuhkan:** `barryvdh/laravel-dompdf`

**Manfaat:** Memudahkan distribusi laporan dalam format yang tidak bisa diedit

---

### 2. **Toast Notifications** 🔔
**Status:** 🔴 Belum Dikerjakan  
**Deskripsi:**
- Ganti alert Bootstrap biasa dengan SweetAlert2 atau Toastr
- Notifikasi popup yang lebih modern dan menarik
- Auto-dismiss setelah 3-5 detik
- Tipe: Success, Error, Warning, Info dengan ikon yang sesuai

**Package yang dibutuhkan:** `sweetalert2` (via CDN atau npm)

**Manfaat:** UX yang lebih baik, notifikasi tidak mengganggu layout

---

### 3. **Profile Page** 👤
**Status:** 🔴 Belum Dikerjakan  
**Deskripsi:**
- Halaman profil untuk edit data diri (nama, email, username)
- Ganti password sendiri (dengan validasi password lama)
- Upload foto profil
- Lihat aktivitas login terakhir

**Manfaat:** User bisa mengelola data pribadi tanpa harus minta admin

---

### 4. **Soft Deletes + Recycle Bin (Lanjutan)** 💾
**Status:** 🟡 Partial - Migration ada, UI belum lengkap  
**Deskripsi:**
- Views Recycle Bin untuk Pelanggan dan User (sudah ada di tab tapi file tidak ditemukan)
- Fitur "Restore" untuk mengembalikan data yang dihapus
- Fitur "Permanent Delete" untuk hapus permanen setelah 30 hari
- Command `cleanup:recycle-bin` untuk hapus permanen otomatis

**File yang perlu dibuat:**
- `resources/views/pelanggan/recycle-bin.blade.php`
- `resources/views/users/recycle-bin.blade.php`
- `app/Console/Commands/CleanupRecycleBin.php`

**Manfaat:** Mencegah kehilangan data permanen akibat salah klik

---

### 5. **Email Notifications** 📧
**Status:** 🔴 Belum Dikerjakan  
**Deskripsi:**
- Email notifikasi saat password direset
- Email konfirmasi untuk admin saat ada request password reset
- Email ringkasan mingguan (opsional)

**Package yang dibutuhkan:** Laravel Mail + SMTP configuration

**Manfaat:** Notifikasi real-time ke email admin/user

---

## 📊 Dashboard Analytics & Reporting (Lanjutan)

### 6. **Advanced Dashboard Widgets** 📈
**Status:** 🔴 Belum Dikerjakan  
**Ide:**
- Trend line: Perbandingan kunjungan bulan ini vs bulan lalu
- Top 5 Pelanggan dengan Omset Tertinggi
- Top 5 Cabang dengan Kunjungan Terbanyak
- Grafik pertumbuhan pelanggan baru per minggu

---

### 7. **Laporan PDF dengan Chart** 📊
**Status:** 🔴 Belum Dikerjakan  
**Ide:**
- Laporan PDF yang include grafik/statistik
- Laporan bulanan otomatis dengan cron job
- Kirim laporan via email ke admin

---

## ✨ User Experience (UX) Enhancement

### 8. **Dark Mode** 🌙
**Status:** 🔴 Belum Dikerjakan  
**Ide:**
- Toggle dark/light mode di navbar
- Simpan preferensi di localStorage
- CSS variable untuk warna tema
- Auto-detect system preference

---

### 9. **Responsive Mobile** 📱
**Status:** 🟡 Partial  
**Ide:**
- Optimasi tampilan untuk tablet dan mobile
- Sidebar collapsible dengan hamburger menu
- Table dengan horizontal scroll di mobile
- Touch-friendly buttons

---

### 10. **Auto-complete Search** ⌨️
**Status:** 🔴 Belum Dikerjakan  
**Ide:**
- Suggestion nama/PID saat mengetik di search box
- AJAX search untuk hasil real-time
- Minimal 3 karakter untuk trigger search
- Highlight hasil pencarian

---

## 🔐 Security & Data Management

### 11. **Data Backup Otomatis** 💿
**Status:** 🔴 Belum Dikerjakan  
**Ide:**
- Backup database otomatis harian/mingguan via cron job
- Tombol backup manual di admin panel
- Download backup sebagai SQL dump
- Retention policy (simpan 30 hari terakhir)

**Package yang dibutuhkan:** `spatie/laravel-backup`

---

### 12. **Two Factor Authentication (2FA)** 🔐
**Status:** 🔴 Belum Dikerjakan  
**Ide:**
- 2FA via Google Authenticator
- Backup codes untuk recovery
- Optional untuk admin, wajib untuk super admin

---

## 🧪 Testing & Quality Assurance

### 13. **Unit Tests** ✅
**Status:** 🔴 Belum Dikerjakan  
**Ide:**
- Test untuk setiap method controller
- Test untuk model relationships
- Test untuk helper functions (calculateClass, generatePid)

**Command:** `php artisan make:test PelangganTest`

---

### 14. **Feature Tests** 🧪
**Status:** 🔴 Belum Dikerjakan  
**Ide:**
- Test alur lengkap: login → tambah pelanggan → edit → hapus
- Test import/export dengan file sample
- Test filter dan pencarian
- Test role-based access control

---

### 15. **Browser Tests** 🌐
**Status:** 🔴 Belum Dikerjakan  
**Ide:**
- Test otomatis dengan Laravel Dusk
- Test UI interaction (klik, input, submit)
- Screenshot saat test gagal
- Test critical path: login, CRUD pelanggan, export

**Package yang dibutuhkan:** `laravel/dusk`

---

## 🎁 Fitur Bonus (Nice to Have)

### 16. **Foto Pelanggan** 📷
- Upload foto profil pelanggan
- Preview foto di detail pelanggan
- Compress foto otomatis (max 500KB)

### 17. **Catatan Khusus per Pelanggan** 📝
- Field catatan tambahan per pelanggan
- Rich text editor untuk format catatan
- Tag/label untuk kategori khusus

### 18. **Status Kunjungan** 🏥
- Status: Selesai, Pending, Cancelled
- Filter by status
- Statistik status kunjungan

### 19. **SMS/WhatsApp Gateway** 💬
- Kirim notifikasi ke pelanggan via WhatsApp
- Reminder kunjungan berikutnya
- Promo/penawaran ke pelanggan tertentu

### 20. **Multi Cabang (Lanjutan)** 🏢
- Dashboard per cabang
- Laporan komparasi antar cabang
- User assignment ke cabang tertentu

### 21. **API REST** 🔌
- API endpoint untuk mobile app
- JWT authentication
- Rate limiting untuk API
- Dokumentasi API dengan Swagger

---

## 📅 Timeline Rekomendasi (Updated)

### **Fase 1: UX & Reporting (Minggu 1-2)**
1. Export PDF
2. Toast Notifications (SweetAlert2)
3. Profile Page
4. Recycle Bin UI (lengkapi yang sudah ada)

### **Fase 2: Communication & Security (Minggu 3-4)**
5. Email Notifications
6. Data Backup Otomatis
7. Two Factor Authentication (opsional)

### **Fase 3: Testing & Polish (Bulan 2)**
8. Unit Tests & Feature Tests
9. Responsive Mobile
10. Auto-complete Search

### **Fase 4: Advanced Features (Bulan 3)**
11. Dark Mode
12. Advanced Dashboard Widgets
13. API REST (jika diperlukan)

---

## 🛠️ Tech Stack Tambahan yang Direkomendasikan

| Fitur | Package/Library | Status |
|-------|----------------|--------|
| Export PDF | `barryvdh/laravel-dompdf` | ⏳ Pending |
| Toast Notification | `sweetalert2` (CDN) | ⏳ Pending |
| Date Range Picker | `flatpickr` (sudah ada di laporan) | ✅ Done |
| Testing | `laravel/dusk` (browser test) | ⏳ Pending |
| Backup | `spatie/laravel-backup` | ⏳ Pending |
| 2FA | `pragmarx/google2fa-laravel` | ⏳ Pending |
| Queue/Job | Laravel Queue + Redis/Supervisor | ⏳ Pending |

---

## 📊 Ringkasan Status Project

| Kategori | Done | In Progress | Pending |
|----------|------|-------------|---------|
| Core CRM | 90% | 5% | 5% |
| Import/Export | 95% | 0% | 5% |
| Reporting | 70% | 0% | 30% |
| Security | 85% | 5% | 10% |
| UX/UI | 60% | 10% | 30% |
| Testing | 5% | 0% | 95% |

**Overall Progress: ~75%**

---

## 📝 Catatan Pengembangan

- ✅ **Optimasi Performa sudah selesai** - Lihat `TODO.md` untuk detail
- ✅ **Soft Deletes migration sudah ada** - Perlu lengkapi UI Recycle Bin
- ✅ **Activity Log sudah lengkap** - Model, migration, controller, export, view
- ✅ **Klasifikasi sudah final** - Prioritas, Loyal, Potensial (dengan tracking history)
- **Selalu backup database** sebelum melakukan perubahan besar
- **Test di local** sebelum deploy ke production
- **Gunakan migration** untuk perubahan database
- **Tulis dokumentasi** untuk fitur baru
- **Code review** sebelum merge ke branch utama

---

**Dibuat:** 2026-03-02  
**Oleh:** AI Assistant  
**Project:** CRM Medical Lab - SIMA Lab  
**Versi:** 2.0 (Updated)
