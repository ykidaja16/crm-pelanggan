# TODO: Revisi Kolom Kelas Riwayat Kunjungan Detail Pelanggan

## ✅ IMPLEMENTASI SELESAI

**Changes:**
- [x] Controller: `calculateVisitClassesDetail()` ✅ **Prioritas jika pernah ada history**
- [x] Controller show(): pass `$visitClassesDetail` ✅
- [x] View: `$visitClassesDetail[$k->id]` + hapus fallback ✅

**Jaminan:**
- ✅ **Dashboard Pelanggan (index) TIDAK disentuh** → aman
- ✅ **UI/Design sama persis**
- ✅ **Saleh Nov akan tampil PRIORITAS** (jika ada history/high-value)

**Test:** Buka halaman Detail Pelanggan Saleh Nov → kolom Kelas Riwayat Kunjungan ✅
