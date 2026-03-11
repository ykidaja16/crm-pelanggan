# TODO Revisi 4

## Tasks

- [ ] 1. Fix filter form buttons di `pelanggan/index.blade.php`
  - Hapus conditional `@if` pada tombol Reset Filter → selalu tampil
  - Ubah padding `px-4` → `px-3` pada kedua tombol

- [ ] 2. Fix pagination Riwayat Pengajuan Perubahan di `pelanggan/show.blade.php`
  - Ubah kondisi dari `@if($approvalHistories->hasPages())` → `@if($approvalHistories->hasPages() || $approvalHistories->total() > 0)`
  - Tambah `->appends(request()->query())` pada pagination links

## Notes
- Sidebar approval submenus sudah benar ✅
- Ketiga view approval sudah ada tombol Informasi ✅
