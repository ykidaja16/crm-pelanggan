# TODO: Fix Kelas Historis - Dashboard & Export Excel

## Status: ✅ SELESAI

---

## Masalah yang Diperbaiki

### Issue 1 - Export Excel filter Periode (Kelas salah)
- **Gejala**: Filter bulan Mei 2021 → view benar (Prioritas), export salah (Potensial)
- **Root cause**: `PelangganExport.php` non-search case: ketika `classHistories` kosong, fallback ke `$p->class` (kelas saat ini)
- **Fix**: Ketika `classHistories` kosong → hitung dinamis dengan `calculateClass()` menggunakan biaya/kedatangan kumulatif s.d. `endDate`

### Issue 2 - Export by Search PID/Nama (semua baris kelas sama)
- **Gejala**: Pelanggan 2 kunjungan (Mei=Potensial, Juni=Prioritas) → export semua baris Potensial
- **Root cause**: `PelangganExport.php` search case: ketika `classHistories` kosong, fallback ke 'Potensial'
- **Fix**: Ketika `classHistories` kosong → hitung dinamis dengan `calculateClass()` per kunjungan secara kumulatif

### Issue 3 - Dashboard view kolom Kelas tidak sesuai periode
- **Gejala**: Filter Mei 2021 → tampil "Prioritas" padahal harusnya "Potensial"
- **Root cause**: View menggunakan `$p->class` (kelas saat ini), bukan kelas historis di periode tersebut
- **Fix (view)**: `$class = $p->class_at_period ?? $p->class ?? 'Potensial'`
- **Fix (controller)**: Setelah pagination, batch-load histories + hitung `class_at_period` per pelanggan. Ketika history kosong → hitung dinamis dengan `calculateClass()` menggunakan `biaya_periode`/`kedatangan_periode`

### Issue 4 - Detail pelanggan Export Riwayat Kunjungan (semua baris kelas sama)
- **Gejala**: Pelanggan 2 kunjungan (Mei=Potensial, Juni=Prioritas) → export semua baris Potensial
- **Root cause**: `KunjunganExport.php`: ketika `classHistories` kosong, fallback ke `$this->pelanggan->class` (kelas saat ini)
- **Fix**: Ketika `classHistories` kosong → hitung dinamis dengan `calculateClass()` per kunjungan secara kumulatif

---

## File yang Diubah

### [x] `app/Models/Pelanggan.php`
- Tambah static method `resolveClassAtDate($date, $classHistories, $currentClass)`:
  - Iterasi history ASC, cari entry terakhir yang `changed_at <= $date`
  - Fallback ke `previous_class` dari entry pertama jika kunjungan sebelum perubahan pertama
  - Fallback ke `$currentClass ?? 'Potensial'` jika tidak ada history

### [x] `app/Http/Controllers/PelangganController.php`
- Inisialisasi `$endOfPeriod = null` sebelum blok if-else periode
- Set `$endOfPeriod` untuk perbulan, pertahun, dan range
- Setelah `$pelanggan = $query->paginate(30)->withQueryString()`:
  - Batch-load `PelangganClassHistory` untuk semua pelanggan di halaman
  - Batch-load `highValuePelangganIds` (kunjungan >= 4 juta s.d. endOfPeriod)
  - Set `$p->class_at_period` per pelanggan:
    - Jika history kosong → `calculateClass(kedatangan_periode, biaya_periode, hasHighValue, isKhusus)`
    - Jika ada history → `resolveClassAtDate(endOfPeriod, histories, $p->class)`

### [x] `resources/views/pelanggan/index.blade.php`
- Ubah `$class = $p->class ?? 'Potensial'`
  → `$class = $p->class_at_period ?? $p->class ?? 'Potensial'`

### [x] `app/Exports/PelangganExport.php`
- **Search case** (satu baris per kunjungan):
  - Jika `classHistories` kosong → `calculateClass(cumulativeKedatangan, cumulativeBiaya, hasHighValue, isKhusus)`
  - Jika ada history → `getClassAtDate(tanggal_kunjungan, classHistories, $pelanggan->class)`
- **Non-search case** (filter periode):
  - Jika `classHistories` kosong → `calculateClass(total_kedatangan_range, total_biaya_range, hasHighValue, isKhusus)`
  - Jika ada history → `getClassAtDate(endDate, classHistories, $p->class)`
- `getClassAtDate()` fallback: `return $currentClass ?? 'Potensial'` (bukan hardcode 'Potensial')

### [x] `app/Exports/KunjunganExport.php`
- `collection()`: sort kunjungans ASC, track cumulative biaya/kedatangan/hasHighValue
- Jika `classHistories` kosong → `calculateClass(cumulativeKedatangan, cumulativeBiaya, hasHighValue, isKhusus)`
- Jika ada history → `getClassAtDate(tanggal_kunjungan)`
- `getClassAtDate()` fallback: `return $this->pelanggan->class ?? 'Potensial'`

---

## Logika Inti: Menentukan Kelas Historis

### Kasus 1: Ada `classHistories`
Gunakan `resolveClassAtDate()` / `getClassAtDate()`:
- Iterasi history urut ASC
- Ambil `new_class` dari entry terakhir yang `changed_at <= tanggal_referensi`
- Jika tidak ada entry sebelum tanggal → gunakan `previous_class` dari entry pertama
- Jika tidak ada history sama sekali → fallback ke `$currentClass`

### Kasus 2: Tidak ada `classHistories` (data import lama)
Hitung dinamis dengan `calculateClass()`:
- Gunakan biaya/kedatangan kumulatif s.d. tanggal referensi
- Cek apakah ada kunjungan high-value (>= 4 juta) s.d. tanggal referensi
- Hasilnya mencerminkan kelas yang seharusnya berlaku pada saat itu

---

## Syntax Check
- [x] `app/Models/Pelanggan.php` → No syntax errors
- [x] `app/Http/Controllers/PelangganController.php` → No syntax errors
- [x] `app/Exports/PelangganExport.php` → No syntax errors
- [x] `app/Exports/KunjunganExport.php` → No syntax errors
