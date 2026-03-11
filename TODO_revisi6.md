# TODO Revisi 6 - CRM Pelanggan

## Revisi 1: Validasi Hak Akses Cabang
- [ ] `app/Http/Controllers/PelangganController.php` - filter cabangs di create(), store() validasi, khusus() filter, index() pass userCabangs
- [ ] `app/Http/Controllers/PelangganImportExportController.php` - terima cabang_id, validasi hak akses + PID prefix
- [ ] `app/Http/Controllers/ApprovalRequestController.php` - validasi cabang akses di storeSpecialCustomerRequest() & storeSpecialCustomerImportRequest()
- [ ] `resources/views/pelanggan/index.blade.php` - tambah dropdown cabang sebelum tombol Import
- [ ] `resources/views/pelanggan/khusus.blade.php` - tambah dropdown cabang di section Import Excel

## Revisi 2: Pagination Special Day
- [ ] `resources/views/special-day/index.blade.php` - tambah Bootstrap 5 pagination links

## Revisi 3: Akses Cabang Checkboxes
- [ ] `resources/views/users/edit.blade.php` - redesign checkboxes lebih rapi
- [ ] `resources/views/users/create.blade.php` - redesign checkboxes lebih rapi

## Revisi 4: Redirect setelah Ajukan Perubahan
- [ ] `app/Http/Controllers/ApprovalRequestController.php` - storePelangganEditRequest() redirect ke pelanggan.index

## Revisi 5: Menu Profil
- [ ] `app/Http/Controllers/ProfileController.php` - baru: edit() dan update()
- [ ] `resources/views/profile/edit.blade.php` - baru: form ubah username, email, password (dengan konfirmasi password lama)
- [ ] `routes/web.php` - tambah route /profile
- [ ] `resources/views/layouts/main.blade.php` - tambah dropdown profil di navbar
