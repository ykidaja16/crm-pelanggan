# TODO: Implementasi Import Batch Rollback

## Status: ✅ COMPLETED (dengan bugfix rollback Mode B)

### Langkah-langkah Implementasi

- [x] 1. Buat migration: tambah kolom `import_batch_id` ke tabel `kunjungans`
- [x] 2. Buat migration: buat tabel `import_batches`
- [x] 3. Buat migration: buat tabel `import_batch_pelanggan_snapshots`
- [x] 4. Buat Model `ImportBatch`
- [x] 5. Buat Model `ImportBatchPelangganSnapshot`
- [x] 6. Modifikasi `PelangganImportExportController` (generate batch_id, simpan snapshot, catat batch)
- [x] 7. Buat `ImportBatchController` (index + rollback)
- [x] 8. Buat View `resources/views/import-batch/index.blade.php`
- [x] 9. Update `routes/web.php` (tambah route IT)
- [x] 10. Update sidebar di `layouts/main.blade.php` (tambah menu IT)
- [x] 11. Jalankan migration ✅ (3 migrations ran: add_import_batch_id_to_kunjungans, create_import_batches, create_import_batch_pelanggan_snapshots)

### Bugfix Session (Rollback "Tidak ada snapshot")

- [x] **Fix root cause**: Import Excel lama menggunakan `KunjunganImport` class → tidak menyimpan snapshot
- [x] **Fix**: Ganti semua format (CSV + Excel) menggunakan `processCsvImport()` → snapshot selalu tersimpan
- [x] **Fix `parseCsvDate()`**: Tambah support Excel serial date (numeric) via `PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject()`
- [x] **Fix rollback dual-mode**:
  - **Mode A** (ada snapshot): restore dari snapshot — akurat, untuk import baru
  - **Mode B** (tidak ada snapshot): recalculate dari kunjungan tersisa — fallback untuk import lama
- [x] Syntax check semua file: ✅ PASS
- [x] Route list verified: ✅ 2 routes terdaftar

### Ringkasan Perubahan

| File | Perubahan |
|------|-----------|
| `database/migrations/2026_04_02_095146_add_import_batch_id_to_kunjungans_table.php` | Tambah kolom `import_batch_id` (nullable string 36) ke tabel kunjungans |
| `database/migrations/2026_04_02_095157_create_import_batches_table.php` | Tabel baru untuk mencatat sesi import |
| `database/migrations/2026_04_02_095204_create_import_batch_pelanggan_snapshots_table.php` | Tabel baru untuk snapshot data pelanggan sebelum import |
| `app/Models/ImportBatch.php` | Model baru dengan relasi user, cabang, rolledBackByUser, snapshots |
| `app/Models/ImportBatchPelangganSnapshot.php` | Model baru dengan relasi pelanggan |
| `app/Http/Controllers/ImportBatchController.php` | Controller baru: index() + rollback(). **Fix**: dual-mode rollback (Mode A snapshot-based + Mode B recalculate) — tidak lagi block jika tidak ada snapshot |
| `app/Http/Controllers/PelangganImportExportController.php` | Tambah use statements, generate batchId, simpan snapshot, tag kunjungan, catat ImportBatch. **Fix**: semua format (CSV+Excel) melalui `processCsvImport()`. **Fix `parseCsvDate()`**: support Excel serial date |
| `resources/views/import-batch/index.blade.php` | View baru: tabel riwayat import + modal konfirmasi rollback |
| `routes/web.php` | Tambah 2 route IT: GET /import-batches, POST /import-batches/{batchId}/rollback |
| `resources/views/layouts/main.blade.php` | Tambah menu "Riwayat Import" di sidebar IT |
