@extends('layouts.main')

@section('title', 'Input Data Pelanggan - Medical Lab CRM')

@section('content')
@php $role = Auth::user()->role?->name; @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="text-primary mb-0 fw-semibold">
            <i class="fas fa-file-import me-2"></i>Input Data Pelanggan
        </h4>
        <a href="{{ route('pelanggan.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard Pelanggan
        </a>
    </div>

    <div class="row g-4">

        {{-- Import Card: hanya Admin & Super Admin --}}
        @if(in_array($role, ['Admin', 'Super Admin']))
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h6 class="mb-0 fw-semibold text-success">
                            <i class="fas fa-file-import me-2"></i>Import Data Kunjungan
                            <span class="badge bg-success ms-2" style="font-size:0.7rem;">Pelanggan Biasa</span>
                        </h6>
                        <span class="badge bg-warning text-dark" style="font-size:0.72rem;">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Bukan untuk import pelanggan khusus.
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    {{-- Info: import hanya untuk pelanggan biasa --}}
                    <div class="alert alert-info alert-sm py-2 px-3 mb-3 d-flex align-items-start gap-2" style="font-size:0.85rem;">
                        <i class="fas fa-info-circle mt-1 shrink-0"></i>
                        <div>
                            <strong>Perhatian:</strong> Import ini <strong>hanya untuk Pelanggan Biasa</strong>.
                            Jika Anda menggunakan file format <strong>Pelanggan Khusus</strong> (memiliki kolom <em>Kategori Khusus</em>),
                            import akan <strong>ditolak</strong>. Gunakan menu
                            <a href="{{ route('pelanggan.khusus.index') }}" class="alert-link">Pelanggan Khusus</a>
                            untuk mengimport data pelanggan khusus.
                        </div>
                    </div>
                  
                    <div class="d-flex flex-wrap align-items-end gap-3">
                        <div style="min-width:200px; flex:0 0 auto;">
                            <label class="form-label fw-medium small mb-1">Pilih Cabang <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="importCabangSelect" style="min-width:180px;">
                                <option value="">-- Pilih Cabang --</option>
                                @foreach($cabangs as $cabang)
                                    <option value="{{ $cabang->id }}">{{ $cabang->nama }} ({{ $cabang->kode }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div style="min-width:200px; flex:0 0 auto;">
                            <label class="form-label fw-medium small mb-1">Pilih File <span class="text-danger">*</span></label>
                            <input type="file" class="form-control form-control-sm" id="fileInput" accept=".xlsx,.xls,.csv" style="min-width:180px;">
                            <div class="invalid-feedback">File harus berupa Excel atau CSV</div>
                        </div>
                        <div style="flex:0 0 auto; padding-top:1.5rem;">
                            <button type="button" class="btn btn-success btn-sm" id="importBtn" onclick="startImport()">
                                <i class="fas fa-upload me-1"></i>Import
                            </button>
                        </div>
                        <div style="flex:0 0 auto; padding-top:1.5rem;">
                            <a href="{{ route('pelanggan.download-template') }}" class="btn btn-outline-primary btn-sm" id="downloadTemplateBtn">
                                <i class="fas fa-download me-1"></i>Download Template
                            </a>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Format Excel (11 kolom):</strong>
                            No | Nama Pasien | Total Kedatangan | Tanggal Kedatangan Terakhir | Total (Biaya) | No Telpon | DOB | PID | Alamat | Kota | Kelompok Pelanggan (mandiri/klinisi)
                        </small>
                    </div>

                    {{-- Progress Bar real-time --}}
                    <div id="importProgressContainer" class="mt-3 d-none">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted fw-semibold" id="importProgressLabel">Progress Import</small>
                            <small class="text-muted"><span id="importProgressText">0%</span></small>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div id="importProgressBar"
                                 class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                                 role="progressbar"
                                 style="width: 0%;"
                                 aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div>
                        </div>
                        <small class="text-muted mt-1 d-block" id="importProgressDetail"></small>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Card: Tambah Pelanggan Manual --}}
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-semibold text-primary">
                        <i class="fas fa-user-plus me-2"></i>Tambah Pelanggan Manual
                    </h6>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3 small">
                        Tambahkan data pelanggan baru secara manual satu per satu beserta data kunjungan pertamanya.
                    </p>
                    <a href="{{ route('pelanggan.create') }}" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus me-2"></i> Tambah Pelanggan
                    </a>
                </div>
            </div>
        </div>

    </div>

@endsection

@section('scripts')
<script>
// =============================================
// IMPORT - Real-time Progress Bar
// =============================================
function startImport() {
    const fileInput         = document.getElementById('fileInput');
    const importBtn         = document.getElementById('importBtn');
    const progressContainer = document.getElementById('importProgressContainer');
    const progressBar       = document.getElementById('importProgressBar');
    const progressText      = document.getElementById('importProgressText');
    const progressLabel     = document.getElementById('importProgressLabel');
    const progressDetail    = document.getElementById('importProgressDetail');

    if (!fileInput || !fileInput.files[0]) {
        alert('Pilih file terlebih dahulu.');
        return;
    }

    const importCabangSelect = document.getElementById('importCabangSelect');
    if (!importCabangSelect || !importCabangSelect.value) {
        alert('Pilih cabang terlebih dahulu sebelum import.');
        if (importCabangSelect) importCabangSelect.focus();
        return;
    }

    const file     = fileInput.files[0];
    const validExt = ['.xlsx', '.xls', '.csv'];
    const isValid  = validExt.some(ext => file.name.toLowerCase().endsWith(ext));
    if (!isValid) {
        fileInput.classList.add('is-invalid');
        alert('File harus berupa Excel (.xlsx, .xls) atau CSV (.csv)');
        return;
    }
    fileInput.classList.remove('is-invalid');

    importBtn.disabled = true;
    importBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" style="width: 16px; height: 16px; border-width: 2px;"></span><span>Mengimpor...</span>';
    progressContainer.classList.remove('d-none');
    progressBar.style.width = '0%';
    progressBar.setAttribute('aria-valuenow', 0);
    progressText.textContent = '0%';
    progressLabel.textContent = 'Progress Import';
    progressDetail.textContent = '';

    // Polling progress setiap 800ms
    let pollInterval = setInterval(async function() {
        try {
            const resp = await fetch('{{ route("pelanggan.import.progress") }}', {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            if (!resp.ok) return;
            const data = await resp.json();
            const pct  = Math.max(0, Math.min(99, parseInt(data.percent || 0)));
            progressBar.style.width = pct + '%';
            progressBar.setAttribute('aria-valuenow', pct);
            progressText.textContent = pct + '%';
        } catch(e) { /* abaikan */ }
    }, 800);

    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('file', file);
    formData.append('import_cabang_id', importCabangSelect.value);

    fetch('{{ route("pelanggan.import") }}', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(async function(response) {
        clearInterval(pollInterval);
        progressBar.style.width = '100%';
        progressBar.setAttribute('aria-valuenow', 100);
        progressText.textContent = '100%';
        progressLabel.textContent = 'Import Selesai';
        progressDetail.textContent = '';

        // Reset file input
        fileInput.value = '';
        importBtn.disabled = false;
        importBtn.innerHTML = '<i class="fas fa-upload me-1"></i>Import';

        let data;
        try { data = await response.json(); } catch(e) {
            showImportAlert('error', 'Terjadi kesalahan tidak terduga. Silakan coba lagi.');
            return;
        }

        if (data.success) {
            showImportAlert('success', data.message || 'Import berhasil!', data.errors || []);
        } else {
            showImportAlert('error', data.message || 'Import gagal.', data.errors || []);
        }
        setTimeout(() => { progressContainer.classList.add('d-none'); }, 3000);
    })
    .catch(function() {
        clearInterval(pollInterval);
        importBtn.disabled = false;
        importBtn.innerHTML = '<i class="fas fa-upload me-1"></i>Import';
        progressContainer.classList.add('d-none');
        showImportAlert('error', 'Koneksi terputus atau server tidak merespons. Silakan coba lagi.');
    });
}

function showImportAlert(type, message, errors) {
    document.querySelectorAll('.import-alert').forEach(el => el.remove());
    const cls  = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    let errHtml = '';
    if (errors && errors.length > 0) {
        errHtml = '<ul class="mb-0 mt-2">' + errors.map(e => '<li>' + e + '</li>').join('') + '</ul>';
    }
    const div = document.createElement('div');
    div.className = 'alert ' + cls + ' alert-dismissible fade show shadow-sm import-alert';
    div.setAttribute('role', 'alert');
    div.innerHTML = '<i class="fas ' + icon + ' me-2"></i>' + message + errHtml +
        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" onclick="window.location.reload()"></button>';
    const row = document.querySelector('.row.g-4');
    if (row) row.insertBefore(div, row.firstChild);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>
@endsection
