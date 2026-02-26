@extends('layouts.main')

@section('title', 'Dashboard - Medical Lab CRM')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-primary mb-0 fw-semibold">Dashboard Pelanggan</h3>
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
                            <label class="form-label fw-medium">Format File: .xlsx, .xls, .csv</label>
                            <input type="file" name="file" class="form-control form-control-lg" id="fileInput" required accept=".xlsx,.xls,.csv">                     
                            <div class="invalid-feedback">File harus berupa Excel atau CSV</div> 
                        </div>
                        <div class="col-md-2">
                             <button type="submit" class="btn btn-success btn-lg w-100" id="importBtn">
                                <span id="btnText"><i class="fas fa-upload me-2"></i>Import</span>
                                <span id="btnLoading" class="spinner-border spinner-border-sm" style="display: none;"></span>
                            </button>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-light border mb-0">
                                <small class="text-muted">
                                    <strong>Format Excel (10 kolom):</strong><br>
                                    No | Nama Pasien | Total Kedatangan | Tanggal Kedatangan | Total (Biaya) | No Telpon | DOB | PID | Alamat | Kota
                                </small>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-semibold text-primary">
                        <i class="fas fa-filter me-2"></i>Filter Data
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('pelanggan.index') }}" class="row g-3">
                        <!-- Search -->
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Cari (PID/Nama)</label>
                            <input type="text" name="search" class="form-control form-control-lg" value="{{ $search }}" placeholder="Masukkan PID atau Nama...">
                        </div>
                        
                        <!-- Cabang Filter -->
                        <div class="col-md-2">
                            <label class="form-label fw-medium">Cabang</label>
                            <select name="cabang_id" class="form-select form-select-lg">
                                <option value="">Semua Cabang</option>
                                @foreach($cabangs as $cabang)
                                    <option value="{{ $cabang->id }}" {{ $cabang_id == $cabang->id ? 'selected' : '' }}>
                                        {{ $cabang->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Omset Range Filter -->
                        <div class="col-md-2">
                            <label class="form-label fw-medium">Range Omset</label>
                            <select name="omset_range" class="form-select form-select-lg">
                                <option value="">Semua Omset</option>
                                <option value="0" {{ $omset_range === '0' ? 'selected' : '' }}>0 - < 1 Juta</option>
                                <option value="1" {{ $omset_range === '1' ? 'selected' : '' }}>1 Juta - < 4 Juta</option>
                                <option value="2" {{ $omset_range === '2' ? 'selected' : '' }}>4 Juta - Lebih</option>
                            </select>
                        </div>

                        <!-- Kedatangan Range Filter -->
                        <div class="col-md-2">
                            <label class="form-label fw-medium">Jumlah Kedatangan</label>
                            <select name="kedatangan_range" class="form-select form-select-lg">
                                <option value="">Semua Kedatangan</option>
                                <option value="0" {{ $kedatangan_range === '0' ? 'selected' : '' }}>≤ 2 Kali</option>
                                <option value="1" {{ $kedatangan_range === '1' ? 'selected' : '' }}>3 - 4 Kali</option>
                                <option value="2" {{ $kedatangan_range === '2' ? 'selected' : '' }}>> 4 Kali</option>
                            </select>
                        </div>

                        <!-- Type Filter -->
                        <div class="col-md-2">
                            <label class="form-label fw-medium">Periode</label>
                            <select name="type" id="typeSelect" class="form-select form-select-lg">
                                <option value="perbulan" {{ $type == 'perbulan' ? 'selected' : '' }}>Per Bulan</option>
                                <option value="pertahun" {{ $type == 'pertahun' ? 'selected' : '' }}>Per Tahun</option>
                                <option value="semua" {{ $type == 'semua' ? 'selected' : '' }}>Semua Data</option>
                            </select>
                        </div>

                        <!-- Bulan -->
                        <div class="col-md-2" id="bulanContainer" style="{{ $type == 'pertahun' || $type == 'semua' ? 'display:none;' : '' }}">
                            <label class="form-label fw-medium">Bulan</label>
                            <select name="bulan" class="form-select form-select-lg">
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" {{ $bulan == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                        {{ DateTime::createFromFormat('!m', $i)->format('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <!-- Tahun -->
                        <div class="col-md-2" id="tahunContainer" style="{{ $type == 'semua' ? 'display:none;' : '' }}">
                            <label class="form-label fw-medium">Tahun</label>
                            <select name="tahun" class="form-select form-select-lg">
                                @for($i = date('Y'); $i >= date('Y') - 5; $i--)
                                    <option value="{{ $i }}" {{ $tahun == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-search me-2"></i>Tampilkan
                            </button>
                        </div>
                        
                        @if($type || $search || $cabang_id || $omset_range || $kedatangan_range)
                        <div class="col-md-2 d-flex align-items-end">
                            <a href="{{ route('pelanggan.index') }}" class="btn btn-outline-secondary btn-lg w-100">
                                <i class="fas fa-times me-2"></i>Reset
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
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold text-info">
                        <i class="fas fa-users me-2"></i>Data Pelanggan
                    </h6>
                    @if($pelanggan->count() > 0)
                    <a href="{{ route('pelanggan.export', ['bulan' => $bulan, 'tahun' => $tahun, 'type' => $type, 'search' => $search]) }}" class="btn btn-success">
                        <i class="fas fa-file-excel me-2"></i>Export Excel
                    </a>
                    @endif
                </div>
                <div class="card-body p-0">
                    @if($pelanggan->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="px-4 py-3">No</th>
                                    <th class="py-3">
                                        <a href="{{ route('pelanggan.index', array_merge(request()->all(), ['sort' => 'pid', 'direction' => $sort == 'pid' && $direction == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark fw-semibold">
                                            PID <i class="fas fa-sort{{ $sort == 'pid' ? ($direction == 'asc' ? '-up' : '-down') : '' }} text-muted ms-1"></i>
                                        </a>
                                    </th>
                                    <th class="py-3">
                                        <a href="{{ route('pelanggan.index', array_merge(request()->all(), ['sort' => 'nama', 'direction' => $sort == 'nama' && $direction == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark fw-semibold">
                                            Nama Pasien <i class="fas fa-sort{{ $sort == 'nama' ? ($direction == 'asc' ? '-up' : '-down') : '' }} text-muted ms-1"></i>
                                        </a>
                                    </th>
                                    <th class="py-3">Cabang</th>
                                    <th class="py-3">No Telp</th>
                                    <th class="py-3">DOB</th>
                                    <th class="py-3">Alamat</th>
                                    <th class="py-3">Kota</th>
                                    <th class="py-3">Total Kedatangan</th>
                                    <th class="py-3">
                                        <a href="{{ route('pelanggan.index', array_merge(request()->all(), ['sort' => 'tgl_kunjungan', 'direction' => $sort == 'tgl_kunjungan' && $direction == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark fw-semibold">
                                            Tgl Kunjungan Terakhir <i class="fas fa-sort{{ $sort == 'tgl_kunjungan' ? ($direction == 'asc' ? '-up' : '-down') : '' }} text-muted ms-1"></i>
                                        </a>
                                    </th>
                                    <th class="py-3">Total Biaya</th>
                                    <th class="py-3">
                                        <a href="{{ route('pelanggan.index', array_merge(request()->all(), ['sort' => 'class', 'direction' => $sort == 'class' && $direction == 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark fw-semibold">
                                            Kelas <i class="fas fa-sort{{ $sort == 'class' ? ($direction == 'asc' ? '-up' : '-down') : '' }} text-muted ms-1"></i>
                                        </a>
                                    </th>
                                    <th class="py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pelanggan as $index => $p)
                                <tr>
                                    <td class="px-4">{{ $pelanggan->firstItem() + $index }}</td>
                                    <td><code class="bg-light px-2 py-1 rounded">{{ $p->pid }}</code></td>
                                    <td class="fw-medium">{{ $p->nama }}</td>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info">{{ $p->cabang?->nama ?? '-' }}</span>
                                    </td>
                                    <td>{{ $p->no_telp ?? '-' }}</td>
                                    <td>{{ $p->dob ? $p->dob->format('d-m-Y') : '-' }}</td>
                                    <td>{{ $p->alamat ?? '-' }}</td>
                                    <td>{{ $p->kota ?? '-' }}</td>
                                    <td><span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $p->total_kedatangan ?? $p->kunjungans->count() }} kali</span></td>
                                    <td>{{ $p->tgl_kunjungan }}</td>
                                    <td class="fw-semibold">Rp {{ number_format($p->total_biaya ?? $p->kunjungans->sum('biaya'), 0, ',', '.') }}</td>
                                    <td>
                                        @php
                                            $class = $p->class ?? 'Potensial';
                                            $badgeClass = match($class) {
                                                'Prioritas' => 'bg-danger bg-opacity-10 text-danger border border-danger',
                                                'Loyal' => 'bg-success bg-opacity-10 text-success border border-success',
                                                'Potensial' => 'bg-warning bg-opacity-10 text-warning border border-warning',
                                                default => 'bg-secondary bg-opacity-10 text-secondary border border-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ $class }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="{{ route('pelanggan.show', $p->id) }}" class="btn btn-sm btn-info" title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if(Auth::user()->role?->name === 'Admin')
                                            <a href="{{ route('pelanggan.edit', $p->id) }}" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('pelanggan.destroy', $p->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
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
                    <div class="d-flex justify-content-between align-items-center p-4 border-top bg-light">
                        <div class="text-muted">
                            Menampilkan <strong>{{ $pelanggan->firstItem() ?? 0 }} - {{ $pelanggan->lastItem() ?? 0 }}</strong> dari <strong>{{ $pelanggan->total() }}</strong> data
                        </div>
                        <div>
                            {{ $pelanggan->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                    @else
                    <div class="text-center py-5 text-muted">
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
    
    if (typeSelect) {
        typeSelect.addEventListener('change', function() {
            if (this.value === 'pertahun' || this.value === 'semua') {
                bulanContainer.style.display = 'none';
            } else {
                bulanContainer.style.display = 'block';
            }
            
            if (this.value === 'semua') {
                tahunContainer.style.display = 'none';
            } else {
                tahunContainer.style.display = 'block';
            }
        });
    }
    
    // Import form handling
    const importForm = document.getElementById('importForm');
    const importBtn = document.getElementById('importBtn');
    const fileInput = document.getElementById('fileInput');
    const btnText = document.getElementById('btnText');
    const btnLoading = document.getElementById('btnLoading');
    
    if (importForm && importBtn && fileInput) {
        importForm.addEventListener('submit', function(e) {
            const file = fileInput.files[0];
            
            if (file) {
                const validExtensions = ['.xlsx', '.xls', '.csv'];
                const fileName = file.name.toLowerCase();
                const isValid = validExtensions.some(ext => fileName.endsWith(ext));
                
                if (!isValid) {
                    e.preventDefault();
                    fileInput.classList.add('is-invalid');
                    alert('File harus berupa Excel (.xlsx, .xls) atau CSV (.csv)');
                    return false;
                }
                
                fileInput.classList.remove('is-invalid');
            }
            
            if (btnText && btnLoading) {
                importBtn.disabled = true;
                btnText.style.display = 'none';
                btnLoading.style.display = 'inline';
            }
        });
        
        fileInput.addEventListener('change', function() {
            this.classList.remove('is-invalid');
        });
    }
});
</script>
@endsection
