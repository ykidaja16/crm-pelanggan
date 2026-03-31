# TODO: Revisi Export Excel - Total Biaya & Kelas Sesuai Range Tanggal

## Status: SELESAI ✅

## Masalah
Saat export Excel dengan filter Periode Range Tanggal:
- Kolom **Total Biaya** menampilkan nilai ALL-TIME dari DB, bukan akumulasi s/d akhir range
- Kolom **Total Kedatangan** menampilkan nilai ALL-TIME dari DB, bukan akumulasi s/d akhir range
- Kolom **Kelas** menampilkan kelas SAAT INI, bukan kelas pada akhir range tanggal

## Contoh Kasus (Pelanggan Diky)
- Kunjungan 1: 16 Des 2024, biaya 20.000
- Kunjungan 2: 2 Feb 2025, biaya 50.000
- Kunjungan 3: 6 Mar 2025, biaya 10.000
- Kunjungan 4: 20 Mar 2025, biaya 20.000

| Filter | Total Biaya Seharusnya | Kunjungan Seharusnya |
|--------|------------------------|----------------------|
| 1-31 Des 2024 | 20.000 | 1 |
| 1-28 Feb 2025 | 70.000 (akumulasi s/d Feb) | 2 |
| 1-31 Mar 2025 | 100.000 (akumulasi s/d Mar) | 4 |

## File yang Diubah
- [x] `app/Exports/PelangganExport.php`

## Steps

### Step 1: Update `app/Exports/PelangganExport.php`
- [x] Tambah eager load `classHistories` (urut ASC) ke query utama
- [x] Setelah filter periode, hitung nilai range per pelanggan:
  - `total_biaya_range` = `$p->kunjungans->sum('biaya')` (kunjungans sudah filtered <= endDate)
  - `total_kedatangan_range` = `$p->kunjungans->sum('total_kedatangan')`
  - `class_at_range` = kelas pada endDate via `getClassAtDate()`
- [x] Update filter omset range → gunakan `total_biaya_range`
- [x] Update filter kedatangan range → gunakan `total_kedatangan_range`
- [x] Update output map → gunakan nilai range, bukan nilai ALL-TIME dari DB
- [x] Untuk `type == 'semua'` (tanpa endDate) → tetap gunakan nilai ALL-TIME (tidak berubah)
