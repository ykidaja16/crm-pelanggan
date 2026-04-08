# TODO: Validasi Urutan Rollback Import

## Task
Implementasi validasi urutan rollback import Excel - rollback harus dilakukan secara berurutan dari yang terbaru ke yang terlama (LIFO).

## Plan & Progress

### 1. Model ImportBatch - Tambah method canBeRolledBack()
- [x] Tambah method untuk cek apakah batch bisa di-rollback
- [x] Logika: tidak ada batch lain di cabang yang sama dengan imported_at lebih baru dan status != 'rolled_back'

### 2. Controller ImportBatchController - Update method rollback()
- [x] Tambah validasi setelah cek isRolledBack()
- [x] Query batch di cabang yang sama dengan imported_at > batch ini dan status != 'rolled_back'
- [x] Jika ada, return error dengan pesan yang jelas

### 3. View index.blade.php - Update kondisi tombol
- [x] Ganti kondisi tombol dari !$batch->isRolledBack() menjadi $batch->canBeRolledBack()
- [x] Tambahkan indikator visual (badge "Terblokir" dengan icon lock) untuk batch yang tidak bisa di-rollback

## Implementation Notes
- Tidak mengubah design UI yang sudah ada
- Flow bisnis tetap sama, hanya ditambah validasi urutan
- Validasi di backend untuk keamanan, di frontend untuk UX
