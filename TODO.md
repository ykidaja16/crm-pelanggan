# TODO Refactor Kelompok Pelanggan FK + Final Testing

- [ ] Rapikan `PelangganController`:
  - [ ] Hapus semua write `pelanggans.kelompok_pelanggan`
  - [ ] Ubah create/update/edit kunjungan agar pakai `kelompok_pelanggan_id`
  - [ ] Rapikan import CSV agar map kode -> ID master
- [ ] Rapikan `KunjunganImport`:
  - [ ] Map `kelompok_pelanggan` (mandiri/klinisi) -> `kelompok_pelanggan_id`
  - [ ] Pastikan tidak ada assignment ke `pelanggans.kelompok_pelanggan`
- [ ] Validasi `ApprovalRequestController`:
  - [ ] Pastikan tidak ada write legacy kolom kelompok di pelanggan/kunjungan
  - [ ] Pastikan syntax valid
- [ ] Cek view/form terkait agar tetap kirim value kelompok pelanggan
- [ ] Regression scan semua referensi `kelompok_pelanggan` (legacy)
- [ ] Testing menyeluruh:
  - [ ] import excel/csv
  - [ ] tambah pelanggan + kunjungan
  - [ ] pelanggan khusus + approval
  - [ ] edit/hapus kunjungan via approval
