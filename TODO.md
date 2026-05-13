# TODO Revisi Fitur Pelanggan

- [x] Update `PelangganController@update` agar Admin bisa direct edit data pelanggan (tanpa approval), tetap pertahankan validasi & logging.
- [x] Update `resources/views/pelanggan/edit.blade.php` agar Admin dan Super Admin menggunakan form direct update yang sama.
- [x] Revisi fitur import mismatch per baris:
  - [x] Backend import kirim `mismatch_rows` terstruktur (row, pid, db_nama, excel_nama, key)
  - [x] Tambah endpoint `sync` untuk update nama berdasarkan pilihan checkbox per baris
  - [x] UI error import tampilkan checkbox per baris mismatch + tombol `Sesuaikan Data`
  - [x] Tombol `Sesuaikan Data` hanya update baris yang dipilih, lalu user re-import manual
- [x] Rapikan TODO (checklist selesai) setelah implementasi.
