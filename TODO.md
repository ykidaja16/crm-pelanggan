# TODO - Penambahan Kolom NIK

## Progress Implementasi

- [x] 1. Buat migration baru untuk kolom NIK
- [x] 2. Update Model Pelanggan (tambahkan 'nik' ke $fillable)
- [x] 3. Update Controller Pelanggan (store, searchByPid, update)
- [x] 4. Update Import Kunjungan (processAllRows, processRow)
- [x] 5. Update View create.blade.php (tambah input NIK di sebelah Nama)
- [x] 6. Update View index.blade.php (update placeholder pencarian, tambah kolom NIK di tabel)
- [x] 6b. Update View show.blade.php (tambah NIK di bawah PID di Data Pelanggan)
- [x] 7. Update View khusus.blade.php (tambah input NIK, update format import)
- [x] 8. Update Export PelangganExport (tambah kolom NIK)
- [x] 9. Update LaporanController (update pencarian include NIK)
- [x] 10. Update PelangganBulkExport (tambah kolom NIK)
- [x] 11. Update PelangganImportExportController (validasi 13 kolom, template download, **FIX: simpan NIK saat import**)
- [ ] 12. Jalankan migration

## Catatan
- NIK bersifat nullable
- Bisa diisi "TIDAK ADA IDENTITAS" atau dikosongi
- Tidak mengubah rumus kelas pelanggan, total kedatangan, atau view halaman
- Format import pelanggan biasa: 12 kolom (NIK di kolom terakhir/index 11)
- Format import pelanggan khusus: 13 kolom (Kategori Khusus di index 12, NIK di index 11)
