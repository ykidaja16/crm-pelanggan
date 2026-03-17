@extends('layouts.main')

@section('title', 'Pelanggan Khusus - Medical Lab CRM')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="text-primary mb-0 fw-semibold">Pelanggan Khusus</h4>
</div>

<div class="row g-4">
    <!-- Form Manual -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-2 border-bottom">
                <ul class="nav nav-tabs card-header-tabs" id="khususTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold" id="tab-baru-btn" data-bs-toggle="tab"
                            data-bs-target="#tab-baru" type="button" role="tab">
                            <i class="fas fa-user-plus me-1"></i>Pelanggan Baru
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold" id="tab-lama-btn" data-bs-toggle="tab"
                            data-bs-target="#tab-lama" type="button" role="tab">
                            <i class="fas fa-user-clock me-1"></i>Pelanggan Lama
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body tab-content" id="khususTabContent">

                {{-- ── TAB: PELANGGAN BARU ── --}}
                <div class="tab-pane fade show active" id="tab-baru" role="tabpanel">
                    <form method="POST" action="{{ route('approval.special.store') }}" id="formBaru" class="row g-3">
                        @csrf
                        <input type="hidden" name="mode" value="new">

                        {{-- Identitas Pelanggan --}}
                        <div class="col-12">
                            <h6 class="fw-semibold text-secondary border-bottom pb-2 mb-1">
                                <i class="fas fa-user me-1"></i> Data Pelanggan
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">PID <span class="text-danger">*</span></label>
                            <input type="text" name="pid" class="form-control" required placeholder="Contoh: LXB00000001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cabang <span class="text-danger">*</span></label>
                            <select name="cabang_id" id="cabang_id_khusus" class="form-select" required
                                onchange="updateSuperadminDropdown(this.value)">
                                <option value="">Pilih Cabang</option>
                                @foreach($cabangs as $cabang)
                                    <option value="{{ $cabang->id }}">{{ $cabang->nama }} ({{ $cabang->kode }})</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Superadmin Tujuan (dinamis berdasarkan cabang) --}}
                        <div class="col-md-6" id="superadmin_section" style="display:none;">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-user-shield me-1 text-primary"></i> Tujuan Kepala Cabang <span class="text-danger">*</span>
                            </label>
                            <div id="superadmin_auto" style="display:none;">
                                <input type="hidden" name="assigned_to" id="assigned_to_hidden">
                                <div class="form-control bg-light text-muted" style="cursor:default;" id="superadmin_auto_label">
                                    <i class="fas fa-user-check me-2 text-success"></i>
                                    <span id="superadmin_auto_name"></span>
                                    <span class="badge bg-success ms-2 small">Auto-assign</span>
                                </div>
                                <div class="form-text text-muted">Pengajuan akan otomatis dikirim ke kacab ini.</div>
                            </div>
                            <div id="superadmin_dropdown" style="display:none;">
                                <select name="assigned_to" id="assigned_to_select" class="form-select" required>
                                    <option value="">-- Pilih Kepala Cabang --</option>
                                </select>
                                <div class="form-text text-muted">Pilih kepala cabang yang akan menerima pengajuan ini.</div>
                            </div>
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
                            <h6 class="fw-semibold text-secondary border-bottom pb-2 mb-1">
                                <i class="fas fa-calendar-check me-1"></i> Data Kunjungan
                            </h6>
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
                                <input type="text" name="biaya" class="form-control biaya-khusus-input" required
                                    placeholder="0" inputmode="numeric">
                            </div>
                            <small class="text-muted">Masukkan angka tanpa titik/koma</small>
                        </div>

                        {{-- Kategori Khusus --}}
                        <div class="col-12 mt-2">
                            <h6 class="fw-semibold text-secondary border-bottom pb-2 mb-1">
                                <i class="fas fa-star me-1"></i> Kategori Khusus
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Kategori Khusus <span class="text-danger">*</span></label>
                            <select name="kategori_khusus" id="kategori_khusus_select" class="form-select" required
                                onchange="toggleKategoriLainnya(this)">
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
                            <input type="text" name="kategori_khusus_lainnya" id="kategori_khusus_lainnya"
                                class="form-control" placeholder="Sebutkan kategori">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Note Pengajuan (Alasan) <span class="text-danger">*</span></label>
                            <textarea name="request_note" class="form-control" rows="3" required
                                placeholder="Alasan pengajuan pelanggan khusus..."></textarea>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Ajukan ke Kacab
                            </button>
                        </div>
                    </form>
                </div>{{-- end #tab-baru --}}

                {{-- ── TAB: PELANGGAN LAMA ── --}}
                <div class="tab-pane fade" id="tab-lama" role="tabpanel">
                    {{-- Pencarian --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Cari Pelanggan Khusus <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="text" id="searchPidLama" class="form-control"
                                placeholder="Ketik PID atau nama pelanggan khusus..."
                                onkeydown="if(event.key==='Enter'){event.preventDefault();searchPelangganKhusus();}">
                            <button type="button" class="btn btn-outline-primary" onclick="searchPelangganKhusus()">
                                <i class="fas fa-search me-1"></i>Cari
                            </button>
                        </div>
                        <div class="form-text text-muted small">
                            Cari berdasarkan PID atau nama pelanggan khusus yang sudah terdaftar.
                        </div>
                    </div>

                    {{-- Hasil pencarian: ditemukan --}}
                    <div id="searchResultLama" class="d-none mb-3">
                        <div class="alert alert-info border-0 p-3 mb-0">
                            <div class="d-flex align-items-start gap-3">
                                <i class="fas fa-user-circle fa-2x text-info mt-1"></i>
        <div class="grow">
                                    <div class="fw-semibold" id="resultNama">-</div>
                                    <div class="small text-muted">
                                        PID: <span id="resultPid">-</span> |
                                        Cabang: <span id="resultCabang">-</span>
                                    </div>
                                    <div class="small text-muted">
                                        Kategori: <span id="resultKategori">-</span> |
                                        Total Kunjungan: <span id="resultKunjungan">-</span>x
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearSearchLama()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Hasil pencarian: tidak ditemukan --}}
                    <div id="searchNotFound" class="d-none mb-3">
                        <div class="alert alert-warning border-0 p-3 mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Pelanggan khusus tidak ditemukan. Pastikan PID atau nama sudah benar.
                        </div>
                    </div>

                    {{-- Form tambah kunjungan pelanggan lama --}}
                    <form method="POST" action="{{ route('approval.special.store') }}" id="formLama" class="row g-3">
                        @csrf
                        <input type="hidden" name="mode" value="existing">
                        <input type="hidden" name="existing_pelanggan_id" id="existingPelangganId">

                        <div class="col-md-6">
                            <label class="form-label">Kelompok Pelanggan <span class="text-danger">*</span></label>
                            <select name="kelompok_pelanggan" class="form-select" required>
                                <option value="">Pilih Kelompok</option>
                                <option value="mandiri">Mandiri</option>
                                <option value="klinisi">Klinisi</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Kunjungan <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_kunjungan" class="form-control"
                                value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Biaya Kunjungan <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="biaya" class="form-control biaya-lama-input"
                                    placeholder="0" inputmode="numeric" required>
                            </div>
                            <small class="text-muted">Masukkan angka tanpa titik/koma</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alasan Pengajuan <span class="text-danger">*</span></label>
                            <textarea name="request_note" class="form-control" rows="3" required maxlength="500"
                                placeholder="Jelaskan alasan penambahan kunjungan pelanggan khusus ini..."></textarea>
                            <div class="form-text text-muted small">Maks. 500 karakter.</div>
                        </div>
                        <div class="col-12">
                            <div class="alert alert-warning border-0 p-2 mb-0 small">
                                <i class="fas fa-info-circle me-1"></i>
                                Pengajuan ini akan dikirim ke <strong>Kepala Cabang</strong> untuk disetujui.
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-warning" id="submitLamaBtn" disabled>
                                <i class="fas fa-paper-plane me-2"></i>Kirim Pengajuan Kunjungan
                            </button>
                        </div>
                    </form>
                </div>{{-- end #tab-lama --}}

            </div>{{-- end .tab-content --}}
        </div>{{-- end .card --}}
    </div>{{-- end .col-lg-7 --}}

    <!-- Import Excel -->
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-semibold text-success">
                    <i class="fas fa-file-import me-2"></i>Import Excel Pelanggan Khusus
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('approval.special.import.store') }}"
                    enctype="multipart/form-data" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <label class="form-label fw-medium">Pilih Cabang <span class="text-danger">*</span></label>
                        <select name="import_cabang_id"
                            class="form-select @error('import_cabang_id') is-invalid @enderror" required>
                            <option value="">-- Pilih Cabang --</option>
                            @foreach($cabangs as $cabang)
                                <option value="{{ $cabang->id }}"
                                    {{ old('import_cabang_id') == $cabang->id ? 'selected' : '' }}>
                                    {{ $cabang->nama }} ({{ $cabang->kode }})
                                </option>
                            @endforeach
                        </select>
                        @error('import_cabang_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Semua PID dalam file harus sesuai dengan kode cabang yang dipilih.
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">File Excel/CSV <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        <div class="mt-2 p-2 bg-light rounded border small text-muted">
                            <strong><i class="fas fa-info-circle me-1 text-info"></i>Format Kolom (12 kolom):</strong><br>
                            No | Nama Pasien | Total Kedatangan | Tanggal Kedatangan | Total Biaya |
                            No Telpon | DOB | PID | Alamat | Kota | Kelompok Pelanggan |
                            <strong class="text-success">Kategori Khusus</strong>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Note Pengajuan Import (Alasan) <span class="text-danger">*</span></label>
                        <textarea name="request_note" class="form-control" rows="3" required
                            placeholder="Alasan pengajuan import pelanggan khusus..."></textarea>
                    </div>
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <a href="{{ route('pelanggan.download-template-khusus') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-download me-1"></i>Download Template
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-upload me-2"></i>Ajukan Import
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Info Card -->
        <div class="card shadow-sm border-start border-warning">
            <div class="card-body py-3">
                <h6 class="fw-semibold text-warning mb-2">
                    <i class="fas fa-exclamation-triangle me-2"></i>Catatan Penting
                </h6>
                <ul class="mb-0 small text-muted ps-3">
                    <li>Pengajuan akan dikirim ke Kepala Cabang untuk disetujui.</li>
                    <li>Pelanggan khusus otomatis mendapat kelas <strong>Prioritas</strong>.</li>
                    <li>Kategori "Lainnya" wajib disertai keterangan.</li>
                    <li>Jika PID sudah terdaftar sebagai pelanggan khusus, akan dianggap sebagai penambahan kunjungan.</li>
                    <li>Format import sama dengan template biasa + kolom Kategori Khusus di akhir.</li>
                </ul>
            </div>
        </div>
    </div>{{-- end .col-lg-5 --}}
</div>{{-- end .row --}}

@section('scripts')
<script>
const superadminsByCabang = @json($superadminsByCabang ?? []);
const searchPelangganUrl  = "{{ route('api.pelanggan.search') }}";

// ── Superadmin dropdown (Pelanggan Baru) ──────────────────────────────────────
function updateSuperadminDropdown(cabangId) {
    const section    = document.getElementById('superadmin_section');
    const autoDiv    = document.getElementById('superadmin_auto');
    const dropdownDiv= document.getElementById('superadmin_dropdown');
    const hiddenInput= document.getElementById('assigned_to_hidden');
    const selectEl   = document.getElementById('assigned_to_select');
    const autoName   = document.getElementById('superadmin_auto_name');

    if (!cabangId || !superadminsByCabang[cabangId] || superadminsByCabang[cabangId].length === 0) {
        section.style.display = 'none';
        autoDiv.style.display = 'none';
        dropdownDiv.style.display = 'none';
        if (hiddenInput) hiddenInput.value = '';
        if (selectEl) { selectEl.required = false; selectEl.value = ''; }
        return;
    }

    const admins = superadminsByCabang[cabangId];
    section.style.display = 'block';

    if (admins.length === 1) {
        autoDiv.style.display = 'block';
        dropdownDiv.style.display = 'none';
        hiddenInput.value = admins[0].id;
        autoName.textContent = admins[0].name;
        if (selectEl) { selectEl.required = false; selectEl.value = ''; }
    } else {
        autoDiv.style.display = 'none';
        dropdownDiv.style.display = 'block';
        if (hiddenInput) hiddenInput.value = '';
        selectEl.required = true;
        selectEl.innerHTML = '<option value="">-- Pilih Superadmin --</option>';
        admins.forEach(function(admin) {
            const opt = document.createElement('option');
            opt.value = admin.id;
            opt.textContent = admin.name;
            selectEl.appendChild(opt);
        });
    }
}

// ── Kategori Lainnya toggle ───────────────────────────────────────────────────
function toggleKategoriLainnya(select) {
    const wrapper = document.getElementById('kategori_lainnya_wrapper');
    const input   = document.getElementById('kategori_khusus_lainnya');
    if (select.value === 'Lainnya') {
        wrapper.style.display = 'block';
        input.required = true;
    } else {
        wrapper.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}

// ── Format biaya inputs ───────────────────────────────────────────────────────
function attachBiayaFormat(selector) {
    document.querySelectorAll(selector).forEach(function(input) {
        input.addEventListener('input', function() {
            let val = this.value.replace(/\D/g, '');
            val = val.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            this.value = val;
        });
    });
}
attachBiayaFormat('.biaya-khusus-input');
attachBiayaFormat('.biaya-lama-input');

// ── Submit Pelanggan Baru: merge kategori lainnya + clean biaya ───────────────
document.getElementById('formBaru').addEventListener('submit', function(e) {
    const select      = document.getElementById('kategori_khusus_select');
    const lainnyaInput= document.getElementById('kategori_khusus_lainnya');
    if (select.value === 'Lainnya') {
        if (!lainnyaInput.value.trim()) {
            e.preventDefault();
            alert('Harap isi keterangan untuk kategori Lainnya.');
            return false;
        }
        select.value = lainnyaInput.value.trim();
    }
    document.querySelectorAll('.biaya-khusus-input').forEach(function(inp) {
        inp.value = inp.value.replace(/\./g, '');
    });
});

// ── Submit Pelanggan Lama: clean biaya ────────────────────────────────────────
document.getElementById('formLama').addEventListener('submit', function(e) {
    const pid = document.getElementById('existingPelangganId').value;
    if (!pid) {
        e.preventDefault();
        alert('Harap cari dan pilih pelanggan khusus terlebih dahulu.');
        return false;
    }
    document.querySelectorAll('.biaya-lama-input').forEach(function(inp) {
        inp.value = inp.value.replace(/\./g, '');
    });
});

// ── Pencarian Pelanggan Khusus (AJAX) ─────────────────────────────────────────
function searchPelangganKhusus() {
    const query = document.getElementById('searchPidLama').value.trim();
    if (!query) {
        alert('Masukkan PID atau nama pelanggan terlebih dahulu.');
        return;
    }

    const resultDiv   = document.getElementById('searchResultLama');
    const notFoundDiv = document.getElementById('searchNotFound');
    const submitBtn   = document.getElementById('submitLamaBtn');

    resultDiv.classList.add('d-none');
    notFoundDiv.classList.add('d-none');
    submitBtn.disabled = true;
    document.getElementById('existingPelangganId').value = '';

    fetch(searchPelangganUrl + '?pid=' + encodeURIComponent(query) + '&khusus=1', {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data && data.id && data.is_pelanggan_khusus) {
            document.getElementById('existingPelangganId').value = data.id;
            document.getElementById('resultNama').textContent     = data.nama || '-';
            document.getElementById('resultPid').textContent      = data.pid  || '-';
            document.getElementById('resultCabang').textContent   = data.cabang_nama || '-';
            document.getElementById('resultKategori').textContent = data.kategori_khusus || '-';
            document.getElementById('resultKunjungan').textContent= data.total_kedatangan || '0';
            resultDiv.classList.remove('d-none');
            submitBtn.disabled = false;
        } else {
            notFoundDiv.classList.remove('d-none');
        }
    })
    .catch(function() {
        notFoundDiv.classList.remove('d-none');
    });
}

function clearSearchLama() {
    document.getElementById('searchPidLama').value = '';
    document.getElementById('existingPelangganId').value = '';
    document.getElementById('searchResultLama').classList.add('d-none');
    document.getElementById('searchNotFound').classList.add('d-none');
    document.getElementById('submitLamaBtn').disabled = true;
}
</script>
@endsection
@endsection
