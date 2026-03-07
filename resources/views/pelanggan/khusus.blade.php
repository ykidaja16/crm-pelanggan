@extends('layouts.main')

@section('title', 'Pelanggan Khusus - Medical Lab CRM')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="text-primary mb-0 fw-semibold">Pelanggan Khusus</h4>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-4">
    <!-- Form Manual -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-semibold text-primary"><i class="fas fa-user-plus me-2"></i>Tambah Manual Pelanggan Khusus</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('approval.special.store') }}" class="row g-3">
                    @csrf

                    {{-- Identitas Pelanggan --}}
                    <div class="col-12">
                        <h6 class="fw-semibold text-secondary border-bottom pb-2 mb-1"><i class="fas fa-user me-1"></i> Data Pelanggan</h6>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">PID <span class="text-danger">*</span></label>
                        <input type="text" name="pid" class="form-control" required placeholder="Contoh: LXB00000001">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Cabang <span class="text-danger">*</span></label>
                        <select name="cabang_id" class="form-select" required>
                            <option value="">Pilih Cabang</option>
                            @foreach($cabangs as $cabang)
                                <option value="{{ $cabang->id }}">{{ $cabang->nama }} ({{ $cabang->kode }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nama <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" required placeholder="Nama lengkap">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">No Telp</label>
                        <input type="text" name="no_telp" class="form-control" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Lahir (DOB)</label>
                        <input type="date" name="dob" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kota</label>
                        <input type="text" name="kota" class="form-control" placeholder="Kota domisili">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="2" placeholder="Alamat lengkap"></textarea>
                    </div>

                    {{-- Data Kunjungan --}}
                    <div class="col-12 mt-2">
                        <h6 class="fw-semibold text-secondary border-bottom pb-2 mb-1"><i class="fas fa-calendar-check me-1"></i> Data Kunjungan</h6>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Kelompok Pelanggan <span class="text-danger">*</span></label>
                        <select name="kelompok_pelanggan" class="form-select" required>
                            <option value="mandiri">Mandiri</option>
                            <option value="klinisi">Klinisi</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Kunjungan <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_kunjungan" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Total Biaya <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" name="biaya" class="form-control biaya-khusus-input" required placeholder="0" inputmode="numeric">
                        </div>
                        <small class="text-muted">Masukkan angka tanpa titik/koma</small>
                    </div>

                    {{-- Kategori Khusus --}}
                    <div class="col-12 mt-2">
                        <h6 class="fw-semibold text-secondary border-bottom pb-2 mb-1"><i class="fas fa-star me-1"></i> Kategori Khusus</h6>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Kategori Khusus <span class="text-danger">*</span></label>
                        <select name="kategori_khusus" id="kategori_khusus_select" class="form-select" required onchange="toggleKategoriLainnya(this)">
                            <option value="">Pilih kategori</option>
                            <option value="Kepala Dinas">Kepala Dinas</option>
                            <option value="PIC Perusahaan">PIC Perusahaan</option>
                            <option value="Tokoh Masyarakat">Tokoh Masyarakat / Public Figure</option>
                            <option value="Dokter">Dokter</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="col-md-6" id="kategori_lainnya_wrapper" style="display:none;">
                        <label class="form-label">Keterangan Kategori Lainnya <span class="text-danger">*</span></label>
                        <input type="text" name="kategori_khusus_lainnya" id="kategori_khusus_lainnya" class="form-control" placeholder="Sebutkan kategori">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Note Pengajuan (Alasan) <span class="text-danger">*</span></label>
                        <textarea name="request_note" class="form-control" rows="3" required placeholder="Alasan pengajuan pelanggan khusus..."></textarea>
                    </div>
                    <div class="col-12 text-end">
                        <button class="btn btn-primary"><i class="fas fa-paper-plane me-2"></i>Ajukan ke Superadmin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Import Excel -->
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-semibold text-success"><i class="fas fa-file-import me-2"></i>Import Excel Pelanggan Khusus</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('approval.special.import.store') }}" enctype="multipart/form-data" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <label class="form-label">File Excel/CSV <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        <div class="mt-2 p-2 bg-light rounded border small text-muted">
                            <strong><i class="fas fa-info-circle me-1 text-info"></i>Format Kolom (12 kolom):</strong><br>
                            No | Nama Pasien | Total Kedatangan | Tanggal Kedatangan | Total Biaya | No Telpon | DOB | PID | Alamat | Kota | Kelompok Pelanggan | <strong class="text-success">Kategori Khusus</strong>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Note Pengajuan Import (Alasan) <span class="text-danger">*</span></label>
                        <textarea name="request_note" class="form-control" rows="3" required placeholder="Alasan pengajuan import pelanggan khusus..."></textarea>
                    </div>
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <a href="{{ route('pelanggan.download-template-khusus') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-download me-1"></i>Download Template
                        </a>
                        <button class="btn btn-success"><i class="fas fa-upload me-2"></i>Ajukan Import</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Info Card -->
        <div class="card shadow-sm border-0 border-start border-4 border-warning">
            <div class="card-body py-3">
                <h6 class="fw-semibold text-warning mb-2"><i class="fas fa-exclamation-triangle me-2"></i>Catatan Penting</h6>
                <ul class="mb-0 small text-muted ps-3">
                    <li>Pengajuan akan dikirim ke Superadmin untuk disetujui.</li>
                    <li>Pelanggan khusus otomatis mendapat kelas <strong>Prioritas</strong>.</li>
                    <li>Kategori "Lainnya" wajib disertai keterangan.</li>
                    <li>Format import sama dengan template biasa + kolom Kategori Khusus di akhir.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function toggleKategoriLainnya(select) {
    const wrapper = document.getElementById('kategori_lainnya_wrapper');
    const input = document.getElementById('kategori_khusus_lainnya');
    if (select.value === 'Lainnya') {
        wrapper.style.display = 'block';
        input.required = true;
    } else {
        wrapper.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}

// Format biaya input
document.querySelectorAll('.biaya-khusus-input').forEach(function(input) {
    input.addEventListener('input', function() {
        let val = this.value.replace(/\D/g, '');
        val = val.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        this.value = val;
    });
});

// Before submit: merge kategori lainnya into kategori_khusus
document.querySelector('form[action="{{ route('approval.special.store') }}"]').addEventListener('submit', function(e) {
    const select = document.getElementById('kategori_khusus_select');
    const lainnyaInput = document.getElementById('kategori_khusus_lainnya');
    if (select.value === 'Lainnya') {
        if (!lainnyaInput.value.trim()) {
            e.preventDefault();
            alert('Harap isi keterangan untuk kategori Lainnya.');
            return false;
        }
        // Set select value to the custom text so it gets submitted
        select.value = lainnyaInput.value.trim();
    }
    // Clean biaya: remove dots before submit
    document.querySelectorAll('.biaya-khusus-input').forEach(function(inp) {
        inp.value = inp.value.replace(/\./g, '');
    });
});
</script>
@endsection
