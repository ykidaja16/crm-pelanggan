# TODO - CRM Pelanggan Optimization

## ✅ SELESAI - Optimasi Performa (2026-03-01)

### 1. ✅ Database Indexes (Migration Baru)
**File:** `database/migrations/2026_03_01_000001_add_performance_indexes.php`
- ✅ Index `pelanggans.nama` — untuk search dan ORDER BY
- ✅ Index `pelanggans.total_biaya` — untuk filter range omset
- ✅ Index `pelanggans.total_kedatangan` — untuk filter range kedatangan
- ✅ Composite index `pelanggans.(class, cabang_id)` — untuk filter kelas + cabang
- ✅ Composite index `kunjungans.(pelanggan_id, tanggal_kunjungan)` — untuk subquery MAX(tanggal_kunjungan)
- ✅ Composite index `kunjungans.(tanggal_kunjungan, pelanggan_id)` — untuk GROUP BY dashboard
- ✅ Migration sudah dijalankan: `php artisan migrate`

### 2. ✅ Pelanggan Model — updateStats() Single Query
**File:** `app/Models/Pelanggan.php`
- ✅ `updateStats()`: dari 2 query terpisah (`count()` + `sum()`) → 1 query `selectRaw('COUNT(*), COALESCE(SUM(biaya),0)')`
- ✅ `generatePid()`: dari `orderBy()->first()` → `max('pid')` (lebih efisien)

### 3. ✅ PelangganController — DB-level Filtering & Pagination
**File:** `app/Http/Controllers/PelangganController.php`
- ✅ `index()`: dari load ALL records ke memory + filter PHP collection → query builder dengan WHERE di DB
- ✅ Filter cabang, kelas, omset range, kedatangan range: semua di DB level
- ✅ Sorting: dari `sortBy()` collection → `orderBy()`/`orderByRaw()` di DB
- ✅ Pagination: dari manual `LengthAwarePaginator::make(slice)` → `->paginate(30)->withQueryString()`
- ✅ Subquery `tgl_kunjungan` dihitung di DB (correlated subquery), bukan load semua kunjungan
- ✅ Validasi `$direction` (asc/desc) untuk mencegah SQL injection
- ✅ Hapus method `applyFilters()` dan `applySorting()` yang tidak efisien

### 4. ✅ DashboardController — Grouped Queries + Caching
**File:** `app/Http/Controllers/DashboardController.php`
- ✅ Mode `monthly`: dari 12 query terpisah → 1 query `GROUP BY MONTH(tanggal_kunjungan)`
- ✅ Mode `yearly`: dari 5 query terpisah → 1 query `GROUP BY YEAR(tanggal_kunjungan)`
- ✅ Mode `class`: dari N query terpisah → 1 query `GROUP BY class`
- ✅ Statistik dashboard di-cache 5 menit (`Cache::remember('dashboard_stats', 300, ...)`)
- ✅ `pelangganBaruBulanIni`: menggunakan `whereExists`/`whereNotExists` yang lebih efisien

### 5. ✅ LaporanController — Hilangkan Double Query
**File:** `app/Http/Controllers/LaporanController.php`
- ✅ `preview()`: dari `buildQuery()` dipanggil 2x (sekali untuk data, sekali untuk summary) → summary dihitung dengan `selectRaw` aggregate dalam 1 query
- ✅ Summary (count, sum, avg) dihitung dalam 1 query `COALESCE(SUM(...)), COALESCE(AVG(...))`

### 6. ✅ AppServiceProvider — Cache Cabang List
**File:** `app/Providers/AppServiceProvider.php`
- ✅ `Cabang::all()` di-cache 1 jam via `Cache::remember('cabangs_all', 3600, ...)`
- ✅ Tersedia via `app('cabangs')` di seluruh aplikasi

---

## 📊 Ringkasan Dampak Optimasi

| Area | Sebelum | Sesudah |
|------|---------|---------|
| Dashboard monthly | 12 queries | 1 query |
| Dashboard yearly | 5 queries | 1 query |
| Dashboard stats | 4 queries/request | Cached 5 menit |
| Pelanggan index | Load ALL records ke memory | Paginate di DB |
| updateStats() | 2 queries per call | 1 query per call |
| Laporan preview | buildQuery() 2x + 3 aggregate queries | 1 aggregate query |
| DB Indexes | Hanya pid, cabang_id, class, tanggal | +6 indexes baru |

---

## 📝 Catatan Teknis

- Semua perubahan **backward-compatible** — tidak ada fitur yang dihapus
- Tidak ada perubahan UI/view
- Logic bisnis (class calculation, PID generation) tidak berubah
- Cache `dashboard_stats` akan otomatis expire setiap 5 menit
- Cache `cabangs_all` akan expire setiap 1 jam
- Jika ada perubahan data cabang, jalankan: `php artisan cache:clear`
