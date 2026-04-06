# TODO Revisi CRM Pelanggan

## Progress
- [x] Analisis files & buat plan  
- [x] Revisi 1: Fix Total Biaya range tanggal → PelangganController.php biayaSubquery <= tanggal_selesai (kumulatif)
- [x] Revisi 2: Kolom Kelas Riwayat sudah benar (pelanggan/show.blade.php gunakan $visitClasses)
- [x] Revisi 3: Button Approval sudah berubah Approve/Reject (index.blade.php & kunjungan.blade.php JS OK)
- [x] Verifikasi semua popup approval punya JS updateApprovalBtn()

## Detail Steps
1. **Revisi 1** app/Http/Controllers/PelangganController.php
   - type='range': biayaSubquery SUM <= $tanggal_selesai (kumulatif)
   
2. **Revisi 2** resources/views/pelanggan/show.blade.php
   - Kolom kelas gunakan $visitClasses[$kunjungan->id]
   
3. **Revisi 3** approval-requests/*.blade.php (5 files)
   - JS onchange radio → button text "Approve"/"Reject"

## Next Steps
- Test manual:
  * Filter range 01-11-2021 → Saleh Total Biaya 5.350.000 (Mei+Nov)
  * Dina 01-07 → 400.000 (Mei+Juli), 01-11 → 3.400.000  
  * Detail Saleh Nov → Kelas Prioritas (cek history)
  * Semua popup approval → radio Approve/Reject → button text berubah
