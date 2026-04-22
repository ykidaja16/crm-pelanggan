# TODO - Validasi Duplikat PID dengan Nama Berbeda dalam Satu File

## Task
Tambahkan validasi saat import Excel: jika dalam 1 file ada PID yang sama tapi Nama Pasien berbeda, maka gagal import dan tampilkan informasi baris mana yang bermasalah.

## Plan
- [x] Analisis file PelangganImportExportController.php
- [x] Analisis file KunjunganImport.php
- [x] Buat plan implementasi
- [x] Dapatkan approval dari user
- [ ] Implementasi: Tambahkan array `$pidNamaMap` untuk tracking
- [ ] Implementasi: Tracking PID dan Nama dalam loop validasi
- [ ] Implementasi: Cek duplikat dengan nama berbeda setelah loop
- [ ] Implementasi: Format error message dengan informasi baris
- [ ] Test dan verifikasi

## Implementation Details

### File yang diedit:
- `app/Http/Controllers/PelangganImportExportController.php`

### Perubahan:
1. Tambahkan `$pidNamaMap = []` sebelum loop validasi (sekitar baris 140)
2. Dalam loop validasi, setelah dapat `$pid` dan `$nama`, simpan ke array:
   ```php
   if (!empty($pid) && !empty($nama)) {
       if (!isset($pidNamaMap[$pid])) {
           $pidNamaMap[$pid] = ['nama' => $nama, 'baris' => $rowNumber];
       } elseif (strtolower($pidNamaMap[$pid]['nama']) !== strtolower($nama)) {
           // Nama berbeda, catat error
       }
   }
   ```
3. Setelah loop selesai (sebelum cek `!empty($errors)`), cek duplikat:
   ```php
   foreach ($pidNamaMap as $pid => $data) {
       // Cek jika ada duplikat dengan nama berbeda
   }
   ```

### Contoh Error Message:
"Baris 10 dan 175: PID LX001 memiliki nama berbeda ('Diky' vs 'Diky Mega')"
