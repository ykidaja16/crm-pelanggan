# TODO Revisi Filter Kelas Berdasarkan Periode

- [x] Update `PelangganController@index`:
  - [x] Jika filter periode aktif, filter `kelas` harus berdasarkan kelas pada akhir periode (historis), bukan `pelanggans.class` current.
  - [x] Jika tanpa periode, tetap gunakan logika lama (`pelanggans.class`).
- [x] Update `LaporanController::buildQuery`:
  - [x] Terapkan logika filter kelas yang sama agar konsisten dengan Dashboard Pelanggan.
- [x] Validasi cepat:
  - [x] Pastikan UI/design/flow tidak berubah.
  - [x] Pastikan filter lain (omset, kedatangan, cabang, tipe) tetap berjalan.
