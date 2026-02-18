# 🚀 Fitur & Pengembangan CRM Medical Lab - Roadmap

Dokumen ini berisi daftar fitur yang sudah dikerjakan dan saran pengembangan untuk project CRM Medical Lab ke depannya.

---

## ✅ Fitur yang Sudah Dikerjakan

### 1. **Manajemen Pelanggan**
- [x] CRUD Pelanggan (Create, Read, Update, Delete)
- [x] Sistem Klasifikasi Otomatis (Basic, Silver, Gold, Platinum)
- [x] Riwayat Kunjungan per Pelanggan
- [x] Detail Pelanggan dengan Total Transaksi

### 2. **Import/Export Data**
- [x] Import Excel (.xlsx, .xls)
- [x] Import CSV dengan berbagai delimiter
- [x] Export Excel dengan filter
- [x] Validasi data saat import (cek duplikat NIK, validasi nama & alamat)

### 3. **Pencarian & Filter**
- [x] Search by NIK/Nama
- [x] Filter per Bulan/Tahun
- [x] Filter Semua Data
- [x] Sorting (NIK, Nama, Tanggal Kunjungan, Klasifikasi)
- [x] Pagination

### 4. **Keamanan**
- [x] Autentikasi & Autorisasi (Role: Admin, Superadmin)
- [x] Rate Limiting (30 request/minute)
- [x] Session Timeout
- [x] Audit Logging
- [x] Security Headers
- [x] Password Reset Request

### 5. **User Experience**
- [x] Disable autocomplete di login
- [x] Tombol kembali ke dashboard pelanggan (konsisten)
- [x] Filter nonaktif saat search dengan tetap bisa export
- [x] Loading indicator saat import

---

## 🎯 Prioritas Tinggi (Rekomendasi Segera Dikerjakan)

### 1. **Soft Deletes + Recycle Bin** 💾
**Status:** 🔴 Belum Dikerjakan  
**Deskripsi:** 
- Tambahkan `deleted_at` column ke tabel pelanggan dan kunjungan
- Data yang dihapus masuk ke "Recycle Bin"
- Fitur "Restore" untuk mengembalikan data
- Fitur "Permanent Delete" untuk hapus permanen setelah 30 hari

**Manfaat:** Mencegah kehilangan data permanen akibat salah klik

---

### 2. **Export PDF** 📄
**Status:** 🔴 Belum Dikerjakan  
**Deskripsi:**
- Export laporan pelanggan ke format PDF
- Template laporan yang rapi dengan logo
- Export detail pelanggan + riwayat kunjungan
- Export hasil filter/pencarian

**Package yang dibutuhkan:** `barryvdh/laravel-dompdf`

---

### 3. **Template Import Download** 📥
**Status:** 🔴 Belum Dikerjakan  
**Deskripsi:**
- Tombol "Download Template" di halaman import
- Template Excel/CSV dengan format yang benar
- Contoh data dummy di template
- Petunjuk pengisian di sheet terpisah

**Manfaat:** Memudahkan user mengisi data dengan format yang benar

---

### 4. **Toast Notifications** 🔔
**Status:** 🔴 Belum Dikerjakan  
**Deskripsi:**
- Ganti alert Bootstrap dengan Toastr atau SweetAlert2
- Notifikasi popup yang lebih modern
- Auto-dismiss setelah 3-5 detik
- Tipe: Success, Error, Warning, Info

**Package yang dibutuhkan:** `sweetalert2` atau `toastr`

---

### 5. **Filter Tanggal Range** 📅
**Status:** 🔴 Belum Dikerjakan  
**Deskripsi:**
- Pilih rentang tanggal: Dari Tanggal X Sampai Tanggal Y
- Filter kunjungan dalam periode tertentu
- Berguna untuk laporan periode kustom

---

## 📊 Dashboard Analytics & Reporting

### 6. **Grafik Statistik** 📈
**Status:** 🔴 Belum Dikerjakan  
**Ide:**
- Grafik batang: Pertumbuhan pelanggan per bulan
- Grafik pie: Distribusi klasifikasi (Platinum, Gold, Silver, Basic)
- Grafik garis: Trend kunjungan 12 bulan terakhir
- Counter widget: Total pelanggan, kunjungan hari ini, pelanggan baru bulan ini

**Package yang dibutuhkan:** `Chart.js` atau `ApexCharts`

---

### 7. **Quick Stats Widget** 🎯
**Status:** 🔴 Belum Dikerjakan  
**Ide:**
- Card di atas dashboard dengan angka penting:
  - Total Pelanggan Aktif
  - Kunjungan Hari Ini
  - Pelanggan Baru Bulan Ini
  - Total Transaksi Bulan Ini

---

### 8. **Laporan Lengkap** 📋
**Status:** 🔴 Belum Dikerjakan  
**Ide:**
- Halaman khusus laporan dengan berbagai filter
- Laporan bisa di-preview sebelum export
- Pilihan format: Excel, PDF, Print

---

## 🔔 Notifikasi & Alert System

### 9. **Email Notifications** 📧
**Status:** 🔴 Belum Dikerjakan  
**Ide:**
- Email notifikasi saat password direset
- Email ringkasan mingguan untuk admin
- Email alert saat ada pelanggan baru

**Package yang dibutuhkan:** Laravel Mail + SMTP/Queue

---

### 10. **Alert Klasifikasi** 🏆
**Status:** 🔴 Belum Dikerjakan  
**Ide:**
- Notifikasi ketika pelanggan naik klasifikasi (contoh: Silver → Gold)
- Notifikasi di dashboard admin
- Riwayat perubahan klasifikasi per pelanggan

---

## 🔍 Pencarian & Filter Lanjutan

### 11. **Filter Multiple Klasifikasi** 🎨
**Status:** 🔴 Belum Dikerjakan  
**Ide:**
- Checkbox untuk pilih beberapa klasifikasi sekaligus
- Contoh: Tampilkan hanya Platinum + Gold

---

### 12. **Search by Alamat** 🏠
**Status:** 🔴 Belum Dikerjakan  
**Ide:**
- Field pencarian alamat di filter
- Pencarian partial (contoh: "Jakarta" menampilkan semua alamat Jakarta)

---

### 13. **Auto-complete Search** ⌨️
**Status:** 🔴 Belum Dikerjakan  
**Ide:**
- Suggestion nama/NIK saat mengetik
- AJAX search untuk hasil real-time
- Minimal 3 karakter untuk trigger search

---

## ✨ User Experience (UX) Enhancement

### 14. **Dark Mode** 🌙
**Status:** 🔴 Belum Dikerjakan  
**Ide:**
- Toggle dark/light mode
- Simpan preferensi di localStorage
- CSS variable untuk warna tema

---

### 15. **Responsive Mobile** 📱
**Status:** 🟡 Partial  
**Ide:**
- Optimasi tampilan untuk tablet dan mobile
- Sidebar collapsible
- Table dengan horizontal scroll di mobile

---

### 16. **Loading States** ⏳
**Status:** 🟡 Partial  
**Ide:**
- Skeleton loading saat load data
- Spinner di tombol saat proses (sudah ada di import, perlu ditambah di tempat lain)
- Progress bar untuk upload file besar

---

## 👥 Manajemen Pengguna

### 17. **Profile Page** 👤
**Status:** 🔴 Belum Dikerjakan  
**Ide:**
- Halaman profil untuk edit data diri
- Ganti password sendiri
- Upload foto profil

---

### 18. **Role Permissions Granular** 🔐
**Status:** 🟡 Basic  
**Ide:**
- Permission lebih detail:
  - `view_pelanggan` - Lihat data
  - `create_pelanggan` - Tambah data
  - `edit_pelanggan` - Edit data
  - `delete_pelanggan` - Hapus data
  - `import_data` - Import Excel/CSV
  - `export_data` - Export laporan

---

### 19. **User Activity Log** 📋
**Status:** 🔴 Belum Dikerjakan  
**Ide:**
- Log aktivitas setiap user (login, logout, CRUD)
- Filter log by user dan tanggal
- Export log ke Excel

---

## 💾 Data Management

### 20. **Bulk Actions** ⚡
**Status:** 🔴 Belum Dikerjakan  
**Ide:**
- Checkbox di setiap baris tabel
- Hapus multiple pelanggan sekaligus
- Export multiple pelanggan terpilih

---

### 21. **Data Backup** 💿
**Status:** 🔴 Belum Dikerjakan  
**Ide:**
- Backup database otomatis harian/mingguan
- Tombol backup manual
- Restore dari backup

---

### 22. **Audit Trail Lengkap** 📜
**Status:** 🟡 Partial  
**Ide:**
- Catatan siapa yang mengubah data dan kapan
- Field `created_by`, `updated_by`, `deleted_by`
- Riwayat perubahan nilai (old value → new value)

---

## 🧪 Testing & Quality Assurance

### 23. **Unit Tests** ✅
**Status:** 🔴 Belum Dikerjakan  
**Ide:**
- Test untuk setiap method controller
- Test untuk model relationships
- Test untuk helper functions

**Command:** `php artisan make:test PelangganTest`

---

### 24. **Feature Tests** 🧪
**Status:** 🔴 Belum Dikerjakan  
**Ide:**
- Test alur lengkap: login → tambah pelanggan → edit → hapus
- Test import/export
- Test filter dan pencarian

---

### 25. **Browser Tests** 🌐
**Status:** 🔴 Belum Dikerjakan  
**Ide:**
- Test otomatis dengan Laravel Dusk
- Test UI interaction (klik, input, submit)
- Screenshot saat test gagal

**Package yang dibutuhkan:** `laravel/dusk`

---

## 🎁 Fitur Bonus (Nice to Have)

### 26. **Foto Pelanggan** 📷
- Upload foto profil pelanggan
- Preview foto di detail pelanggan
- Compress foto otomatis

### 27. **Catatan Khusus** 📝
- Field catatan tambahan per pelanggan
- Rich text editor untuk format catatan

### 28. **Status Kunjungan** 🏥
- Status: Selesai, Pending, Cancelled
- Filter by status
- Statistik status kunjungan

### 29. **SMS/WhatsApp Gateway** 💬
- Kirim notifikasi ke pelanggan via WhatsApp
- Reminder kunjungan berikutnya
- Promo/penawaran ke pelanggan tertentu

### 30. **Multi Cabang** 🏢
- Support untuk multiple lokasi/lab
- Filter data by cabang
- Laporan per cabang

---

## 📅 Timeline Rekomendasi

### **Minggu 1-2: Prioritas Tinggi**
1. Soft Deletes + Recycle Bin
2. Export PDF
3. Template Import

### **Minggu 3-4: UX Enhancement**
4. Toast Notifications
5. Filter Tanggal Range
6. Quick Stats Widget

### **Bulan 2: Dashboard & Analytics**
7. Grafik Statistik
8. Laporan Lengkap
9. Alert Klasifikasi

### **Bulan 3: Advanced Features**
10. Auto-complete Search
11. Bulk Actions
12. Profile Page

---

## 🛠️ Tech Stack Tambahan yang Direkomendasikan

| Fitur | Package/Library |
|-------|----------------|
| Export PDF | `barryvdh/laravel-dompdf` |
| Grafik | `Chart.js` atau `ApexCharts` |
| Toast Notification | `sweetalert2` atau `toastr` |
| Date Range Picker | `flatpickr` atau `daterangepicker` |
| Testing | `laravel/dusk` (browser test) |
| Queue/Job | Laravel Queue + Redis/Supervisor |
| Backup | `spatie/laravel-backup` |

---

## 📝 Catatan Pengembangan

- **Selalu backup database** sebelum melakukan perubahan besar
- **Test di local** sebelum deploy ke production
- **Gunakan migration** untuk perubahan database
- **Tulis dokumentasi** untuk fitur baru
- **Code review** sebelum merge ke branch utama

---

**Dibuat:** [Tanggal Hari Ini]  
**Oleh:** AI Assistant  
**Project:** CRM Medical Lab - SIMA Lab  
**Versi:** 1.0
