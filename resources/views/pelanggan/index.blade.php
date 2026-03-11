@extends('layouts.main')

@section('title', 'Dashboard - Medical Lab CRM')

@section('content')
@php $role = Auth::user()->role?->name; @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="text-primary mb-0 fw-semibold">Dashboard Pelanggan</h4>

        @if(in_array($role, ['Admin', 'Super Admin']))
            <a href="{{ route('pelanggan.create') }}" class="btn btn-primary btn-lg">
                <i class="fas fa-plus me-2"></i> Tambah Pelanggan
            </a>
        @endif
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" onclick="window.location.reload()"></button>
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
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" onclick="window.location.reload()"></button>
        </div>
    @endif

    <div class="row g-4">

        {{-- Import Card: Point 5 - hanya Admin & Super Admin --}}
        @if(in_array($role, ['Admin', 'Super Admin']))
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-semibold text-success">
                        <i class="fas fa-file-import me-2"></i>Import Data Kunjungan
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row align-items-end g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-medium small">Pilih Cabang <span class="text-danger">*</span></label>
                            <select class="form-select" id="importCabangSelect">
                                <option value="">-- Pilih Cabang --</option>
                                @foreach($cabangs as $cabang)
                                    <option value="{{ $cabang->id }}">{{ $cabang->nama }} ({{ $cabang->kode }})</option>
                                @endforeach
                            </select>
                            <div class="form-text text-muted" style="font-size:0.75rem;">
                                <i class="fas fa-info-circle me-1"></i>PID dalam file harus sesuai kode cabang.
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium small">Pilih File <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="fileInput" accept=".xlsx,.xls,.csv">
                            <div class="form-text text-muted" style="font-size:0.75rem;">
                                <i class="fas fa-info-circle me-1"></i>Format: .xlsx, .xls, .csv
                            </div>
                            <div class="invalid-feedback">File harus berupa Excel atau CSV</div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-success" id="importBtn" onclick="startImport()">
                                    <i class="fas fa-upload me-2"></i>Import
                                </button>
                                <a href="{{ route('pelanggan.download-template') }}" class="btn btn-outline-primary">
                                    <i class="fas fa-download me-2"></i>Download Template
                                </a>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="alert alert-light border mb-0">
                                <small class="text-muted">
                                    <strong>Format Excel (11 kolom):</strong><br>
                                    No | Nama Pasien | Total Kedatangan | Tanggal Kedatangan Terakhir | Total (Biaya) | No Telpon | DOB | PID | Alamat | Kota | Kelompok Pelanggan (mandiri/klinisi)
                                </small>
                            </div>
                        </div>
                    </div>

                    {{-- Progress Bar real-time (Point 1) --}}
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
                            <input type="text" name="search" class="form-control" value="{{ $search ?? '' }}" placeholder="Masukkan PID atau Nama...">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i>Cari
                            </button>
                        </div>
                        @if($search ?? '')
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
                                    <option value="{{ $cabang->id }}" {{ ($cabang_id ?? '') == $cabang->id ? 'selected' : '' }}>
                                        {{ $cabang->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-medium small">Kelas</label>
                            <select name="kelas" class="form-select">
                                <option value="">Semua Kelas</option>
                                <option value="Prioritas" {{ ($kelas ?? '') == 'Prioritas' ? 'selected' : '' }}>Prioritas</option>
                                <option value="Loyal" {{ ($kelas ?? '') == 'Loyal' ? 'selected' : '' }}>Loyal</option>
                                <option value="Potensial" {{ ($kelas ?? '') == 'Potensial' ? 'selected' : '' }}>Potensial</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-medium small">Range Omset</label>
                            <select name="omset_range" class="form-select">
                                <option value="">Semua Omset</option>
                                <option value="0" {{ ($omset_range ?? '') === '0' ? 'selected' : '' }}>0 - &lt; 1 Juta</option>
                                <option value="1" {{ ($omset_range ?? '') === '1' ? 'selected' : '' }}>1 Juta - &lt; 4 Juta</option>
                                <option value="2" {{ ($omset_range ?? '') === '2' ? 'selected' : '' }}>4 Juta - Lebih</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-medium small">Jumlah Kedatangan</label>
                            <select name="kedatangan_range" class="form-select">
                                <option value="">Semua Kedatangan</option>
                                <option value="0" {{ ($kedatangan_range ?? '') === '0' ? 'selected' : '' }}>&le; 2 Kali</option>
                                <option value="1" {{ ($kedatangan_range ?? '') === '1' ? 'selected' : '' }}>3 - 4 Kali</option>
                                <option value="2" {{ ($kedatangan_range ?? '') === '2' ? 'selected' : '' }}>&gt; 4 Kali</option>
                            </select>
                        </div>

                        <!-- Row 2: Periode, Bulan, Tahun -->
                        <div class="col-md-3">
                            <label class="form-label fw-medium small">Periode</label>
                            <select name="type" id="typeSelect" class="form-select">
                                <option value="semua" {{ ($type ?? 'semua') == 'semua' ? 'selected' : '' }}>Semua Data</option>
                                <option value="perbulan" {{ ($type ?? '') == 'perbulan' ? 'selected' : '' }}>Per Bulan</option>
                                <option value="pertahun" {{ ($type ?? '') == 'pertahun' ? 'selected' : '' }}>Per Tahun</option>
                            </select>
                        </div>

                        <div class="col-md-3" id="bulanContainer" style="{{ !($type ?? '') || ($type ?? '') == 'pertahun' || ($type ?? '') == 'semua' ? 'display:none;' : '' }}">
                            <label class="form-label fw-medium small">Bulan</label>
                            <select name="bulan" class="form-select">
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" {{ ($bulan ?? '') == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                        {{ DateTime::createFromFormat('!m', $i)->format('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-md-3" id="tahunContainer" style="{{ !($type ?? '') || ($type ?? '') == 'semua' ? 'display:none;' : '' }}">
                            <label class="form-label fw-medium small">Tahun</label>
                            <select name="tahun" class="form-select">
                                @for($i = date('Y'); $i >= date('Y') - 5; $i--)
                                    <option value="{{ $i }}" {{ ($tahun ?? date('Y')) == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>

                        {{-- Point 4: Kelompok Pelanggan --}}
                        <div class="col-md-3">
                            <label class="form-label fw-medium small">Kelompok Pelanggan</label>
                            <select name="kelompok_pelanggan" class="form-select">
                                <option value="">Semua Kelompok</option>
                                <option value="mandiri" {{ ($kelompok_pelanggan ?? '') == 'mandiri' ? 'selected' : '' }}>Mandiri</option>
                                <option value="klinisi" {{ ($kelompok_pelanggan ?? '') == 'klinisi' ? 'selected' : '' }}>Klinisi</option>
                            </select>
                        </div>

                        {{-- Point 4: Tipe Pelanggan --}}
                        <div class="col-md-3">
                            <label class="form-label fw-medium small">Tipe Pelanggan</label>
                            <select name="tipe_pelanggan" class="form-select">
                                <option value="">Semua Tipe</option>
                                <option value="biasa" {{ ($tipe_pelanggan ?? '') == 'biasa' ? 'selected' : '' }}>Pelanggan Biasa</option>
                                <option value="khusus" {{ ($tipe_pelanggan ?? '') == 'khusus' ? 'selected' : '' }}>Pelanggan Khusus</option>
                            </select>
                        </div>

                        <div class="col-12 d-flex gap-2 mt-1">
                            <button type="submit" class="btn btn-info px-3">
                                <i class="fas fa-filter me-2"></i>Terapkan Filter
                            </button>
                            <a href="{{ route('pelanggan.index') }}" class="btn btn-outline-secondary px-3">
                                <i class="fas fa-times me-2"></i>Reset Filter
                            </a>
                        </div>
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
                        {{-- Bulk Action Toolbar --}}
                        @if(in_array($role, ['Admin', 'Super Admin']))
                        <div id="bulkActionToolbar" class="d-none align-items-center gap-2">
                            <span class="badge bg-primary fs-6 px-3 py-2" id="selectedCount">0 dipilih</span>

                            {{-- Export Terpilih --}}
                            <form id="bulkExportForm" method="POST" action="{{ route('pelanggan.bulk-export') }}" class="d-inline">
                                @csrf
                                <div id="bulkExportIds"></div>
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-file-excel me-1"></i>Export Terpilih
                                </button>
                            </form>

                            {{-- Point 10: Hapus Terpilih - SA langsung, Admin perlu approval --}}
                            @if($role === 'Super Admin')
                                <form id="bulkDeleteFormSA" method="POST" action="{{ route('pelanggan.bulk-delete') }}" class="d-inline" onsubmit="return confirmBulkDeleteSA()">
                                    @csrf
                                    <div id="bulkDeleteIdsSA"></div>
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash me-1"></i>Hapus Terpilih
                                    </button>
                                </form>
                            @elseif($role === 'Admin')
                                {{-- Point 10: Admin → modal approval --}}
                                <button type="button" class="btn btn-danger btn-sm" onclick="openBulkDeleteModal()">
                                    <i class="fas fa-trash me-1"></i>Hapus Terpilih
                                </button>
                            @endif
                            {{-- Point 5: User tidak tampil Hapus Terpilih --}}
                        </div>
                        @endif

                        @if(isset($pelanggan) && method_exists($pelanggan, 'count') && $pelanggan->count() > 0)
                        <a href="{{ route('pelanggan.export', [
                            'bulan' => $bulan ?? '',
                            'tahun' => $tahun ?? '',
                            'type' => $type ?? '',
                            'search' => $search ?? '',
                            'cabang_id' => $cabang_id ?? '',
                            'kelas' => $kelas ?? '',
                            'omset_range' => $omset_range ?? '',
                            'kedatangan_range' => $kedatangan_range ?? '',
                            'kelompok_pelanggan' => $kelompok_pelanggan ?? '',
                            'tipe_pelanggan' => $tipe_pelanggan ?? '',
                        ]) }}" class="btn btn-success btn-sm" id="exportAllBtn">
                            <i class="fas fa-file-excel me-2"></i>Export Excel
                        </a>
                        @endif
                    </div>
                </div>

                <div class="card-body p-0">
                    @if(isset($pelanggan) && method_exists($pelanggan, 'count') && $pelanggan->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle small" style="min-width: 1200px;">
                            <thead class="table-light">
                                <tr>
                                    @if(in_array($role, ['Admin', 'Super Admin']))
                                    <th class="px-2 py-2 text-center" style="width: 40px;">
                                        <input type="checkbox" id="selectAll" class="form-check-input" title="Pilih Semua">
                                    </th>
                                    @endif
                                    <th class="px-2 py-2 text-center" style="width: 35px;">
                                        <a href="{{ route('pelanggan.index', array_merge(request()->all(), ['sort' => 'id', 'direction' => ($sort ?? '') == 'id' && ($direction ?? '') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark fw-semibold">
                                            No <i class="fas fa-sort{{ ($sort ?? '') == 'id' ? (($direction ?? '') == 'asc' ? '-up' : '-down') : '' }} text-muted ms-1"></i>
                                        </a>
                                    </th>
                                    <th class="py-2" style="width: 100px;">
                                        <a href="{{ route('pelanggan.index', array_merge(request()->all(), ['sort' => 'pid', 'direction' => ($sort ?? '') == 'pid' && ($direction ?? '') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark fw-semibold">
                                            PID <i class="fas fa-sort{{ ($sort ?? '') == 'pid' ? (($direction ?? '') == 'asc' ? '-up' : '-down') : '' }} text-muted ms-1"></i>
                                        </a>
                                    </th>
                                    <th class="py-2" style="min-width: 200px;">
                                        <a href="{{ route('pelanggan.index', array_merge(request()->all(), ['sort' => 'nama', 'direction' => ($sort ?? '') == 'nama' && ($direction ?? '') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark fw-semibold">
                                            Nama Pasien <i class="fas fa-sort{{ ($sort ?? '') == 'nama' ? (($direction ?? '') == 'asc' ? '-up' : '-down') : '' }} text-muted ms-1"></i>
                                        </a>
                                    </th>
                                    <th class="py-2 text-center" style="width: 100px;">
                                        <a href="{{ route('pelanggan.index', array_merge(request()->all(), ['sort' => 'cabang_id', 'direction' => ($sort ?? '') == 'cabang_id' && ($direction ?? '') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark fw-semibold">
                                            Cabang <i class="fas fa-sort{{ ($sort ?? '') == 'cabang_id' ? (($direction ?? '') == 'asc' ? '-up' : '-down') : '' }} text-muted ms-1"></i>
                                        </a>
                                    </th>
                                    <th class="py-2" style="width: 100px;">
                                        <a href="{{ route('pelanggan.index', array_merge(request()->all(), ['sort' => 'no_telp', 'direction' => ($sort ?? '') == 'no_telp' && ($direction ?? '') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark fw-semibold">
                                            No Telp <i class="fas fa-sort{{ ($sort ?? '') == 'no_telp' ? (($direction ?? '') == 'asc' ? '-up' : '-down') : '' }} text-muted ms-1"></i>
                                        </a>
                                    </th>
                                    <th class="py-2 text-center" style="width: 85px;">
                                        <a href="{{ route('pelanggan.index', array_merge(request()->all(), ['sort' => 'dob', 'direction' => ($sort ?? '') == 'dob' && ($direction ?? '') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark fw-semibold">
                                            DOB <i class="fas fa-sort{{ ($sort ?? '') == 'dob' ? (($direction ?? '') == 'asc' ? '-up' : '-down') : '' }} text-muted ms-1"></i>
                                        </a>
                                    </th>
                                    <th class="py-2" style="min-width: 120px; max-width: 180px;">
                                        <a href="{{ route('pelanggan.index', array_merge(request()->all(), ['sort' => 'alamat', 'direction' => ($sort ?? '') == 'alamat' && ($direction ?? '') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark fw-semibold">
                                            Alamat <i class="fas fa-sort{{ ($sort ?? '') == 'alamat' ? (($direction ?? '') == 'asc' ? '-up' : '-down') : '' }} text-muted ms-1"></i>
                                        </a>
                                    </th>
                                    <th class="py-2 text-center" style="width: 65px;">
                                        <a href="{{ route('pelanggan.index', array_merge(request()->all(), ['sort' => 'total_kedatangan', 'direction' => ($sort ?? '') == 'total_kedatangan' && ($direction ?? '') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark fw-semibold">
                                            Kunjungan <i class="fas fa-sort{{ ($sort ?? '') == 'total_kedatangan' ? (($direction ?? '') == 'asc' ? '-up' : '-down') : '' }} text-muted ms-1"></i>
                                        </a>
                                    </th>
                                    <th class="py-2 text-center" style="width: 100px;">
                                        <a href="{{ route('pelanggan.index', array_merge(request()->all(), ['sort' => 'tgl_kunjungan', 'direction' => ($sort ?? '') == 'tgl_kunjungan' && ($direction ?? '') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark fw-semibold">
                                            Kunjungan Terakhir <i class="fas fa-sort{{ ($sort ?? '') == 'tgl_kunjungan' ? (($direction ?? '') == 'asc' ? '-up' : '-down') : '' }} text-muted ms-1"></i>
                                        </a>
                                    </th>
                                    <th class="py-2 text-end" style="width: 100px;">
                                        <a href="{{ route('pelanggan.index', array_merge(request()->all(), ['sort' => 'total_biaya', 'direction' => ($sort ?? '') == 'total_biaya' && ($direction ?? '') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark fw-semibold">
                                            Total Biaya <i class="fas fa-sort{{ ($sort ?? '') == 'total_biaya' ? (($direction ?? '') == 'asc' ? '-up' : '-down') : '' }} text-muted ms-1"></i>
                                        </a>
                                    </th>
                                    <th class="py-2 text-center" style="width: 75px;">
                                        <a href="{{ route('pelanggan.index', array_merge(request()->all(), ['sort' => 'class', 'direction' => ($sort ?? '') == 'class' && ($direction ?? '') == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark fw-semibold">
                                            Kelas <i class="fas fa-sort{{ ($sort ?? '') == 'class' ? (($direction ?? '') == 'asc' ? '-up' : '-down') : '' }} text-muted ms-1"></i>
                                        </a>
                                    </th>
                                    <th class="py-2 text-center fw-semibold" style="width: 110px;">Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($pelanggan as $index => $p)
                                <tr>
                                    @if(in_array($role, ['Admin', 'Super Admin']))
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
                                            {{-- Point 5: User tidak boleh edit/delete --}}
                                            @if($role === 'Super Admin')
                                                <a href="{{ route('pelanggan.edit', $p->id) }}" class="btn btn-warning btn-sm" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('pelanggan.destroy', $p->id) }}" method="POST" class="d-inline"
                                                      onsubmit="return confirm('Yakin ingin menghapus pelanggan ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @elseif($role === 'Admin')
                                                <a href="{{ route('pelanggan.edit', $p->id) }}" class="btn btn-warning btn-sm" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                {{-- Point 10: Admin hapus → perlu approval --}}
                                                <button type="button" class="btn btn-danger btn-sm" title="Hapus"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deleteModal{{ $p->id }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                {{-- Modal Hapus untuk Admin (Point 10) --}}
                                @if($role === 'Admin')
                                <div class="modal fade" id="deleteModal{{ $p->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('pelanggan.destroy', $p->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <div class="modal-header">
                                                    <h5 class="modal-title text-danger">
                                                        <i class="fas fa-exclamation-triangle me-2"></i>Ajukan Hapus Pelanggan
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Anda akan mengajukan penghapusan pelanggan <strong>{{ $p->nama }}</strong> ({{ $p->pid }}) ke Superadmin.</p>
                                                    <label class="form-label fw-semibold">Catatan / Alasan Hapus <span class="text-danger">*</span></label>
                                                    <textarea name="catatan_hapus" class="form-control" rows="3"
                                                              placeholder="Wajib diisi. Contoh: Data duplikat / pelanggan tidak aktif." required></textarea>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-danger">
                                                        <i class="fas fa-paper-plane me-1"></i>Ajukan Hapus
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center p-3 border-top bg-light small">
                        <div class="text-muted">
                            Menampilkan <strong>{{ $pelanggan->firstItem() ?? 0 }} - {{ $pelanggan->lastItem() ?? 0 }}</strong>
                            dari <strong>{{ $pelanggan->total() }}</strong> data
                        </div>
                        <div>
                            {{ $pelanggan->appends(request()->query())->links('pagination::bootstrap-5') }}
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

    {{-- Modal Bulk Delete untuk Admin (Point 10) --}}
    @if($role === 'Admin')
    <div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="bulkDeleteFormAdmin" method="POST" action="{{ route('pelanggan.bulk-delete') }}">
                    @csrf
                    <div id="bulkDeleteIdsAdmin"></div>
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>Ajukan Hapus Terpilih
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Anda akan mengajukan penghapusan <strong id="bulkDeleteCount">0</strong> pelanggan terpilih ke Superadmin.</p>
                        <label class="form-label fw-semibold">Catatan / Alasan Hapus <span class="text-danger">*</span></label>
                        <textarea name="catatan_hapus" class="form-control" rows="3"
                                  placeholder="Wajib diisi. Contoh: Data duplikat / pelanggan tidak aktif." required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-paper-plane me-1"></i>Ajukan Hapus
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // =============================================
    // PERIODE FILTER - Show/Hide Bulan & Tahun
    // =============================================
    const typeSelect      = document.getElementById('typeSelect');
    const bulanContainer  = document.getElementById('bulanContainer');
    const tahunContainer  = document.getElementById('tahunContainer');

    function updatePeriodContainers() {
        if (!typeSelect) return;
        const val = typeSelect.value;
        if (bulanContainer) bulanContainer.style.display = (val === 'perbulan') ? 'block' : 'none';
        if (tahunContainer) tahunContainer.style.display = (val === 'semua') ? 'none' : 'block';
    }
    updatePeriodContainers();
    if (typeSelect) typeSelect.addEventListener('change', updatePeriodContainers);

    // =============================================
    // BULK ACTION - Checkbox Logic
    // =============================================
    const selectAll          = document.getElementById('selectAll');
    const bulkToolbar        = document.getElementById('bulkActionToolbar');
    const selectedCount      = document.getElementById('selectedCount');
    const bulkExportIds      = document.getElementById('bulkExportIds');
    const bulkDeleteIdsSA    = document.getElementById('bulkDeleteIdsSA');
    const bulkDeleteIdsAdmin = document.getElementById('bulkDeleteIdsAdmin');

    if (!selectAll || !bulkToolbar) return;

    function getChecked() {
        return Array.from(document.querySelectorAll('.row-checkbox:checked'));
    }

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

        const ids = checked.map(cb => cb.value);

        if (bulkExportIds) {
            bulkExportIds.innerHTML = '';
            ids.forEach(id => {
                const inp = document.createElement('input');
                inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = id;
                bulkExportIds.appendChild(inp);
            });
        }

        if (bulkDeleteIdsSA) {
            bulkDeleteIdsSA.innerHTML = '';
            ids.forEach(id => {
                const inp = document.createElement('input');
                inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = id;
                bulkDeleteIdsSA.appendChild(inp);
            });
        }

        if (bulkDeleteIdsAdmin) {
            bulkDeleteIdsAdmin.innerHTML = '';
            ids.forEach(id => {
                const inp = document.createElement('input');
                inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = id;
                bulkDeleteIdsAdmin.appendChild(inp);
            });
        }

        const countEl = document.getElementById('bulkDeleteCount');
        if (countEl) countEl.textContent = count;

        const allCbs = document.querySelectorAll('.row-checkbox');
        if (allCbs.length > 0) {
            selectAll.indeterminate = count > 0 && count < allCbs.length;
            selectAll.checked       = count === allCbs.length;
        }
    }

    selectAll.addEventListener('change', function() {
        document.querySelectorAll('.row-checkbox').forEach(cb => {
            cb.checked = this.checked;
            cb.closest('tr').classList.toggle('table-active', this.checked);
        });
        updateBulkToolbar();
    });

    document.querySelectorAll('.row-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            this.closest('tr').classList.toggle('table-active', this.checked);
            updateBulkToolbar();
        });
    });
});

// =============================================
// BULK DELETE HELPERS
// =============================================
function confirmBulkDeleteSA() {
    const checked = document.querySelectorAll('.row-checkbox:checked');
    if (checked.length === 0) { alert('Tidak ada pelanggan yang dipilih.'); return false; }
    return confirm('Yakin ingin menghapus ' + checked.length + ' pelanggan terpilih?\nData tidak dapat dikembalikan!');
}

function openBulkDeleteModal() {
    const checked = document.querySelectorAll('.row-checkbox:checked');
    if (checked.length === 0) { alert('Tidak ada pelanggan yang dipilih.'); return; }
    const modal = new bootstrap.Modal(document.getElementById('bulkDeleteModal'));
    modal.show();
}

// =============================================
// IMPORT - Real-time Progress Bar (Point 1)
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
    importBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mengimpor...';
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

        // Reset file input (Point 1)
        fileInput.value = '';
        importBtn.disabled = false;
        importBtn.innerHTML = '<i class="fas fa-upload me-2"></i>Import';

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
        importBtn.innerHTML = '<i class="fas fa-upload me-2"></i>Import';
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
