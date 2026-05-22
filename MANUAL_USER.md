# Manual Pengguna — SIMA Lab CRM Pelanggan

**Versi:** 1.0  
**Tanggal:** Mei 2026  
**Sistem:** SIMA Lab — Sistem Manajemen Pelanggan Laboratorium

---

## Daftar Isi

1. [Pendahuluan](#1-pendahuluan)
2. [Login & Keamanan Sesi](#2-login--keamanan-sesi)
3. [Navigasi Utama](#3-navigasi-utama)
4. [Dashboard](#4-dashboard)
5. [Manajemen Pelanggan](#5-manajemen-pelanggan)
6. [Input Data Pelanggan](#6-input-data-pelanggan)
7. [Import & Export Data](#7-import--export-data)
8. [Kunjungan Pelanggan](#8-kunjungan-pelanggan)
9. [Pelanggan Khusus](#9-pelanggan-khusus)
10. [Special Day Member](#10-special-day-member)
11. [Laporan](#11-laporan)
12. [Retensi Pelanggan](#12-retensi-pelanggan)
13. [Cari Berdasarkan Nomor HP](#13-cari-berdasarkan-nomor-hp)
14. [Approval Request](#14-approval-request)
15. [Manajemen Pengguna (IT)](#15-manajemen-pengguna-it)
16. [Manajemen Cabang (IT)](#16-manajemen-cabang-it)
17. [Log Aktivitas (IT)](#17-log-aktivitas-it)
18. [Riwayat Import & Rollback (IT)](#18-riwayat-import--rollback-it)
19. [Profil Pengguna](#19-profil-pengguna)
20. [Peran & Hak Akses](#20-peran--hak-akses)
21. [Klasifikasi Pelanggan](#21-klasifikasi-pelanggan)
22. [Pertanyaan Umum (FAQ)](#22-pertanyaan-umum-faq)

---

## 1. Pendahuluan

**SIMA Lab** adalah aplikasi CRM (Customer Relationship Management) yang dirancang khusus untuk kebutuhan laboratorium medis. Sistem ini membantu staf laboratorium dalam:

- Mencatat dan mengelola data pelanggan/pasien
- Melacak riwayat kunjungan dan pengeluaran pelanggan
- Mengklasifikasikan pelanggan berdasarkan frekuensi kunjungan dan nilai transaksi
- Menganalisis retensi pelanggan dan tren kunjungan
- Menghasilkan laporan untuk keperluan manajemen
- Mendukung operasional multi-cabang

### Peran Pengguna dalam Sistem

| Peran | Singkatan Fungsi |
|---|---|
| **Super Admin** | Menyetujui atau menolak permintaan perubahan data sensitif |
| **Admin** | Mengelola data pelanggan secara penuh di cabang yang ditetapkan |
| **Direktur** | Akses setara Admin, khusus untuk jenjang manajerial |
| **User** | Melihat data, mencari pelanggan, melihat laporan |
| **IT** | Mengelola pengguna, cabang, log aktivitas, dan riwayat import |

---

## 2. Login & Keamanan Sesi

### Cara Login

1. Buka aplikasi di browser.
2. Masukkan **Username** dan **Password** yang telah diberikan oleh administrator IT.
3. Klik tombol **Masuk**.

### Ketentuan Keamanan

- Sistem akan **mengunci akun sementara** setelah **5 kali percobaan login yang gagal** dalam satu menit. Tunggu sebentar sebelum mencoba kembali.
- Sesi akan **otomatis berakhir** setelah **30 menit tidak aktif**. Anda akan diarahkan kembali ke halaman login.
- Selalu klik **Logout** saat selesai menggunakan aplikasi, terutama pada komputer yang digunakan bersama.

### Lupa Password

1. Klik tautan **Lupa Password** di halaman login.
2. Masukkan email akun Anda.
3. Sistem akan membuat permintaan reset password yang akan ditindaklanjuti oleh IT.
4. IT akan mereset password Anda dan menginformasikan password baru.

---

## 3. Navigasi Utama

Setelah login, Anda akan melihat **sidebar menu** di sisi kiri layar. Menu yang tampil menyesuaikan peran akun Anda.

### Menu Berdasarkan Peran

**Semua Pengguna:**
- Dashboard
- Data Pelanggan
- Profil

**Admin / Direktur:**
- Dashboard
- Data Pelanggan
- Input Data Pelanggan
- Update NIK (Bulk)
- Pelanggan Khusus
- Special Day Member
- Laporan
- Retensi
- Cari Berdasarkan HP
- Approval Request

**Super Admin:**
- Semua menu Admin +
- Approval Request (khusus panel persetujuan)

**IT:**
- Dashboard
- Manajemen Pengguna
- Manajemen Cabang
- Log Aktivitas
- Riwayat Import (Import Batches)
- Permintaan Reset Password

### Meminimalkan Sidebar

Klik ikon panah (hamburger) di bagian atas sidebar untuk memperkecil menu menjadi ikon-ikon kecil saja. Klik kembali untuk menampilkan menu lengkap.

---

## 4. Dashboard

Halaman dashboard menampilkan ringkasan statistik dan grafik performa pelanggan.

### Kartu Statistik Utama

| Kartu | Keterangan |
|---|---|
| Total Pelanggan | Jumlah seluruh pelanggan aktif |
| Kunjungan Bulan Ini | Total kunjungan dalam bulan berjalan |
| Kunjungan Tahun Ini | Total kunjungan dalam tahun berjalan |
| Pelanggan Baru | Pelanggan yang baru terdaftar |

### Grafik

- **Grafik Pertumbuhan Pasien Bulanan** — tren kunjungan per bulan
- **Grafik Pertumbuhan Pasien Tahunan** — perbandingan antar tahun
- **Grafik Berdasarkan Kelas** — distribusi pelanggan per klasifikasi

### Filter Cabang

Jika Anda memiliki akses ke lebih dari satu cabang, pilih tab cabang yang diinginkan di bagian atas dashboard untuk melihat data masing-masing cabang.

### Export Data Dashboard

Klik tombol **Export Detail Dashboard** untuk mengunduh data dalam format Excel sesuai filter yang aktif.

---

## 5. Manajemen Pelanggan

Menu **Data Pelanggan** menampilkan daftar seluruh pelanggan di cabang Anda.

### Tampilan Daftar Pelanggan

Kolom yang tersedia:
- **PID** — nomor identitas pelanggan di sistem (unik per cabang)
- **Nama** — nama lengkap pelanggan
- **Cabang** — cabang tempat pelanggan terdaftar
- **No. HP** — nomor telepon
- **Tanggal Lahir**
- **Alamat / Kota**
- **Jumlah Kunjungan** — total kunjungan sepanjang waktu
- **Tanggal Kunjungan Terakhir**
- **Total Biaya** — akumulasi biaya seluruh kunjungan
- **Kelas** — klasifikasi pelanggan (Prioritas, Loyal, Potensial, Umum)
- **Aksi** — tombol Lihat Detail, Edit, Hapus

### Mencari Pelanggan

Gunakan kotak pencarian di bagian atas tabel untuk mencari berdasarkan:
- **PID**
- **Nama**
- **NIK** (Nomor Induk Kependudukan)

Ketik minimal 1 karakter, hasil akan muncul secara otomatis.

### Filter Data Pelanggan

Gunakan panel filter di atas tabel untuk menyempurnakan hasil:

| Filter | Pilihan |
|---|---|
| Cabang | Pilih satu atau semua cabang |
| Kelas | Prioritas / Loyal / Potensial / Umum |
| Omset | 0–<1 Juta / 1–4 Juta / >4 Juta |
| Frekuensi Kunjungan | ≤2 / 3–4 / >4 kunjungan |
| Periode | Bulanan / Tahunan / Semua Data |

### Mengurutkan Data

Klik judul kolom untuk mengurutkan data secara ascending atau descending.

### Melihat Detail Pelanggan

1. Klik ikon **mata** (lihat detail) pada baris pelanggan.
2. Halaman detail menampilkan:
   - Informasi lengkap pelanggan
   - Riwayat kunjungan
   - Riwayat perubahan kelas

### Mengedit Data Pelanggan

1. Klik ikon **pensil** (edit) pada baris pelanggan.
2. Ubah data yang diperlukan.
3. Klik **Simpan**.

> **Catatan:** Beberapa perubahan data sensitif memerlukan persetujuan Super Admin. Permintaan akan masuk ke antrian approval.

### Menghapus Pelanggan

1. Klik ikon **tempat sampah** (hapus) pada baris pelanggan.
2. Konfirmasi penghapusan pada dialog yang muncul.
3. Pelanggan akan dipindahkan ke **Recycle Bin** (terhapus sementara, dapat dipulihkan).

### Memulihkan Pelanggan (Recycle Bin)

1. Buka halaman **Data Pelanggan**.
2. Klik tab atau tombol **Recycle Bin**.
3. Cari pelanggan yang ingin dipulihkan.
4. Klik **Restore** untuk mengembalikan data pelanggan.

### Operasi Massal (Bulk)

- Centang kotak di kolom paling kiri untuk memilih beberapa pelanggan.
- Pilih aksi: **Hapus Massal** atau **Export Massal**.

---

## 6. Input Data Pelanggan

Menu **Input Data Pelanggan** digunakan untuk mendaftarkan pelanggan baru atau mencatat kunjungan pelanggan yang sudah ada.

> **Akses:** Admin, Direktur

### Mode 1 — Tambah Pelanggan Baru + Kunjungan Pertama

Digunakan untuk mendaftarkan pelanggan yang belum pernah terdaftar di sistem.

**Langkah-langkah:**
1. Pilih mode **"Tambah Pelanggan Baru"**.
2. Isi formulir data pelanggan:
   - **Nama Lengkap** (wajib)
   - **NIK** — Nomor Induk Kependudukan
   - **No. HP**
   - **Tanggal Lahir**
   - **Alamat & Kota**
3. Isi data kunjungan pertama:
   - **Tanggal Kunjungan** (wajib)
   - **Biaya/Omset** — nilai transaksi kunjungan
4. Klik **Simpan**.
5. Sistem akan secara otomatis menghasilkan **PID** unik untuk pelanggan baru.

### Mode 2 — Tambah Kunjungan Pelanggan yang Sudah Ada

Digunakan untuk mencatat kunjungan ulang pelanggan yang sudah terdaftar.

**Langkah-langkah:**
1. Pilih mode **"Tambah Kunjungan Pelanggan Lama"**.
2. Cari pelanggan berdasarkan PID atau Nama.
3. Pilih pelanggan dari hasil pencarian.
4. Isi data kunjungan:
   - **Tanggal Kunjungan** (wajib)
   - **Biaya/Omset** (wajib)
5. Klik **Simpan**.
6. Sistem akan memperbarui total kunjungan dan total biaya pelanggan secara otomatis.

---

## 7. Import & Export Data

### Import Data dari Excel

Fitur import memungkinkan penginputan data pelanggan dalam jumlah besar sekaligus.

> **Akses:** Admin, Direktur
> **Batas:** 3 kali import per menit per pengguna

**Langkah-langkah Import:**
1. Buka menu **Import Data**.
2. Unduh **Template Excel** dengan mengklik tombol **Download Template**.
3. Isi template sesuai format yang telah ditentukan (jangan ubah nama/urutan kolom).
4. Kembali ke halaman import, klik **Pilih File** dan pilih file yang sudah diisi.
5. Klik **Upload & Import**.
6. Sistem akan menampilkan **progress bar** selama proses berjalan.
7. Setelah selesai, sistem menampilkan ringkasan hasil: berhasil diimpor, data yang dilewati, dan kesalahan.

**Validasi Otomatis saat Import:**
- Memeriksa apakah PID sudah ada di database
- Mendeteksi ketidaksesuaian nama dengan PID yang sama
- Memvalidasi jumlah kolom
- Menghitung ulang kelas pelanggan secara otomatis

**Format file yang didukung:** `.xlsx`, `.xls`, `.csv`, `.txt`

### Export Data

**Export Daftar Pelanggan:**
1. Atur filter yang diinginkan di halaman Data Pelanggan.
2. Klik tombol **Export Excel**.
3. File Excel akan otomatis terunduh.

**Export Pelanggan Terpilih:**
1. Centang pelanggan yang ingin diekspor.
2. Klik **Export Massal**.
3. File Excel akan otomatis terunduh.

### Download Template

Klik **Download Template** di halaman Import untuk mendapatkan file Excel contoh dengan format kolom yang benar. Gunakan template ini sebagai dasar pengisian data.

---

## 8. Kunjungan Pelanggan

Menu **Kunjungan** menampilkan seluruh riwayat kunjungan dari semua pelanggan di cabang Anda.

### Melihat Riwayat Kunjungan

1. Buka menu **Kunjungan**.
2. Gunakan filter tanggal, cabang, dan pelanggan untuk mempersempit tampilan.
3. Klik nama pelanggan untuk melihat profil lengkapnya.

### Mengedit Data Kunjungan

1. Klik ikon **edit** pada baris kunjungan.
2. Ubah data yang diperlukan (tanggal, biaya).
3. Klik **Simpan**.

> **Catatan:** Pengeditan kunjungan mungkin memerlukan persetujuan Super Admin. Jika demikian, perubahan akan masuk sebagai **Approval Request** dan belum diterapkan sampai disetujui.

### Menghapus Kunjungan

1. Klik ikon **hapus** pada baris kunjungan.
2. Konfirmasi penghapusan.
3. Penghapusan juga mungkin memerlukan persetujuan tergantung kebijakan sistem.

---

## 9. Pelanggan Khusus

Pelanggan Khusus adalah pelanggan VIP atau pelanggan dengan kategori istimewa yang pengelolaannya memerlukan persetujuan tambahan.

> **Akses:** Admin, Direktur (tambah/edit); Super Admin (approve)

### Melihat Daftar Pelanggan Khusus

1. Buka menu **Pelanggan Khusus**.
2. Daftar menampilkan semua pelanggan yang sudah ditandai sebagai khusus.

### Menambahkan Pelanggan Khusus

1. Klik **Tambah Pelanggan Khusus**.
2. Cari pelanggan berdasarkan PID atau nama.
3. Pilih **jenis/kategori** khusus.
4. Tambahkan **catatan** jika diperlukan.
5. Klik **Ajukan**.
6. Permintaan akan masuk ke antrian **Approval Request** dan menunggu persetujuan Super Admin.

### Status Permintaan

- **Menunggu** — belum ditinjau Super Admin
- **Disetujui** — pelanggan berhasil ditandai sebagai khusus
- **Ditolak** — permintaan tidak disetujui (lihat catatan penolakan)

---

## 10. Special Day Member

Menu **Special Day Member** digunakan untuk keperluan program marketing berdasarkan data khusus pelanggan.

> **Akses:** Admin, Direktur, User

### Sub-menu: Ulang Tahun (Birthday)

Menampilkan daftar pelanggan berdasarkan tanggal lahir untuk program ucapan atau promosi ulang tahun.

**Langkah penggunaan:**
1. Buka **Special Day > Ulang Tahun**.
2. Atur filter:
   - **Rentang tanggal** (misalnya: 1–31 Mei)
   - **Cabang**
   - **Kelas Pelanggan**
3. Klik **Tampilkan**.
4. Klik **Export Excel** untuk mengunduh daftar pelanggan berulang tahun.

### Sub-menu: Kunjungan Terakhir

Menampilkan pelanggan berdasarkan tanggal kunjungan terakhir mereka.

**Langkah penggunaan:**
1. Buka **Special Day > Kunjungan Terakhir**.
2. Atur filter:
   - **Rentang tanggal kunjungan terakhir** (misalnya: 3–6 bulan lalu)
   - **Cabang**
   - **Kelas Pelanggan**
3. Klik **Tampilkan**.
4. Gunakan data ini untuk program re-engagement pelanggan yang sudah lama tidak berkunjung.
5. Klik **Export Excel** untuk mengunduh daftar.

---

## 11. Laporan

Menu **Laporan** menyediakan laporan komprehensif data pelanggan yang dapat difilter dan diekspor.

> **Akses:** Admin, Direktur, User, Super Admin

### Membuat Laporan

1. Buka menu **Laporan**.
2. Atur parameter laporan:
   - **Periode** — Bulanan, Tahunan, atau Rentang Tanggal Kustom
   - **Cabang** — pilih satu atau semua cabang
   - **Kelas Pelanggan** — semua atau kelas tertentu
   - **Omset** — semua atau rentang tertentu
   - **Frekuensi Kunjungan** — semua atau rentang tertentu
3. Klik **Tampilkan Laporan** untuk preview di layar.

### Isi Laporan

- Daftar pelanggan beserta statistik masing-masing
- Ringkasan total kunjungan dan total pendapatan
- Breakdown pelanggan per kelas
- Analisis berdasarkan periode yang dipilih

### Export Laporan

- Klik **Export Excel** untuk mengunduh laporan dalam format `.xlsx`.
- Klik **Print** untuk mencetak laporan dalam tampilan yang sudah diformat.

---

## 12. Retensi Pelanggan

Menu **Retensi** menampilkan analisis pelanggan berdasarkan frekuensi dan konsistensi kunjungan mereka.

> **Akses:** Admin, Direktur, User, Super Admin

### Kategori Retensi

| Status | Keterangan |
|---|---|
| **Aktif** | Pelanggan dengan kunjungan rutin |
| **Berisiko (At-Risk)** | Frekuensi kunjungan menurun |
| **Dorman** | Tidak berkunjung dalam periode tertentu |
| **Hilang (Lost)** | Tidak ada kunjungan dalam waktu sangat lama |

### Cara Menggunakan

1. Buka menu **Retensi**.
2. Atur filter:
   - **Periode** — Bulanan atau Tahunan
   - **Tahun** — pilih tahun referensi
   - **Cabang**
   - **Status Retensi**
3. Sistem menampilkan tabel pelanggan beserta status retensinya.
4. Gunakan data ini untuk strategi follow-up dan mempertahankan pelanggan.

---

## 13. Cari Berdasarkan Nomor HP

Fitur ini memungkinkan pencarian massal pelanggan menggunakan daftar nomor HP dari file Excel.

> **Akses:** Admin, Direktur, User

### Langkah Penggunaan

1. Buka menu **Cari Berdasarkan HP**.
2. Siapkan file Excel/CSV berisi daftar nomor HP yang ingin dicari (satu nomor per baris).
3. Klik **Pilih File** dan upload file tersebut.
4. Klik **Cari**.
5. Sistem akan menampilkan dua daftar:
   - **Ditemukan** — nomor HP yang cocok dengan data pelanggan (beserta detail pelanggan)
   - **Tidak Ditemukan** — nomor HP yang tidak ada di database

### Export Hasil

- Klik **Export Ditemukan** untuk mengunduh daftar pelanggan yang ditemukan.
- Klik **Export Tidak Ditemukan** untuk mengunduh daftar nomor yang tidak teridentifikasi.

> **Catatan:** Pencarian ini melintasi **semua cabang**, tidak terbatas pada cabang pengguna yang login.

---

## 14. Approval Request

Sistem Approval Request memastikan bahwa perubahan data sensitif melalui proses persetujuan oleh Super Admin.

### Jenis Permintaan yang Memerlukan Approval

| Jenis | Keterangan |
|---|---|
| **Pelanggan Khusus** | Penambahan atau perubahan status pelanggan VIP |
| **Perubahan Data Kunjungan** | Edit atau hapus kunjungan |
| **Perubahan Data Pelanggan** | Edit data pelanggan tertentu |
| **Naik Kelas** | Promosi massal kelas pelanggan |

### Untuk Admin / Direktur / User — Mengajukan Permintaan

1. Lakukan aksi yang memerlukan approval (misalnya: edit kunjungan).
2. Isi formulir perubahan yang diminta.
3. Tambahkan **catatan/alasan** perubahan.
4. Klik **Ajukan**.
5. Permintaan akan tampil di panel approval dengan status **Menunggu**.
6. Pantau status permintaan di menu **Approval Request > Permintaan Saya**.

### Untuk Super Admin — Meninjau Permintaan

1. Buka menu **Approval Request**.
2. Pilih sub-menu sesuai jenis:
   - Semua Permintaan
   - Pelanggan Khusus
   - Kunjungan
   - Data Pelanggan
   - Naik Kelas
3. Baca detail permintaan dan catatan pengaju.
4. Klik **Setujui** untuk menerima atau **Tolak** untuk menolak.
5. Tambahkan **catatan keputusan** saat menolak agar pengaju memahami alasan penolakan.

---

## 15. Manajemen Pengguna (IT)

> **Akses:** IT saja

### Melihat Daftar Pengguna

1. Buka menu **Pengguna**.
2. Tampil daftar semua pengguna beserta peran dan cabang yang ditetapkan.

### Membuat Pengguna Baru

1. Klik **Tambah Pengguna**.
2. Isi formulir:
   - **Nama Lengkap**
   - **Username**
   - **Email**
   - **Password**
   - **Peran** — pilih dari: Super Admin, Admin, Direktur, User, IT
   - **Cabang** — pilih satu atau lebih cabang (wajib, kecuali peran IT)
3. Klik **Simpan**.
4. Pengguna baru dapat langsung login menggunakan kredensial yang dibuat.

### Mengedit Pengguna

1. Klik ikon **edit** pada baris pengguna.
2. Ubah data yang diperlukan (nama, email, peran, cabang).
3. Klik **Simpan**.

### Menonaktifkan / Mengaktifkan Pengguna

1. Klik ikon **nonaktifkan** pada baris pengguna.
2. Konfirmasi tindakan.
3. Pengguna yang dinonaktifkan tidak dapat login. Data tidak dihapus.
4. Untuk mengaktifkan kembali, klik ikon **aktifkan**.

### Reset Password Pengguna

1. Buka **Permintaan Reset Password** di menu IT.
2. Lihat daftar permintaan reset yang masuk.
3. Klik **Reset Password** untuk mengatur ulang password pengguna.
4. Informasikan password baru kepada pengguna yang bersangkutan.
5. Atau klik **Tolak** jika permintaan tidak valid, dan isi alasan penolakan.

---

## 16. Manajemen Cabang (IT)

> **Akses:** IT saja

### Melihat Daftar Cabang

1. Buka menu **Cabang**.
2. Tampil daftar semua cabang yang terdaftar beserta informasi detailnya.

### Menambahkan Cabang Baru

1. Klik **Tambah Cabang**.
2. Isi formulir:
   - **Kode** — kode singkat untuk prefix PID (misalnya: `JKT`, `SBY`)
   - **Nama** — nama lengkap cabang
   - **Tipe** — jenis cabang
   - **Keterangan** — deskripsi tambahan
3. Klik **Simpan**.
4. PID pelanggan di cabang ini akan menggunakan kode cabang sebagai prefix secara otomatis.

### Mengedit Cabang

1. Klik ikon **edit** pada baris cabang.
2. Ubah informasi yang diperlukan.
3. Klik **Simpan**.

### Menghapus Cabang

1. Klik ikon **hapus** pada baris cabang.
2. Konfirmasi penghapusan.

> **Perhatian:** Pastikan tidak ada pengguna atau pelanggan aktif di cabang tersebut sebelum dihapus.

---

## 17. Log Aktivitas (IT)

> **Akses:** IT saja

Menu **Log Aktivitas** mencatat seluruh tindakan yang dilakukan pengguna di sistem.

### Informasi yang Dicatat

- **Pengguna** — siapa yang melakukan tindakan
- **Aksi** — jenis tindakan (login, logout, create, update, delete, import, restore)
- **Modul** — bagian sistem yang terpengaruh (pelanggan, pengguna, autentikasi)
- **Waktu** — timestamp aksi

### Cara Menggunakan

1. Buka menu **Log Aktivitas**.
2. Gunakan filter untuk mempersempit tampilan:
   - **Pengguna**
   - **Jenis Aksi**
   - **Modul**
   - **Rentang Tanggal**
3. Klik **Tampilkan** untuk memuat hasil.

### Export Log

Klik **Export Excel** untuk mengunduh log aktivitas sebagai file Excel untuk keperluan audit.

> **Catatan:** Log aktivitas secara otomatis dibersihkan setelah 30 hari.

---

## 18. Riwayat Import & Rollback (IT)

> **Akses:** IT saja

Fitur ini memungkinkan IT untuk melihat riwayat seluruh import data dan membatalkan (rollback) import yang bermasalah.

### Melihat Riwayat Import

1. Buka menu **Riwayat Import**.
2. Tampil daftar semua sesi import, lengkap dengan:
   - Tanggal dan waktu import
   - Pengguna yang melakukan import
   - Jumlah record yang diimpor
   - Status import

### Rollback Import

1. Identifikasi batch import yang ingin dibatalkan.
2. Klik **Rollback** pada baris batch tersebut.
3. Konfirmasi tindakan.
4. Sistem akan **menghapus seluruh record** yang diimpor pada batch tersebut dan **mengembalikan data** ke kondisi sebelum import.

> **Perhatian:** Rollback bersifat permanen. Pastikan Anda telah yakin sebelum melakukan rollback.

---

## 19. Profil Pengguna

Semua pengguna dapat mengakses dan mengubah profil mereka sendiri.

### Cara Mengakses

1. Klik nama pengguna atau ikon profil di bagian atas kanan layar.
2. Pilih **Profil** dari dropdown.

### Mengubah Informasi Profil

1. Di halaman profil, klik **Edit Profil**.
2. Ubah **Nama** atau **Email**.
3. Klik **Simpan**.

### Mengubah Password

1. Di halaman profil, klik **Ubah Password**.
2. Masukkan **Password Lama**.
3. Masukkan **Password Baru**.
4. Masukkan kembali Password Baru untuk konfirmasi.
5. Klik **Simpan**.

---

## 20. Peran & Hak Akses

Tabel berikut merangkum fitur yang dapat diakses oleh masing-masing peran:

| Fitur | Super Admin | Admin | Direktur | User | IT |
|---|:---:|:---:|:---:|:---:|:---:|
| Dashboard | ✓ | ✓ | ✓ | ✓ | ✓ |
| Lihat Data Pelanggan | ✓ | ✓ | ✓ | ✓ | — |
| Input / Edit / Hapus Pelanggan | ✓ | ✓ | ✓ | — | — |
| Import Data | — | ✓ | ✓ | — | — |
| Export Data | ✓ | ✓ | ✓ | ✓ | — |
| Pelanggan Khusus | ✓ | ✓ | ✓ | — | — |
| Special Day Member | ✓ | ✓ | ✓ | ✓ | — |
| Laporan | ✓ | ✓ | ✓ | ✓ | — |
| Retensi | ✓ | ✓ | ✓ | ✓ | — |
| Cari by HP | ✓ | ✓ | ✓ | ✓ | — |
| Approval Request (Tinjau) | ✓ | — | — | — | — |
| Approval Request (Ajukan) | — | ✓ | ✓ | ✓ | — |
| Manajemen Pengguna | — | — | — | — | ✓ |
| Manajemen Cabang | — | — | — | — | ✓ |
| Log Aktivitas | — | — | — | — | ✓ |
| Riwayat Import | — | — | — | — | ✓ |
| Reset Password User | — | — | — | — | ✓ |

---

## 21. Klasifikasi Pelanggan

Sistem secara otomatis mengklasifikasikan pelanggan ke dalam 4 kelas berdasarkan frekuensi kunjungan dan total pengeluaran.

### Kelas Pelanggan

| Kelas | Deskripsi |
|---|---|
| **Prioritas** | Pelanggan paling setia dengan kunjungan sangat sering dan pengeluaran tinggi |
| **Loyal** | Pelanggan dengan kunjungan rutin dan pengeluaran signifikan |
| **Potensial** | Pelanggan dengan potensi berkembang, kunjungan atau pengeluaran cukup |
| **Umum** | Pelanggan baru atau dengan aktivitas minimal |

### Kapan Kelas Diperbarui

- Saat data kunjungan baru ditambahkan
- Saat data diimpor dari Excel
- Saat terjadi perubahan data kunjungan
- Saat rollback import

### Melihat Riwayat Perubahan Kelas

1. Buka halaman **Detail Pelanggan**.
2. Gulir ke bagian **Riwayat Kelas**.
3. Tampil log setiap perubahan kelas: dari kelas apa, ke kelas apa, kapan, dan oleh siapa.

---

## 22. Pertanyaan Umum (FAQ)

**Q: PID itu apa dan bagaimana cara kerjanya?**  
A: PID (Patient/Pelanggan ID) adalah nomor identifikasi unik yang otomatis dihasilkan sistem saat pelanggan baru didaftarkan. Format PID menggunakan kode cabang sebagai prefix diikuti nomor urut. Contoh: `JKT-0001`, `SBY-0042`.

---

**Q: Saya tidak bisa login. Apa yang harus dilakukan?**  
A: Periksa kembali username dan password. Jika sudah 5 kali gagal, tunggu beberapa menit lalu coba lagi. Jika masih tidak bisa, hubungi IT untuk reset password.

---

**Q: Apakah data pelanggan yang dihapus bisa dipulihkan?**  
A: Ya. Pelanggan yang dihapus masuk ke **Recycle Bin** dan bisa dipulihkan kapan saja. Hanya IT yang bisa melakukan penghapusan permanen.

---

**Q: Saya sudah submit approval request tapi lama tidak ada respons. Apa yang harus dilakukan?**  
A: Hubungi Super Admin yang bertanggung jawab di unit Anda untuk menginformasikan bahwa ada permintaan yang menunggu persetujuan.

---

**Q: Saat import, muncul peringatan "PID sudah ada". Apa artinya?**  
A: PID di baris tersebut sudah terdaftar di database. Sistem akan memvalidasi dan biasanya melewati baris tersebut atau memperbarui data yang ada. Pastikan data tidak duplikat sebelum import.

---

**Q: Berapa lama sesi login bertahan?**  
A: Sesi akan berakhir otomatis setelah **30 menit tidak aktif**. Anda akan diarahkan ke halaman login.

---

**Q: Mengapa beberapa tombol tidak muncul di halaman saya?**  
A: Tampilan tombol dan menu disesuaikan dengan peran (role) akun Anda. Jika membutuhkan akses ke fitur tertentu, hubungi IT.

---

**Q: Apakah saya bisa mengakses data cabang lain?**  
A: Tidak, kecuali Anda memiliki peran IT atau secara khusus ditetapkan ke beberapa cabang oleh IT. Setiap pengguna hanya dapat melihat data dari cabang yang ditetapkan untuknya.

---

**Q: Bagaimana cara update NIK pelanggan secara massal?**  
A: Gunakan menu **Update NIK (Bulk)**. Unduh template, isi kolom PID dan NIK baru, lalu upload file tersebut. Sistem akan memperbarui NIK secara massal.

---

*Dokumen ini dibuat untuk keperluan internal. Untuk pertanyaan teknis, hubungi tim IT.*
