# TODO: Tambah Filter Range Tanggal di Menu Data Pelanggan
## Status: ✅ Plan Approved - Implementation Started

**Objective:** Tambah opsi "Range Tanggal" di filter Periode menu Data Pelanggan (sama persis seperti Laporan), tanpa merusak design/flow.

### Breakdown Steps dari Approved Plan:

#### 1. ✅ [DONE] Buat TODO.md ini
#### 2. ✅ Update resources/views/pelanggan/index.blade.php
   - ✅ Tambah `<option value="range">Range Tanggal</option>` di select `type`
   - ✅ Tambah 2 kolom `rangeContainer` & `rangeContainer2` (input date)
   - ✅ Update JS `updatePeriodContainers()` untuk handle `range`
#### 3. ✅ Update app/Http/Controllers/PelangganController.php@index()
   - ✅ Tambah variabel `$tanggal_mulai`, `$tanggal_selesai`
   - ✅ Tambah kondisi subquery untuk `type === 'range'`
   - ✅ Tambah WHERE clause untuk `range` di `if(!$search)`
   - ✅ Pass variabel ke view
#### 4. Testing

   - [ ] Tambah variabel `$tanggal_mulai`, `$tanggal_selesai`
   - [ ] Tambah kondisi subquery untuk `type === 'range'`
   - [ ] Tambah WHERE clause untuk `range` di `if(!$search)`
   - [ ] Pass variabel ke view
#### 4. Testing
   - [ ] Test UI: Range Tanggal muncul/hide correctly
   - [ ] Test Filter: Data filtered by date range
   - [ ] Test Regression: perbulan/pert ahun masih work
#### 5. ✅ Completion

**Current Progress:** Step 1 Complete. Next: Edit blade file.

