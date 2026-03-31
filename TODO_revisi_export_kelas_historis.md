# TODO: Revisi Export Excel - Kelas Historis & Search Per Kunjungan

## Issue 1: KunjunganExport - Kolom Kelas Harus Historis
- [x] Tambah property `$classHistories` di `KunjunganExport`
- [x] Load class histories (ASC by changed_at) di constructor
- [x] Tambah method `getClassAtDate($visitDate)` untuk lookup kelas historis
- [x] Ganti `$this->pelanggan->class` dengan `$this->getClassAtDate($k->tanggal_kunjungan)`

## Issue 2: PelangganExport (Search Mode) - Per Kunjungan + Tanggal Lengkap
- [x] Ubah search mode: iterate per kunjungan individual (bukan group per bulan)
- [x] Tampilkan tanggal lengkap format `d-m-Y` (bukan `Y-m`)
- [x] Gunakan class history untuk kelas historis per kunjungan
- [x] Update headings search mode: 'Bulan' → 'Tanggal Kunjungan', hapus 'Kunjungan Terakhir'
- [x] Tambah method `getClassAtDate($visitDate, $classHistories, $currentClass)`

## Files to Edit
- [x] `app/Exports/KunjunganExport.php`
- [x] `app/Exports/PelangganExport.php`

## SELESAI ✅
