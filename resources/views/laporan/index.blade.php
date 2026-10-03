@extends('layouts.main')

@section('title', 'Laporan Pelanggan - Medical Lab CRM')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="text-primary mb-0 fw-semibold">Laporan Pelanggan</h4>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="mb-0 fw-semibold text-info">
                <i class="fas fa-filter me-2"></i>Filter Laporan
            </h6>
        </div>
        <div class="card-body">
            <form id="filterForm" class="row g-3">
                @csrf

                <!-- Tipe Pelanggan -->
                <div class="col-md-3">
                    <label class="form-label fw-medium small">Tipe Pelanggan</label>
                    <select name="tipe_pelanggan" class="form-select">
                        <option value="">Semua Tipe</option>
                        <option value="biasa">Pelanggan Biasa</option>
                        <option value="khusus">Pelanggan Khusus</option>
                    </select>
                </div>

                <!-- Periode -->
                <div class="col-md-3">
                    <label class="form-label fw-medium small">Periode</label>
                    <select name="type" id="typeSelect" class="form-select">
                        <option value="semua">Semua Data</option>
                        <option value="perbulan">Per Bulan</option>
                        <option value="pertahun">Per Tahun</option>
                        <option value="range">Range Tanggal</option>
                    </select>
                </div>

                <!-- Bulan -->
                <div class="col-md-3" id="bulanContainer" style="display:none;">
                    <label class="form-label fw-medium small">Bulan</label>
                    <select name="bulan" class="form-select">
                        @for($i = 1; $i <= 12; $i++)
                            <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}"
                                {{ date('m') == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                {{ DateTime::createFromFormat('!m', $i)->format('F') }}
                            </option>
                        @endfor
                    </select>
                </div>

                <!-- Tahun -->
                <div class="col-md-3" id="tahunContainer" style="display:none;">
                    <label class="form-label fw-medium small">Tahun</label>
                    <select name="tahun" class="form-select">
                        @for($i = date('Y'); $i >= date('Y') - 8; $i--)
                            <option value="{{ $i }}" {{ date('Y') == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>

                <!-- Range Tanggal -->
                <div class="col-md-3" id="rangeContainer" style="display:none;">
                    <label class="form-label fw-medium small">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" class="form-control">
                </div>
                <div class="col-md-3" id="rangeContainer2" style="display:none;">
                    <label class="form-label fw-medium small">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" class="form-control">
                </div>

                <!-- Cabang -->
                <div class="col-md-3">
                    <label class="form-label fw-medium small">Cabang</label>
                    <select name="cabang_id" class="form-select">
                        @if($isMultiCabang ?? true)
                            <option value="">Semua Cabang</option>
                        @endif
                        @foreach($cabangs as $cabang)
                            <option value="{{ $cabang->id }}" {{ (request('cabang_id', $isMultiCabang ?? true ? '' : $cabang->id)) == $cabang->id ? 'selected' : '' }}>{{ $cabang->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Kelas -->
                <div class="col-md-3">
                    <label class="form-label fw-medium small">Kelas</label>
                    <select name="kelas" class="form-select">
                        <option value="">Semua Kelas</option>
                        @foreach($kelasList as $k)
                            <option value="{{ $k }}">{{ $k }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Range Omset -->
                <div class="col-md-3">
                    <label class="form-label fw-medium small">Range Omset</label>
                    <select name="omset_range" class="form-select">
                        <option value="">Semua Omset</option>
                        <option value="0">0 - &lt; 1 Juta</option>
                        <option value="1">1 Juta - &lt; 4 Juta</option>
                        <option value="2">4 Juta - Lebih</option>
                    </select>
                </div>

                <!-- Range Kedatangan -->
                <div class="col-md-3">
                    <label class="form-label fw-medium small">Jumlah Kunjungan</label>
                    <select name="kedatangan_range" class="form-select">
                        <option value="">Semua Kunjungan</option>
                        <option value="1">1 Kali</option>
                        
                        <option value="2">&gt; 1 Kali</option>
                    </select>
                </div>

                <!-- Sorting -->
                <div class="col-md-3">
                    <label class="form-label fw-medium small">Urutkan Berdasarkan</label>
                    <select name="sort" class="form-select">
                        <option value="nama">Nama</option>
                        <option value="total_biaya">Total Biaya</option>
                        <option value="total_kedatangan">Jumlah Kunjungan</option>
                        <option value="tgl_kunjungan_terakhir">Tanggal Kunjungan Terakhir</option>
                        <option value="class">Kelas</option>
                    </select>
                </div>

                <!-- Arah -->
                <div class="col-md-3">
                    <label class="form-label fw-medium small">Arah</label>
                    <select name="direction" class="form-select">
                        <option value="asc">Ascending (A-Z)</option>
                        <option value="desc">Descending (Z-A)</option>
                    </select>
                </div>

                <!-- Section Search By Pemeriksaan -->
                <div class="col-12 my-3">
                    <div class="card border border-info border-opacity-50 shadow-sm" style="background: linear-gradient(135deg, rgba(13, 202, 240, 0.08) 0%, rgba(255, 255, 255, 0.9) 100%); border-left: 5px solid #0dcaf0 !important; border-radius: 8px;">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="checkSearchPemeriksaan" name="search_by_pemeriksaan" value="1" onchange="togglePemeriksaanFilter(this.checked)" style="cursor: pointer; width: 3em; height: 1.5em;">
                                    </div>
                                    <div>
                                        <label class="form-check-label fw-bold text-dark fs-6 mb-0 d-flex align-items-center flex-wrap gap-2" for="checkSearchPemeriksaan" style="cursor: pointer;">
                                            <span class="d-inline-flex align-items-center justify-content-center bg-info text-white rounded-circle" style="width: 28px; height: 28px; font-size: 13px;">
                                                <i class="fas fa-stethoscope"></i>
                                            </span>
                                            <span>Search By Pemeriksaan</span>
                                            <span class="badge bg-info text-white fw-normal px-2 py-1" style="font-size: 0.75rem;">
                                                <i class="fas fa-check-circle me-1"></i>Syarat Min. 3x Kunjungan
                                            </span>
                                        </label>
                                        <small class="text-muted d-block mt-1">Aktifkan opsi ini untuk memfilter data laporan pasien berdasarkan jenis pemeriksaan tertentu <strong>minimal 3 kali</strong>.</small>
                                    </div>
                                </div>
                            </div>

                            {{-- Filter Pemeriksaan: Searchable Dropdown (Muncul saat Search By Pemeriksaan aktif) --}}
                            <div id="pemeriksaanBox" class="mt-3 pt-3 border-top border-info border-opacity-25" style="display: none;">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-6 col-lg-5">
                                        <label class="form-label fw-bold text-dark small mb-1">
                                            Pilih / Masukkan Nama Pemeriksaan:
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white text-info border-end-0">
                                                <i class="fas fa-search"></i>
                                            </span>
                                            <input type="text" 
                                                   name="pemeriksaan" 
                                                   id="pemeriksaanInput" 
                                                   class="form-control border-start-0 ps-0" 
                                                   list="pemeriksaanDatalist" 
                                                   value="" 
                                                   placeholder="Ketik pemeriksaan (contoh: Asam Urat, Glukosa)..."
                                                   autocomplete="off">
                                            <button class="btn btn-outline-info dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Pilih dari daftar"></button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="max-height: 250px; overflow-y: auto;">
                                                <li><h6 class="dropdown-header">Pilihan Pemeriksaan</h6></li>
                                                @if(isset($pemeriksaanOptions) && $pemeriksaanOptions->count() > 0)
                                                    @foreach($pemeriksaanOptions as $opt)
                                                        <li>
                                                            <a class="dropdown-item small" href="javascript:void(0)" onclick="selectPemeriksaan('{{ addslashes($opt) }}')">
                                                                {{ $opt }}
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                @else
                                                    <li><span class="dropdown-item text-muted small">Belum ada data pemeriksaan</span></li>
                                                @endif
                                            </ul>
                                            <datalist id="pemeriksaanDatalist">
                                                @if(isset($pemeriksaanOptions))
                                                    @foreach($pemeriksaanOptions as $opt)
                                                        <option value="{{ $opt }}">
                                                    @endforeach
                                                @endif
                                            </datalist>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-7">
                                        <div class="p-2 rounded bg-white bg-opacity-75 border border-info border-opacity-25 small text-secondary">
                                            <i class="fas fa-info-circle text-info me-1"></i>
                                            Gunakan <strong>koma (,)</strong> untuk kondisi <strong>ATAU</strong> (salah satu min. 3x), atau tanda <strong>plus (+)</strong> untuk kondisi <strong>DAN</strong> (semuanya masing-masing min. 3x).
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="col-12 pt-3 border-top mt-2">
                    <div class="d-flex gap-2">
                        <button type="button" id="btnPreview" class="btn btn-info px-4 py-2 fw-semibold">
                            <i class="fas fa-eye me-2"></i>Preview Laporan
                        </button>
                        <button type="button" id="btnReset" class="btn btn-outline-secondary px-4 py-2">
                            <i class="fas fa-times me-2"></i>Reset Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Card (Hidden by default) -->
    <div id="summaryCard" class="card shadow-sm border-0 mb-4" style="display:none;">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="mb-0 fw-semibold text-success">
                <i class="fas fa-chart-bar me-2"></i>Ringkasan Laporan
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="alert alert-primary mb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted d-block">Total Pelanggan</small>
                                <strong class="fs-5" id="summaryTotalPelanggan">0</strong>
                            </div>
                            <i class="fas fa-users fa-2x text-primary opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-warning mb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted d-block">Total Kunjungan</small>
                                <strong class="fs-5" id="summaryTotalKunjungan">0</strong>
                            </div>
                            <i class="fas fa-calendar-check fa-2x text-warning opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Buttons (Hidden by default) -->
    <div id="exportButtons" class="d-flex gap-2 mb-4" style="display:none;">
        <button type="button" id="btnExportExcel" class="btn btn-success">
            <i class="fas fa-file-excel me-2"></i>Export Excel
        </button>
        <button type="button" id="btnPrint" class="btn btn-primary">
            <i class="fas fa-print me-2"></i>Print
        </button>
    </div>

    <!-- Preview Table (Hidden by default) -->
    <div id="previewTable" class="card shadow-sm border-0" style="display:none;">
        <div class="card-header bg-white py-2 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold text-dark fs-6">
                <i class="fas fa-table me-2"></i>Preview Data
            </h6>
            <span id="previewInfo" class="badge bg-secondary">Menampilkan 0 data</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="previewTableElement" class="table table-hover table-striped mb-0 align-middle small" style="min-width: 1200px;">
                    <thead id="previewTableHead" class="table-light">
                        <tr>
                            <th class="px-2 py-2 text-center fw-semibold" style="width: 35px;">No</th>
                            <th class="py-2 fw-semibold" style="width: 100px;">PID</th>
                            <th class="py-2 fw-semibold" style="min-width: 200px;">Nama Pasien</th>
                            <th class="py-2 text-center fw-semibold" style="width: 100px;">Cabang</th>
                            <th class="py-2 fw-semibold" style="width: 110px;">No Telp</th>
                            <th class="py-2 text-center fw-semibold" style="width: 85px;">DOB</th>
                            <th class="py-2 fw-semibold" style="min-width: 120px;">Alamat</th>
                            <th class="py-2 text-center fw-semibold" style="width: 75px;">Kunjungan</th>
                            <th class="py-2 text-center fw-semibold" style="width: 110px;">Kunjungan Terakhir</th>
                            <th class="py-2 text-end fw-semibold" style="width: 110px;">Total Biaya</th>
                            <th class="py-2 text-center fw-semibold" style="width: 80px;">Kelas</th>
                        </tr>
                    </thead>
                    <tbody id="previewTableBody">
                        <!-- Data will be loaded here -->
                    </tbody>
                </table>
            </div>

            <!-- Pagination (Bootstrap 5 style) -->
            <div id="paginationContainer" class="d-flex justify-content-between align-items-center p-3 border-top bg-light small">
                <!-- Pagination will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div id="loadingIndicator" class="text-center py-5" style="display:none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Memuat data...</p>
    </div>

    <!-- Empty State -->
    <div id="emptyState" class="text-center py-5 text-muted" style="display:none;">
        <i class="fas fa-inbox fa-3x mb-3 text-secondary opacity-50"></i>
        <p class="mb-0">Tidak ada data yang sesuai dengan filter.</p>
    </div>

    <!-- Debug Info -->
    <div id="debugInfo" class="alert alert-danger" style="display:none;">
        <h6>Error Details:</h6>
        <pre id="debugContent" class="mb-0 small"></pre>
    </div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect      = document.getElementById('typeSelect');
    const bulanContainer  = document.getElementById('bulanContainer');
    const tahunContainer  = document.getElementById('tahunContainer');
    const rangeContainer  = document.getElementById('rangeContainer');
    const rangeContainer2 = document.getElementById('rangeContainer2');

    // ── Handle periode type change ────────────────────────────────────────────
    typeSelect.addEventListener('change', function() {
        const value = this.value;
        bulanContainer.style.display  = 'none';
        tahunContainer.style.display  = 'none';
        rangeContainer.style.display  = 'none';
        rangeContainer2.style.display = 'none';

        if (value === 'perbulan') {
            bulanContainer.style.display = 'block';
            tahunContainer.style.display = 'block';
        } else if (value === 'pertahun') {
            tahunContainer.style.display = 'block';
        } else if (value === 'range') {
            rangeContainer.style.display  = 'block';
            rangeContainer2.style.display = 'block';
        }
    });

    // ── Preview button ────────────────────────────────────────────────────────
    document.getElementById('btnPreview').addEventListener('click', function() {
        loadPreview(1);
    });

    // ── Reset button ──────────────────────────────────────────────────────────
    document.getElementById('btnReset').addEventListener('click', function() {
        document.getElementById('filterForm').reset();
        typeSelect.value = 'semua';
        typeSelect.dispatchEvent(new Event('change'));
        if (typeof togglePemeriksaanFilter === 'function') {
            togglePemeriksaanFilter(false);
        }
        document.getElementById('summaryCard').style.display   = 'none';
        document.getElementById('exportButtons').style.display = 'none';
        document.getElementById('previewTable').style.display  = 'none';
        document.getElementById('emptyState').style.display    = 'none';
        document.getElementById('debugInfo').style.display     = 'none';
    });

    // ── Export buttons ────────────────────────────────────────────────────────
    document.getElementById('btnExportExcel').addEventListener('click', function() {
        exportData('excel');
    });
    document.getElementById('btnPrint').addEventListener('click', function() {
        exportData('print');
    });

    // ── Load Preview ──────────────────────────────────────────────────────────
    function loadPreview(page) {
        page = page || 1;
        const form     = document.getElementById('filterForm');
        const formData = new FormData(form);
        formData.append('page', page);

        // Show loading
        document.getElementById('loadingIndicator').style.display = 'block';
        document.getElementById('previewTable').style.display     = 'none';
        document.getElementById('emptyState').style.display       = 'none';
        document.getElementById('debugInfo').style.display        = 'none';

        // Build query string
        const params = new URLSearchParams();
        formData.forEach(function(value, key) {
            if (value && key !== '_token') params.append(key, value);
        });

        const url = '{{ route("laporan.preview") }}?' + params.toString();

        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(response) {
            if (!response.ok) {
                return response.text().then(function(text) {
                    throw new Error('HTTP ' + response.status + ': ' + text.substring(0, 500));
                });
            }
            return response.json();
        })
        .then(function(data) {
            document.getElementById('loadingIndicator').style.display = 'none';

            // Server-side error
            if (data.error) {
                document.getElementById('debugInfo').style.display = 'block';
                document.getElementById('debugContent').textContent =
                    'Error: ' + data.message + '\nFile: ' + data.file + '\nLine: ' + data.line;
                return;
            }

            if (!data.data || !data.data.data || data.data.data.length === 0) {
                document.getElementById('emptyState').style.display    = 'block';
                document.getElementById('summaryCard').style.display   = 'none';
                document.getElementById('exportButtons').style.display = 'none';
                return;
            }

            var usePeriodeBiaya = data.usePeriodeBiaya || false;
            var isFilterPemeriksaan = data.isFilterPemeriksaanActive || false;

            // ── Summary ───────────────────────────────────────────────────────
            document.getElementById('summaryCard').style.display = 'block';
            document.getElementById('summaryTotalPelanggan').textContent =
                (data.summary.total_pelanggan || 0).toLocaleString('id-ID');
            document.getElementById('summaryTotalKunjungan').textContent =
                (data.summary.total_kunjungan || 0).toLocaleString('id-ID');

            // ── Export buttons ────────────────────────────────────────────────
            document.getElementById('exportButtons').style.display = 'flex';

            // ── Table ─────────────────────────────────────────────────────────
            document.getElementById('previewTable').style.display = 'block';

            var tableElem = document.getElementById('previewTableElement');
            var thead = document.getElementById('previewTableHead');
            if (tableElem) {
                tableElem.style.minWidth = isFilterPemeriksaan ? '1500px' : '1200px';
            }
            if (thead) {
                var theadHtml = '<tr>' +
                    '<th class="px-2 py-2 text-center fw-semibold" style="width: 35px;">No</th>' +
                    '<th class="py-2 fw-semibold" style="width: 100px;">PID</th>' +
                    '<th class="py-2 fw-semibold" style="min-width: 200px;">Nama Pasien</th>' +
                    '<th class="py-2 text-center fw-semibold" style="width: 100px;">Cabang</th>' +
                    '<th class="py-2 fw-semibold" style="width: 110px;">No Telp</th>' +
                    '<th class="py-2 text-center fw-semibold" style="width: 85px;">DOB</th>' +
                    '<th class="py-2 fw-semibold" style="min-width: 120px;">Alamat</th>' +
                    '<th class="py-2 text-center fw-semibold" style="width: 75px;">Kunjungan</th>' +
                    '<th class="py-2 text-center fw-semibold" style="width: 110px;">Kunjungan Terakhir</th>';

                if (isFilterPemeriksaan) {
                    theadHtml += '<th class="py-2 text-center text-info fw-semibold" style="min-width: 110px;">Total Terkait Pemeriksaan</th>' +
                                 '<th class="py-2 text-info fw-semibold" style="min-width: 180px;">Pemeriksaan Terakhir</th>' +
                                 '<th class="py-2 text-center text-info fw-semibold" style="min-width: 130px;">Tanggal Kunjungan Terakhir Terkait Pemeriksaan</th>';
                }

                theadHtml += '<th class="py-2 text-end fw-semibold" style="width: 110px;">Total Biaya</th>' +
                             '<th class="py-2 text-center fw-semibold" style="width: 80px;">Kelas</th>' +
                             '</tr>';
                thead.innerHTML = theadHtml;
            }

            var tbody       = document.getElementById('previewTableBody');
            tbody.innerHTML = '';
            var startNumber = (data.data.current_page - 1) * data.data.per_page + 1;

            data.data.data.forEach(function(item, index) {
                // Pilih kolom biaya & kedatangan sesuai filter periode
                var biaya = usePeriodeBiaya
                    ? (item.biaya_periode      != null ? item.biaya_periode      : (item.total_biaya      || 0))
                    : (item.total_biaya        || 0);
                var kedatangan = usePeriodeBiaya
                    ? (item.kedatangan_periode != null ? item.kedatangan_periode : (item.total_kedatangan || 0))
                    : (item.total_kedatangan   || 0);

                // Kelas: gunakan class_at_period jika tersedia
                var kelas = (usePeriodeBiaya && item.class_at_period)
                    ? item.class_at_period
                    : (item.class || 'Umum');

                var row = document.createElement('tr');
                var rowHtml =
                    '<td class="px-2 py-2 text-center">' + (startNumber + index) + '</td>' +
                    '<td class="py-2"><code class="bg-light px-1 py-1 rounded small text-nowrap">' + (item.pid || '-') + '</code></td>' +
                    '<td class="py-2 fw-medium">' + (item.nama || '-') + '</td>' +
                    '<td class="py-2 text-center"><span class="badge bg-info bg-opacity-10 text-info border border-info small text-nowrap">' + (item.cabang ? item.cabang.nama : '-') + '</span></td>' +
                    '<td class="py-2 text-nowrap small">' + (item.no_telp || '-') + '</td>' +
                    '<td class="py-2 text-center text-nowrap small">' + (item.dob ? formatDate(item.dob) : '-') + '</td>' +
                    '<td class="py-2 small">' + (item.alamat ? item.alamat.substring(0, 25) : '-') + '</td>' +
                    '<td class="py-2 text-center"><span class="badge bg-secondary bg-opacity-10 text-secondary small">' + Math.round(kedatangan) + '</span></td>' +
                    '<td class="py-2 text-center text-nowrap small">' + (item.tgl_kunjungan_terakhir ? formatDate(item.tgl_kunjungan_terakhir) : '-') + '</td>';

                if (isFilterPemeriksaan) {
                    rowHtml += '<td class="py-2 text-center"><span class="badge bg-info bg-opacity-10 text-info border border-info small">' + (item.total_terkait_pemeriksaan || 0) + '</span></td>' +
                               '<td class="py-2 small text-wrap" style="max-width: 250px;">' + (item.pemeriksaan_terakhir_terkait || '-') + '</td>' +
                               '<td class="py-2 text-center text-nowrap small">' + (item.tgl_kunjungan_terakhir_terkait || '-') + '</td>';
                }

                rowHtml += '<td class="py-2 text-end fw-semibold text-nowrap small">Rp ' + parseFloat(biaya).toLocaleString('id-ID') + '</td>' +
                           '<td class="py-2 text-center">' + getKelasBadge(kelas) + '</td>';

                row.innerHTML = rowHtml;
                tbody.appendChild(row);
            });

            // Update info badge
            document.getElementById('previewInfo').textContent =
                'Menampilkan ' + (data.data.from || 1) + '-' + (data.data.to || data.data.data.length) +
                ' dari ' + data.data.total + ' data';

            // Render pagination (Bootstrap 5 style)
            renderPagination(data.data);
        })
        .catch(function(error) {
            document.getElementById('loadingIndicator').style.display = 'none';
            document.getElementById('debugInfo').style.display        = 'block';
            document.getElementById('debugContent').textContent       = error.message;
        });
    }

    // ── Helper: Kelas Badge ───────────────────────────────────────────────────
    function getKelasBadge(kelas) {
        var badgeMap = {
            'Prioritas': 'bg-danger bg-opacity-10 text-danger border border-danger',
            'Loyal':     'bg-success bg-opacity-10 text-success border border-success',
            'Potensial': 'bg-warning bg-opacity-10 text-warning border border-warning',
            'Umum':      'bg-secondary bg-opacity-10 text-secondary border border-secondary'
        };
        var cls = badgeMap[kelas] || 'bg-secondary bg-opacity-10 text-secondary border border-secondary';
        return '<span class="badge ' + cls + ' small">' + (kelas || 'Umum') + '</span>';
    }

    // ── Helper: Format Date ───────────────────────────────────────────────────
    function formatDate(dateString) {
        if (!dateString) return '-';
        var date = new Date(dateString);
        if (isNaN(date.getTime())) return '-';
        return date.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    // ── Pagination Bootstrap 5 (dengan First/Last) ────────────────────────────
    // Revisi 3: Pagination mirip Dashboard Pelanggan (Bootstrap 5 style)
    function renderPagination(data) {
        var container = document.getElementById('paginationContainer');

        if (!data || data.last_page <= 1) {
            container.innerHTML =
                '<div class="text-muted">Menampilkan <strong>' + (data ? data.total : 0) + '</strong> data</div>' +
                '<div></div>';
            return;
        }

        var html = '<div class="text-muted small">Menampilkan <strong>' +
            (data.from || 1) + ' - ' + (data.to || data.data.length) +
            '</strong> dari <strong>' + data.total + '</strong> data</div>';

        html += '<nav aria-label="Navigasi halaman"><ul class="pagination pagination-sm mb-0">';

        // First «
        if (data.current_page > 1) {
            html += '<li class="page-item"><a class="page-link" href="#" onclick="window.changePage(1); return false;" title="Halaman Pertama">&laquo;</a></li>';
            html += '<li class="page-item"><a class="page-link" href="#" onclick="window.changePage(' + (data.current_page - 1) + '); return false;" title="Sebelumnya">&lsaquo;</a></li>';
        } else {
            html += '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
            html += '<li class="page-item disabled"><span class="page-link">&lsaquo;</span></li>';
        }

        // Page numbers with ellipsis
        var startPage = Math.max(1, data.current_page - 2);
        var endPage   = Math.min(data.last_page, data.current_page + 2);

        if (startPage > 1) {
            html += '<li class="page-item"><a class="page-link" href="#" onclick="window.changePage(1); return false;">1</a></li>';
            if (startPage > 2) {
                html += '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
            }
        }

        for (var i = startPage; i <= endPage; i++) {
            if (i === data.current_page) {
                html += '<li class="page-item active"><span class="page-link">' + i + '</span></li>';
            } else {
                html += '<li class="page-item"><a class="page-link" href="#" onclick="window.changePage(' + i + '); return false;">' + i + '</a></li>';
            }
        }

        if (endPage < data.last_page) {
            if (endPage < data.last_page - 1) {
                html += '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
            }
            html += '<li class="page-item"><a class="page-link" href="#" onclick="window.changePage(' + data.last_page + '); return false;">' + data.last_page + '</a></li>';
        }

        // Next › Last »
        if (data.current_page < data.last_page) {
            html += '<li class="page-item"><a class="page-link" href="#" onclick="window.changePage(' + (data.current_page + 1) + '); return false;" title="Berikutnya">&rsaquo;</a></li>';
            html += '<li class="page-item"><a class="page-link" href="#" onclick="window.changePage(' + data.last_page + '); return false;" title="Halaman Terakhir">&raquo;</a></li>';
        } else {
            html += '<li class="page-item disabled"><span class="page-link">&rsaquo;</span></li>';
            html += '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
        }

        html += '</ul></nav>';
        container.innerHTML = html;
    }

    // Global function for pagination onclick
    window.changePage = function(page) {
        loadPreview(page);
    };

    // ── Export ────────────────────────────────────────────────────────────────
    function exportData(format) {
        var form     = document.getElementById('filterForm');
        var formData = new FormData(form);
        var params   = new URLSearchParams();

        formData.forEach(function(value, key) {
            if (value && key !== '_token') params.append(key, value);
        });
        params.append('format', format);

        var url = '{{ route("laporan.export") }}?' + params.toString();

        if (format === 'print') {
            var printWindow = window.open(url, '_blank');
            if (printWindow) printWindow.focus();
        } else {
            window.location.href = url;
        }
    }
});

// =============================================
// PEMERIKSAAN AUTOCOMPLETE & TOGGLE HELPER
// =============================================
function togglePemeriksaanFilter(isChecked) {
    const box = document.getElementById('pemeriksaanBox');
    const input = document.getElementById('pemeriksaanInput');
    if (box) {
        box.style.display = isChecked ? '' : 'none';
    }
    if (!isChecked && input) {
        input.value = '';
    }
}
</script>
<script id="pemeriksaanOptionsData" type="application/json">{!! json_encode(isset($pemeriksaanOptions) ? $pemeriksaanOptions->values()->toArray() : []) !!}</script>
<script>
const allPemeriksaanOptions = JSON.parse(document.getElementById('pemeriksaanOptionsData')?.textContent || '[]');

function updatePemeriksaanDatalist() {
    const pemInput = document.getElementById('pemeriksaanInput');
    const pemDatalist = document.getElementById('pemeriksaanDatalist');
    if (!pemInput || !pemDatalist) return;

    const val = pemInput.value;
    const lastComma = val.lastIndexOf(',');
    const lastPlus  = val.lastIndexOf('+');
    const lastDelimIdx = Math.max(lastComma, lastPlus);

    let prefix = '';
    let currentToken = '';

    if (lastDelimIdx !== -1) {
        const delim = val[lastDelimIdx];
        const beforeDelim = val.substring(0, lastDelimIdx).trim();
        prefix = beforeDelim ? (beforeDelim + ' ' + delim + ' ') : (delim + ' ');
        currentToken = val.substring(lastDelimIdx + 1).trim();
    } else {
        currentToken = val.trim();
    }

    const tokenLower = currentToken.toLowerCase();
    const matched = tokenLower 
        ? allPemeriksaanOptions.filter(function(opt) { return opt.toLowerCase().indexOf(tokenLower) !== -1; })
        : allPemeriksaanOptions;

    let html = '';
    matched.slice(0, 50).forEach(function(opt) {
        const fullVal = prefix ? (prefix + opt) : opt;
        html += '<option value="' + fullVal.replace(/"/g, '&quot;') + '">';
    });
    pemDatalist.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', function() {
    const pemInput = document.getElementById('pemeriksaanInput');
    if (pemInput) {
        pemInput.addEventListener('input', updatePemeriksaanDatalist);
        pemInput.addEventListener('focus', updatePemeriksaanDatalist);
        updatePemeriksaanDatalist();
    }
});

function selectPemeriksaan(val) {
    const input = document.getElementById('pemeriksaanInput');
    if (!input) return;
    const current = input.value;
    const lastComma = current.lastIndexOf(',');
    const lastPlus  = current.lastIndexOf('+');
    const lastDelimIdx = Math.max(lastComma, lastPlus);

    if (lastDelimIdx === -1) {
        if (!current.trim()) {
            input.value = val;
        } else {
            input.value = current.trim() + ', ' + val;
        }
    } else {
        const delim = current[lastDelimIdx];
        const beforeDelim = current.substring(0, lastDelimIdx).trim();
        input.value = (beforeDelim ? beforeDelim + ' ' + delim + ' ' : delim + ' ') + val;
    }
    input.focus();
    updatePemeriksaanDatalist();
}
</script>
@endsection
