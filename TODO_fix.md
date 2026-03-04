# TODO: Fix Riwayat Perubahan Kelas dan Edit Kunjungan

## Masalah:
1. Riwayat Perubahan Kelas tidak tercatat saat perubahan status (potensial ke loyal)
2. Edit Kunjungan mengubah total_kedatangan (seharusnya hanya tanggal dan biaya yang berubah)

## Status: ✅ SELESAI

### 1. app/Models/Pelanggan.php ✅
- [x] Tambahkan method `updateBiayaAndClass()` untuk update biaya dan class tanpa mengubah total_kedatangan
- [x] Pastikan method ini mencatat riwayat perubahan kelas jika class berubah

**Detail perubahan:**
- Method baru `updateBiayaAndClass($biayaDifference, $visitDate)` ditambahkan
- Mengupdate `total_biaya` dengan selisih biaya (tidak menghitung ulang dari DB)
- Menghitung class baru berdasarkan `total_kedatangan` (tetap) dan `total_biaya` (baru)
- Mencatat riwayat perubahan kelas ke `classHistories` jika class berubah

### 2. app/Http/Controllers/PelangganController.php ✅
- [x] Modifikasi `updateKunjungan()` untuk menggunakan method baru dari model
- [x] Hitung selisih biaya dan update total_biaya pelanggan
- [x] Hitung class baru berdasarkan total_kedatangan (tetap) dan total_biaya (baru)
- [x] Catat riwayat perubahan kelas jika class berubah

**Detail perubahan:**
- `$biayaDifference = $request->biaya - $kunjungan->biaya` - hitung selisih biaya
- Ganti `$pelanggan->updateStats()` dengan `$pelanggan->updateBiayaAndClass()`
- Total kedatangan tetap sesuai data import, tidak dihitung ulang dari jumlah record kunjungan

### 3. app/Imports/KunjunganImport.php ✅
- [x] Logika pencatatan riwayat kelas sudah benar (tidak perlu perubahan)
- [x] Sudah mencatat riwayat untuk pelanggan baru dan perubahan kelas

**Catatan:** Logika di `processRow()` sudah mencatat riwayat kelas dengan benar:
- Pelanggan baru: catat kelas awal
- Pelanggan existing dengan perubahan kelas: catat perubahan

## Ringkasan Perubahan:

| Aspek | Sebelum | Sesudah |
|-------|---------|---------|
| Edit Kunjungan | `updateStats()` menghitung ulang total_kedatangan dari DB | `updateBiayaAndClass()` hanya update biaya, total_kedatangan tetap |
| Riwayat Kelas | Tercatat via `updateStats()` | Tercatat via `updateBiayaAndClass()` dengan reason 'Perubahan dari edit kunjungan' |
| Total Kedatangan | Berubah saat edit (menyesuaikan jumlah record) | Tetap sesuai nilai import |

## Testing yang direkomendasikan:
1. Import data dengan total_kedatangan = 3
2. Edit kunjungan (ubah biaya)
3. Verifikasi total_kedatangan masih = 3 (tidak berubah)
4. Verifikasi riwayat perubahan kelas tercatat jika ada perubahan class
