@extends('layouts.main')

@section('title', 'Dashboard - Medical Lab CRM')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="text-primary mb-0 fw-semibold">Dashboard Pelanggan</h4>

        @if(Auth::user()->role?->name === 'Admin')
            <a href="{{ route('pelanggan.create') }}" class="btn btn-primary btn-lg">
                <i class="fas fa-plus me-2"></i> Tambah Pelanggan
            </a>
        @endif
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            @if(session('import_errors'))
                <ul class="mb-0 mt-2">
                    @foreach(session('import_errors') as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Import Card -->
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-semibold text-success">
                        <i class="fas fa-file-import me-2"></i>Import Data Kunjungan
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('pelanggan.import') }}" enctype="multipart/form-data" class="row align-items-end g-3" id="importForm">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label fw-medium small">Format File: .xlsx, .xls, .csv</label>
                            <input type="file" name="file" class="form-control" id="fileInput" required accept=".xlsx,.xls,.csv">                     
                            <div class="invalid-feedback">File harus berupa Excel atau CSV</div> 
                        </div>
                        <div class="col-auto">
                             <button type="submit" class="btn btn-success" id="importBtn">
                                <span id="btnText"><i class="fas fa-upload me-2"></i>Import</span>
                                <span id="btnLoading" class="spinner-border spinner-border-sm" style="display: none;"></span>
                            </button>
                        </div>
                        <div class="col-auto">
                            <a href="{{ route('pelanggan.download-template') }}" class="btn btn-outline-primary">
                                <i class="fas fa-download me-2"></i>Download Template
                            </a>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-light border mb-0">
                                <small class="text-muted">
                                    <strong>Format Excel (11 kolom):</strong><br>
                                    No | Nama Pasien | Total Kedatangan | Tanggal Kedatangan Terakhir | Total (Biaya) | No Telpon | DOB | PID | Alamat | Kota | Kelompok Pelanggan (mandiri/klinisi)
                                </small>
                            </div>
                        </div>

                        <div class="col-12">
                            <div id="importProgressContainer" class="mt-2 d-none">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted fw-semibold">Progress Import</small>
                                    <small class="text-muted"><span id="importProgressText">0%</span></small>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div id="importProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%;" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Search Card -->
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-semibold text-primary">
                        <i class="fas fa-search me-2"></i>Pencarian
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('pelanggan.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-medium small">Cari (PID/Nama)</label>
                            <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Masukkan PID atau Nama...">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i>Cari
                            </button>
                        </div>
                        @if($search)
                        <div class="col-md-2">
                            <a href="{{ route('pelanggan.index') }}" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-times me-2"></i>Reset
                            </a>
                        </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-semibold text-info">
                        <i class="fas fa-filter me-2"></i>Filter Data
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('pelanggan.index') }}" class="row g-3">
                        <!-- Row 1: Cabang, Kelas, Omset, Kedatangan -->
                        <div class="col-md-3">
                            <label class="form-label fw-medium small">Cabang</label>
                            <select name="cabang_id" class="form-select">
                                <option value="">Semua Cabang</option>
                                @foreach($cabangs as $cabang)
                                    <option value="{{ $cabang->id }}" {{ $cabang_id == $cabang->id ? 'selected' : '' }}>
                                        {{ $cabang->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-medium small">Kelas</label>
                            <select name="kelas" class="form-select">
                                <option value="">Semua Kelas</option>
                                <option value="Prioritas" {{ $kelas == 'Prioritas' ? 'selected' : '' }}>Prioritas</option>
                                <option value="Loyal" {{ $kelas == 'Loyal' ? 'selected' : '' }}>Loyal</option>
                                <option value="Potensial" {{ $kelas == 'Potensial' ? 'selected' : '' }}>Potensial</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-medium small">Range Omset</label>
                            <select name="omset_range" class="form-select">
                                <option value="">Semua Omset</option>
                                <option value="0" {{ $omset_range === '0' ? 'selected' : '' }}>0 - < 1 Juta</option>
                                <option value="1" {{ $omset_range === '1' ? 'selected' : '' }}>1 Juta - < 4 Juta</option>
                                <option value="2" {{ $omset_range === '2' ? 'selected' : '' }}>4 Juta - Lebih</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-medium small">Jumlah Kedatangan</label>
                            <select name="kedatangan_range" class="form-select">
                                <option value="">Semua Kedatangan</option>
                                <option value="0" {{ $kedatangan_range === '0' ? 'selected' : '' }}>Γëñ 2 Kali</option>
                                <option value="1" {{ $kedatangan_range === '1' ? 'selected' : '' }}>3 - 4 Kali</option>
                                <option value="2" {{ $kedatangan_range === '2' ? 'selected' : '' }}>> 4 Kali</option>
                            </select>
                        </div>

                        <!-- Row 2: Type, Bulan, Tahun, Button -->
                        <div class="col-md-3">
                            <label class="form-label fw-medium small">Periode</label>
                            <select name="type" id="typeSelect" class="form-select">
                                <option value="semua" {{ $type == 'semua' ? 'selected' : '' }}>Semua Data</option>
                                <option value="perbulan" {{ $type == 'perbulan' ? 'selected' : '' }}>Per Bulan</option>
                                <option value="pertahun" {{ $type == 'pertahun' ? 'selected' : '' }}>Per Tahun</option>
                            </select>
                        </div>


                        <!-- Row 2: Bulan, Tahun, Button -->
                        <div class="col-md-3" id="bulanContainer" style="{{ !$type || $type == 'pertahun' || $type == 'semua' ? 'display:none;' : '' }}">
                            <label class="form-label fw-medium small">Bulan</label>
                            <select name="bulan" class="form-select">
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" {{ $bulan == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                        {{ DateTime::createFromFormat('!m', $i)->format('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-md-3" id="tahunContainer" style="{{ !$type || $type == 'semua' ? 'display:none;' : '' }}">

                            <label class="form-label fw-medium small">Tahun</label>
                            <select name="tahun" class="form-select">
                                @for($i = date('Y'); $i >= date('Y') - 5; $i--)
                                    <option value="{{ $i }}" {{ $tahun == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-auto d-flex align-items-end">
                            <button type="submit" class="btn btn-info">
                                <i class="fas fa-filter me-2"></i>Terapkan Filter
                            </button>
                        </div>
                        
                        @if($type || $cabang_id || $kelas || $omset_range || $kedatangan_range)
                        <div class="col-md-3 d-flex align-items-end">
                            <a href="{{ route('pelanggan.index') }}" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-times me-2"></i>Reset Filter
                            </a>
                        </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-2 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="mb-0 fw-semibold text-info fs-6">
                        <i class="fas fa-users me-2"></i>Data Pelanggan
                    </h6>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        {{-- Bulk Action Toolbar (muncul saat ada checkbox dipilih) --}}
                        @if(in_array(Auth::user()->role?->name, ['Admin', 'Super Admin']))
                        <div id="bulkActionToolbar" class="d-none align-items-center gap-2">
                            <span class="badge bg-primary fs-6 px-3 py-2" id="selectedCount">0 dipilih</span>

                            {{-- Form Export Terpilih --}}
                            <form id="bulkExportForm" method="POST" action="{{ route('pelanggan.bulk-export') }}" class="d-inline">
                                @csrf
                                <div id="bulkExportIds"></div>
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-file-excel me-1"></i>Export Terpilih
                                </button>
                            </form>

                            {{-- Form Hapus Terpilih --}}
                            <form id="bulkDeleteForm" method="POST" action="{{ route('pelanggan.bulk-delete') }}" class="d-inline" onsubmit="return confirmBulkDelete()">
                                @csrf
                                <div id="bulkDeleteIds"></div>
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash me-1"></i>Hapus Terpilih
                                </button>
                            </form>
                        </div>
                        @endif

                        @if($pelanggan->count() > 0)
                        <a href="{{ route('pelanggan.export', [
                            'bulan' => $bulan, 
                            'tahun' => $tahun, 
                            'type' => $type, 
                            'search' => $search,
                            'cabang_id' => $cabang_id,
                            'kelas' => $kelas,
                            'omset_range' => $omset_range,
                            'kedatangan_range' => $kedatangan_range
                        ]) }}" class="btn btn-success btn-sm" id="exportAllBtn">
                            <i class="fas fa-file-excel me-2"></i>Export Excel
                        </a>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($pelanggan->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle small" style="min-width: 1200px;">
                            <thead class="table-light">
                                <tr>
                                    @if(in_array(Auth::user()->role?->name, ['Admin', 'Super Admin']))
                                    <th class="px-2 py-2 text-center" style="width: 40px;">
                                        <input type="checkbox" id="selectAll" class="form-check-input" title="Pilih Semua">
                                    </th>
                                    @endif
                                    <th class="px-2 py-2 text-center" style="width: 35px;">
                                        <a href="{{ route('pelanggan.index', array_merge(request()->all(), ['sort' => 'id', 'direction' => $sort == 'id' && $direction == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark fw-semibold">
                                            No <i class="fas fa-sort{{ $sort == 'id' ? ($direction == 'asc' ? '-up' : '-down') : '' }} text-muted ms-1"></i>
                                        </a>
                                    </th>

                                    <th class="py-2" style="width: 100px;">
                                        <a href="{{ route('pelanggan.index', array_merge(request()->all(), ['sort' => 'pid', 'direction' => $sort == 'pid' && $direction == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark fw-semibold">
                                            PID <i class="fas fa-sort{{ $sort == 'pid' ? ($direction == 'asc' ? '-up' : '-down') : '' }} text-muted ms-1"></i>
                                        </a>
                                    </th>
                                    <th class="py-2" style="min-width: 200px;">
                                        <a href="{{ route('pelanggan.index', array_merge(request()->all(), ['sort' => 'nama', 'direction' => $sort == 'nama' && $direction == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark fw-semibold">
                                            Nama Pasien <i class="fas fa-sort{{ $sort == 'nama' ? ($direction == 'asc' ? '-up' : '-down') : '' }} text-muted ms-1"></i>
                                        </a>
                                    </th>
                                    <th class="py-2 text-center" style="width: 100px;">
                                        <a href="{{ route('pelanggan.index', array_merge(request()->all(), ['sort' => 'cabang_id', 'direction' => $sort == 'cabang_id' && $direction == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark fw-semibold">
                                            Cabang <i class="fas fa-sort{{ $sort == 'cabang_id' ? ($direction == 'asc' ? '-up' : '-down') : '' }} text-muted ms-1"></i>
                                        </a>
                                    </th>

                                    <th class="py-2" style="width: 100px;">
                                        <a href="{{ route('pelanggan.index', array_merge(request()->all(), ['sort' => 'no_telp', 'direction' => $sort == 'no_telp' && $direction == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark fw-semibold">
                                            No Telp <i class="fas fa-sort{{ $sort == 'no_telp' ? ($direction == 'asc' ? '-up' : '-down') : '' }} text-muted ms-1"></i>
                                        </a>
                                    </th>

                                    <th class="py-2 text-center" style="width: 85px;">
                                        <a href="{{ route('pelanggan.index', array_merge(request()->all(), ['sort' => 'dob', 'direction' => $sort == 'dob' && $direction == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark fw-semibold">
                                            DOB <i class="fas fa-sort{{ $sort == 'dob' ? ($direction == 'asc' ? '-up' : '-down') : '' }} text-muted ms-1"></i>
                                        </a>
                                    </th>

                                    <th class="py-2" style="min-width: 120px; max-width: 180px;">
                                        <a href="{{ route('pelanggan.index', array_merge(request()->all(), ['sort' => 'alamat', 'direction' => $sort == 'alamat' && $direction == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark fw-semibold">
                                            Alamat <i class="fas fa-sort{{ $sort == 'alamat' ? ($direction == 'asc' ? '-up' : '-down') : '' }} text-muted ms-1"></i>
                                        </a>
                                    </th>

                                    <th class="py-2 text-center" style="width: 65px;">
                                        <a href="{{ route('pelanggan.index', array_merge(request()->all(), ['sort' => 'total_kedatangan', 'direction' => $sort == 'total_kedatangan' && $direction == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark fw-semibold">
                                            Kunjungan <i class="fas fa-sort{{ $sort == 'total_kedatangan' ? ($direction == 'asc' ? '-up' : '-down') : '' }} text-muted ms-1"></i>
                                        </a>
                                    </th>

                                    <th class="py-2 text-center" style="width: 100px;">
                                        <a href="{{ route('pelanggan.index', array_merge(request()->all(), ['sort' => 'tgl_kunjungan', 'direction' => $sort == 'tgl_kunjungan' && $direction == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark fw-semibold">
                                            Kunjungan Terakhir <i class="fas fa-sort{{ $sort == 'tgl_kunjungan' ? ($direction == 'asc' ? '-up' : '-down') : '' }} text-muted ms-1"></i>
                                        </a>
                                    </th>
                                    <th class="py-2 text-end" style="width: 100px;">
                                        <a href="{{ route('pelanggan.index', array_merge(request()->all(), ['sort' => 'total_biaya', 'direction' => $sort == 'total_biaya' && $direction == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark fw-semibold">
                                            Total Biaya <i class="fas fa-sort{{ $sort == 'total_biaya' ? ($direction == 'asc' ? '-up' : '-down') : '' }} text-muted ms-1"></i>
                                        </a>
                                    </th>

                                    <th class="py-2 text-center" style="width: 75px;">
                                        <a href="{{ route('pelanggan.index', array_merge(request()->all(), ['sort' => 'class', 'direction' => $sort == 'class' && $direction == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark fw-semibold">
                                            Kelas <i class="fas fa-sort{{ $sort == 'class' ? ($direction == 'asc' ? '-up' : '-down') : '' }} text-muted ms-1"></i>
                                        </a>
                                    </th>
                                    <th class="py-2 text-center fw-semibold" style="width: 110px;">Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($pelanggan as $index => $p)
                                <tr>
                                    @if(in_array(Auth::user()->role?->name, ['Admin', 'Super Admin']))
                                    <td class="px-2 py-2 text-center">
                                        <input type="checkbox" class="form-check-input row-checkbox" value="{{ $p->id }}" data-nama="{{ $p->nama }}">
                                    </td>
                                    @endif

                                    <td class="px-2 py-2 text-center">{{ $pelanggan->firstItem() + $index }}</td>
                                    <td class="py-2"><code class="bg-light px-1 py-1 rounded small text-nowrap">{{ $p->pid }}</code></td>
                                    <td class="py-2 fw-medium">{{ $p->nama }}</td>
                                    <td class="py-2 text-center">
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info small text-nowrap">{{ $p->cabang?->nama ?? '-' }}</span>
                                    </td>
                                    <td class="py-2 text-nowrap small">{{ $p->no_telp ?? '-' }}</td>
                                    <td class="py-2 text-center text-nowrap small">{{ $p->dob ? $p->dob->format('d-m-Y') : '-' }}</td>
                                    <td class="py-2 small">{{ Str::limit($p->alamat, 25) ?? '-' }}</td>
                                    <td class="py-2 text-center"><span class="badge bg-secondary bg-opacity-10 text-secondary small">{{ $p->total_kedatangan ?? $p->kunjungans->count() }}</span></td>
                                    <td class="py-2 text-center text-nowrap small">{{ $p->tgl_kunjungan }}</td>
                                    <td class="py-2 text-end fw-semibold text-nowrap small">Rp {{ number_format($p->total_biaya ?? $p->kunjungans->sum('biaya'), 0, ',', '.') }}</td>
                                    <td class="py-2 text-center">
                                        @php
                                            $class = $p->class ?? 'Potensial';
                                            $badgeClass = match($class) {
                                                'Prioritas' => 'bg-danger bg-opacity-10 text-danger border border-danger',
                                                'Loyal' => 'bg-success bg-opacity-10 text-success border border-success',
                                                'Potensial' => 'bg-warning bg-opacity-10 text-warning border border-warning',
                                                default => 'bg-secondary bg-opacity-10 text-secondary border border-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }} small">{{ $class }}</span>
                                    </td>
                                    <td class="py-2 text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('pelanggan.show', $p->id) }}" class="btn btn-info btn-sm" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if(Auth::user()->role?->name === 'Admin')
                                            <a href="{{ route('pelanggan.edit', $p->id) }}" class="btn btn-warning btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('pelanggan.destroy', $p->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center p-3 border-top bg-light small">
                        <div class="text-muted">
                            Menampilkan <strong>{{ $pelanggan->firstItem() ?? 0 }} - {{ $pelanggan->lastItem() ?? 0 }}</strong> dari <strong>{{ $pelanggan->total() }}</strong> data
                        </div>
                        <div>
                            {{ $pelanggan->links('pagination::bootstrap-5') }}
                        </div>
                    </div>

                    @else
                    <div class="text-center py-5 text-muted small">
                        <i class="fas fa-inbox fa-3x mb-3 text-secondary opacity-50"></i>
                        <p class="mb-0">Belum ada data pelanggan. Silakan pilih filter atau import data.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('typeSelect');
    const bulanContainer = document.getElementById('bulanContainer');
    const tahunContainer = document.getElementById('tahunContainer');
    
    /**
     * Update visibility of Bulan and Tahun containers based on selected type
     */
    function updatePeriodContainers() {
        if (!typeSelect) return;
        
        const selectedType = typeSelect.value;
        
        if (selectedType === 'pertahun' || selectedType === 'semua') {
            bulanContainer.style.display = 'none';
        } else {
            bulanContainer.style.display = 'block';
        }
        
        if (selectedType === 'semua') {
            tahunContainer.style.display = 'none';
        } else {
            tahunContainer.style.display = 'block';
        }
    }
    
    // Set initial state on page load
    updatePeriodContainers();
    
    // Update on change
    if (typeSelect) {
        typeSelect.addEventListener('change', updatePeriodContainers);
    }

    
    // =============================================
    // IMPORT FORM ΓÇö AJAX + Real Progress Polling
    // =============================================
    const importForm = document.getElementById('importForm');
    const importBtn = document.getElementById('importBtn');
    const fileInput = document.getElementById('fileInput');
    const btnText = document.getElementById('btnText');
    const btnLoading = document.getElementById('btnLoading');
    const progressContainer = document.getElementById('importProgressContainer');
    const progressBar = document.getElementById('importProgressBar');
    const progressText = document.getElementById('importProgressText');
    let progressInterval = null;

    /**
     * Escape HTML untuk mencegah XSS pada pesan notifikasi
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(String(text)));
        return div.innerHTML;
    }

    /**
     * Tampilkan notifikasi inline di atas import card
     * type: 'success' | 'error'
     * errors: array string (opsional, untuk daftar error detail)
     */
    function showImportNotification(type, message, errors) {
        // Hapus notifikasi import sebelumnya
        document.querySelectorAll('.import-notification').forEach(function(el) {
            el.remove();
        });

        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const icon      = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

        let errorsHtml = '';
        if (errors && errors.length > 0) {
            errorsHtml = '<ul class="mb-0 mt-2">' +
                errors.map(function(e) { return '<li>' + escapeHtml(e) + '</li>'; }).join('') +
                '</ul>';
        }

        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert ' + alertClass + ' alert-dismissible fade show shadow-sm import-notification';
        alertDiv.setAttribute('role', 'alert');
        alertDiv.innerHTML =
            '<i class="fas ' + icon + ' me-2"></i>' + escapeHtml(message) +
            errorsHtml +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';

        // Sisipkan tepat sebelum import card (col-12 pertama dalam .row.g-4)
        const rowContainer = document.querySelector('.row.g-4');
        if (rowContainer) {
            rowContainer.insertBefore(alertDiv, rowContainer.firstChild);
        }

        // Scroll ke atas agar notifikasi terlihat
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    /**
     * Reset tampilan progress bar ke kondisi awal
     */
    function resetProgressBar() {
        if (progressContainer) progressContainer.classList.add('d-none');
        if (progressBar) { progressBar.style.width = '0%'; progressBar.setAttribute('aria-valuenow', '0'); }
        if (progressText) progressText.textContent = '0%';
    }

    /**
     * Reset tombol import ke kondisi normal
     */
    function resetImportBtn() {
        if (importBtn) importBtn.disabled = false;
        if (btnText) btnText.style.display = 'inline';
        if (btnLoading) btnLoading.style.display = 'none';
    }

    if (importForm && importBtn && fileInput) {

        importForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Selalu cegah submit normal ΓÇö gunakan AJAX

            const file = fileInput.files[0];

            if (!file) {
                showImportNotification('error', 'Pilih file terlebih dahulu.');
                return;
            }

            const validExtensions = ['.xlsx', '.xls', '.csv'];
            const fileName = file.name.toLowerCase();
            const isValid = validExtensions.some(function(ext) { return fileName.endsWith(ext); });

            if (!isValid) {
                fileInput.classList.add('is-invalid');
                showImportNotification('error', 'File harus berupa Excel (.xlsx, .xls) atau CSV (.csv)');
                return;
            }

            fileInput.classList.remove('is-invalid');

            // Hapus notifikasi lama
            document.querySelectorAll('.import-notification').forEach(function(el) { el.remove(); });

            // Tampilkan loading state
            importBtn.disabled = true;
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline-block';

            // Tampilkan progress bar mulai dari 0%
            progressContainer.classList.remove('d-none');
            progressBar.style.width = '0%';
            progressBar.setAttribute('aria-valuenow', '0');
            progressText.textContent = '0%';

            // Mulai polling progress setiap 500ms
            // Ini bisa berjalan real-time karena AJAX request import
            // diproses oleh PHP-FPM worker yang berbeda
            if (progressInterval) clearInterval(progressInterval);
            progressInterval = setInterval(async function() {
                try {
                    const resp = await fetch("{{ route('pelanggan.import.progress') }}", {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                    if (!resp.ok) return;
                    const data = await resp.json();
                    const pct = Math.max(0, Math.min(100, parseInt(data.progress || 0)));
                    progressBar.style.width = pct + '%';
                    progressBar.setAttribute('aria-valuenow', pct.toString());
                    progressText.textContent = pct + '%';
                    if (pct >= 100) {
                        clearInterval(progressInterval);
                        progressInterval = null;
                    }
                } catch (err) { /* abaikan error polling sementara */ }
            }, 500);

            // Kirim form via AJAX (fetch)
            const formData = new FormData(importForm);

            fetch(importForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(async function(response) {
                // Hentikan polling & set progress ke 100%
                if (progressInterval) { clearInterval(progressInterval); progressInterval = null; }
                progressBar.style.width = '100%';
                progressBar.setAttribute('aria-valuenow', '100');
                progressText.textContent = '100%';

                resetImportBtn();

                let data;
                try {
                    data = await response.json();
                } catch (parseErr) {
                    showImportNotification('error', 'Terjadi kesalahan tidak terduga. Silakan coba lagi.');
                    resetProgressBar();
                    return;
                }

                if (data.success) {
                    // Sukses: tampilkan notifikasi hijau, reset file input
                    showImportNotification('success', data.message || 'Import berhasil!');
                    fileInput.value = '';
                    // Biarkan progress bar tetap 100% sebagai konfirmasi visual
                    // User bisa klik X notifikasi jika sudah selesai membaca
                } else {
                    // Gagal: tampilkan notifikasi merah + daftar error jika ada
                    showImportNotification('error', data.message || 'Import gagal.', data.errors || []);
                    // Reset progress bar setelah gagal
                    setTimeout(function() { resetProgressBar(); }, 1500);
                }
            })
            .catch(function(networkErr) {
                if (progressInterval) { clearInterval(progressInterval); progressInterval = null; }
                resetImportBtn();
                resetProgressBar();
                showImportNotification('error', 'Koneksi terputus atau server tidak merespons. Silakan coba lagi.');
            });
        });

        fileInput.addEventListener('change', function() {
            this.classList.remove('is-invalid');
        });
    }

    // =============================================
    // BULK ACTION ΓÇö Checkbox Logic
    // =============================================
    const selectAll       = document.getElementById('selectAll');
    const bulkToolbar     = document.getElementById('bulkActionToolbar');
    const selectedCount   = document.getElementById('selectedCount');
    const bulkExportIds   = document.getElementById('bulkExportIds');
    const bulkDeleteIds   = document.getElementById('bulkDeleteIds');

    // Hanya jalankan jika elemen ada (role Admin/SuperAdmin)
    if (!selectAll || !bulkToolbar) return;

    /**
     * Ambil semua checkbox yang sedang dicentang
     */
    function getChecked() {
        return Array.from(document.querySelectorAll('.row-checkbox:checked'));
    }

    /**
     * Update toolbar: tampilkan/sembunyikan, update counter, sync hidden inputs
     */
    function updateBulkToolbar() {
        const checked = getChecked();
        const count   = checked.length;

        if (count > 0) {
            bulkToolbar.classList.remove('d-none');
            bulkToolbar.classList.add('d-flex');
            selectedCount.textContent = count + ' dipilih';
        } else {
            bulkToolbar.classList.add('d-none');
            bulkToolbar.classList.remove('d-flex');
        }

        // Sync hidden input IDs ke kedua form
        const ids = checked.map(cb => cb.value);

        // Export form
        if (bulkExportIds) {
            bulkExportIds.innerHTML = '';
            ids.forEach(id => {
                const input = document.createElement('input');
                input.type  = 'hidden';
                input.name  = 'ids[]';
                input.value = id;
                bulkExportIds.appendChild(input);
            });
        }

        // Delete form
        if (bulkDeleteIds) {
            bulkDeleteIds.innerHTML = '';
            ids.forEach(id => {
                const input = document.createElement('input');
                input.type  = 'hidden';
                input.name  = 'ids[]';
                input.value = id;
                bulkDeleteIds.appendChild(input);
            });
        }

        // Update state selectAll checkbox
        const allCheckboxes = document.querySelectorAll('.row-checkbox');
        if (allCheckboxes.length > 0) {
            selectAll.indeterminate = count > 0 && count < allCheckboxes.length;
            selectAll.checked       = count === allCheckboxes.length;
        }
    }

    // Select All / Deselect All
    selectAll.addEventListener('change', function() {
        document.querySelectorAll('.row-checkbox').forEach(cb => {
            cb.checked = this.checked;
            // Highlight baris yang dipilih
            cb.closest('tr').classList.toggle('table-active', this.checked);
        });
        updateBulkToolbar();
    });

    // Individual checkbox
    document.querySelectorAll('.row-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            this.closest('tr').classList.toggle('table-active', this.checked);
            updateBulkToolbar();
        });
    });


});

/**
 * Konfirmasi hapus bulk ΓÇö dipanggil dari onsubmit form
 */
function confirmBulkDelete() {
    const checked = document.querySelectorAll('.row-checkbox:checked');
    const count   = checked.length;
    if (count === 0) {
        alert('Tidak ada pelanggan yang dipilih.');
        return false;
    }
    return confirm(
        'Yakin ingin menghapus ' + count + ' pelanggan terpilih?\n\n' +
        'Data yang dihapus tidak dapat dikembalikan!'
    );
}
</script>
@endsection
