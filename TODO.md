# TODO: Implementasi Riwayat Perubahan Kelas Pelanggan

## Progress
- [x] 1. Buat Migration untuk tabel pelanggan_class_histories
- [x] 2. Buat Model PelangganClassHistory
- [x] 3. Modifikasi Model Pelanggan (tambah relasi dan logika pencatatan)
- [x] 4. Modifikasi PelangganController (load data riwayat kelas)
- [x] 5. Update View show.blade.php (tampilkan riwayat kelas)
- [x] 6. Jalankan migrasi database
- [x] 7. Testing

## Update: Export Excel dengan Filter
- [x] 1. Update PelangganExport.php - tambah parameter filter (cabang_id, kelas, omset_range, kedatangan_range)
- [x] 2. Update PelangganController::export() - terima dan kirim semua parameter filter
- [x] 3. Update index.blade.php - kirim semua parameter filter ke URL export




## Detail Implementasi

### 1. Migration
Tabel: pelanggan_class_histories
- pelanggan_id (foreign key)
- previous_class (nullable)
- new_class
- changed_at (datetime)
- changed_by (nullable)
- reason (nullable)

### 2. Model
PelangganClassHistory dengan relasi belongsTo Pelanggan

### 3. Modifikasi Pelanggan Model
- Tambah method classHistories()
- Modifikasi updateStats() untuk mencatat perubahan kelas

### 4. Controller
- Method show(): load classHistories bersama kunjungans

### 5. View
- Tampilkan timeline riwayat kelas di halaman detail pelanggan
- Tunjukkan kapan menjadi Prioritas, Loyal, Potensial
