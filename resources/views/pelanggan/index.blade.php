@extends('layouts.main')

@section('title', 'Dashboard - Medical Lab CRM')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-primary mb-0">Dashboard Pelanggan</h3>
        @if(Auth::user()->role?->name === 'Admin')
            <a href="{{ route('pelanggan.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Pelanggan</a>
        @endif
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
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

    <div class="row">
        <!-- Import Card -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <i class="fas fa-file-import text-success me-2"></i> Import Data Kunjungan
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('pelanggan.import') }}" enctype="multipart/form-data" class="row align-items-end" id="importForm">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label">Format File: .xlsx, .xls, .csv</label>
                            <input type="file" name="file" class="form-control" id="fileInput" required accept=".xlsx,.xls,.csv">                     
                            <div class="invalid-feedback">File harus berupa Excel atau CSV</div> 
                        </div>
                        <div class="col-md-2">
                             <button type="submit" class="btn btn-success w-100" id="importBtn">
                                <span id="btnText"><i class="fas fa-upload"></i> Import</span>
                                <span id="btnLoading" style="display: none;"><i class="fas fa-spinner fa-spin"></i> Memproses...</span>
                             </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Search Card (Pencarian NIK/Nama) -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <i class="fas fa-search text-info me-2"></i> Cari Pelanggan
                </div>
                <div class="card-body">
                    <form method="GET" class="row align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Cari NIK/Nama</label>
                            <input type="text" name="search" class="form-control" placeholder="Masukkan NIK atau Nama" value="{{ $search }}">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-info me-2 text-white"><i class="fas fa-search"></i> Search</button>
                            @if($search)
                                <a href="{{ route('dashboard') }}" class="btn btn-secondary"><i class="fas fa-times"></i> Clear</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <i class="fas fa-filter text-primary me-2"></i> Filter Data
                </div>
                <div class="card-body">
                    <form method="GET" class="row align-items-end">
                        <div class="col-md-2">
                            <label class="form-label">Tipe Filter</label>
                            <select name="type" class="form-select">
                                <option value="" {{ !$type ? 'selected' : '' }}>--Pilihan--</option>
                                <option value="perbulan" {{ $type == 'perbulan' ? 'selected' : '' }}>Per Bulan</option>
                                <option value="pertahun" {{ $type == 'pertahun' ? 'selected' : '' }}>Per Tahun</option>
                                <option value="semua" {{ $type == 'semua' ? 'selected' : '' }}>Semua Data</option>
                            </select>
                        </div>

                        <div class="col-md-2" id="bulan-container">
                            <label class="form-label">Bulan</label>
                            <select name="bulan" class="form-select">
                                <option value="" {{ !$bulan ? 'selected' : '' }}>--Pilihan--</option>
                                @foreach ([
                                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                                ] as $num => $nama)
                                    <option value="{{ $num }}" {{ $bulan == $num ? 'selected' : '' }}>{{ $nama }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2" id="tahun-container">
                            <label class="form-label">Tahun</label>
                            <select name="tahun" class="form-select">
                                <option value="" {{ !$tahun ? 'selected' : '' }}>--Pilihan--</option>
                                @for ($i = 2020; $i <= date('Y') + 1; $i++)
                                    <option value="{{ $i }}" {{ $tahun == $i ? 'selected' : '' }}>
                                        {{ $i }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-primary me-2"><i class="fas fa-filter"></i> Filter</button>
                            @if($type || $bulan || $tahun)
                                <a href="{{ route('dashboard') }}" class="btn btn-secondary me-2"><i class="fas fa-times"></i> Clear</a>
                                <a href="{{ route('pelanggan.export', ['bulan' => $bulan, 'tahun' => $tahun, 'type' => $type, 'search' => $search]) }}" class="btn btn-success text-white"><i class="fas fa-file-excel me-1"></i> Export</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    @if($search && isset($searchMode) && $searchMode)
        <!-- Tabel Daftar Pelanggan (Hasil Pencarian) -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-users text-info me-2"></i> Hasil Pencarian: {{ $search }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'nik', 'direction' => ($sort == 'nik' && $direction == 'asc') ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark">
                                        NIK
                                        @if($sort == 'nik')
                                            <i class="fas fa-sort-{{ $direction == 'asc' ? 'up' : 'down' }}"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'nama', 'direction' => ($sort == 'nama' && $direction == 'asc') ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark">
                                        Nama
                                        @if($sort == 'nama')
                                            <i class="fas fa-sort-{{ $direction == 'asc' ? 'up' : 'down' }}"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'tgl_kunjungan', 'direction' => ($sort == 'tgl_kunjungan' && $direction == 'asc') ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark">
                                        Tanggal Kunjungan
                                        @if($sort == 'tgl_kunjungan')
                                            <i class="fas fa-sort-{{ $direction == 'asc' ? 'up' : 'down' }}"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>Total Transaksi</th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'class', 'direction' => ($sort == 'class' && $direction == 'asc') ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark">
                                        Klasifikasi
                                        @if($sort == 'class')
                                            <i class="fas fa-sort-{{ $direction == 'asc' ? 'up' : 'down' }}"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pelanggan as $p)
                                <tr>
                                    <td class="ps-4 fw-bold text-muted">{{ $p->nik }}</td>
                                    <td>{{ $p->nama }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $p->tgl_kunjungan }}</span></td>
                                    <td>Rp {{ number_format($p->total) }}</td>
                                    <td>
                                        @if ($p->class == 'Platinum')
                                            <span class="badge bg-dark px-3 py-2">PLATINUM</span>
                                        @elseif($p->class == 'Gold')
                                            <span class="badge bg-warning text-dark px-3 py-2">GOLD</span>
                                        @elseif($p->class == 'Silver')
                                            <span class="badge bg-secondary px-3 py-2">SILVER</span>
                                        @else
                                            <span class="badge bg-light text-dark border px-3 py-2">BASIC</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('pelanggan.show', $p->id) }}" class="btn btn-sm btn-primary me-1" title="Detail"><i class="fas fa-eye"></i></a>
                                        @if(Auth::user()->role?->name === 'Admin')
                                            <a href="{{ route('pelanggan.edit', $p->id) }}" class="btn btn-sm btn-info text-white me-1" title="Edit"><i class="fas fa-edit"></i></a>
                                            <form action="{{ route('pelanggan.destroy', $p->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                                            </form>
                                        @endif
                                    </td>

                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Tidak ada data pelanggan yang cocok dengan pencarian.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($pelanggan instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted small">
                            Menampilkan {{ $pelanggan->firstItem() }} - {{ $pelanggan->lastItem() }} dari {{ $pelanggan->total() }} data
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                {{-- First Page --}}
                                <li class="page-item {{ $pelanggan->currentPage() == 1 ? 'disabled' : '' }}">
                                    <a class="page-link" href="{{ $pelanggan->url(1) }}" tabindex="-1" aria-disabled="true">
                                        <i class="fas fa-angle-double-left"></i> First
                                    </a>
                                </li>
                                
                                {{-- Previous Page --}}
                                <li class="page-item {{ $pelanggan->previousPageUrl() ? '' : 'disabled' }}">
                                    <a class="page-link" href="{{ $pelanggan->previousPageUrl() }}" tabindex="-1" aria-disabled="true">
                                        <i class="fas fa-angle-left"></i> Prev
                                    </a>
                                </li>

                                {{-- Page Numbers --}}
                                @foreach ($pelanggan->getUrlRange(max(1, $pelanggan->currentPage() - 2), min($pelanggan->lastPage(), $pelanggan->currentPage() + 2)) as $page => $url)
                                    <li class="page-item {{ $page == $pelanggan->currentPage() ? 'active' : '' }}">
                                        <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                    </li>
                                @endforeach

                                {{-- Next Page --}}
                                <li class="page-item {{ $pelanggan->nextPageUrl() ? '' : 'disabled' }}">
                                    <a class="page-link" href="{{ $pelanggan->nextPageUrl() }}">
                                        Next <i class="fas fa-angle-right"></i>
                                    </a>
                                </li>

                                {{-- Last Page --}}
                                <li class="page-item {{ $pelanggan->currentPage() == $pelanggan->lastPage() ? 'disabled' : '' }}">
                                    <a class="page-link" href="{{ $pelanggan->url($pelanggan->lastPage()) }}">
                                        Last <i class="fas fa-angle-double-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                @endif
            </div>
        </div>
    @else
        <!-- Tabel Daftar Pelanggan (Filter Mode) -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'nik', 'direction' => ($sort == 'nik' && $direction == 'asc') ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark">
                                        NIK
                                        @if($sort == 'nik')
                                            <i class="fas fa-sort-{{ $direction == 'asc' ? 'up' : 'down' }}"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'nama', 'direction' => ($sort == 'nama' && $direction == 'asc') ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark">
                                        Nama
                                        @if($sort == 'nama')
                                            <i class="fas fa-sort-{{ $direction == 'asc' ? 'up' : 'down' }}"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'tgl_kunjungan', 'direction' => ($sort == 'tgl_kunjungan' && $direction == 'asc') ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark">
                                        Tanggal Kunjungan
                                        @if($sort == 'tgl_kunjungan')
                                            <i class="fas fa-sort-{{ $direction == 'asc' ? 'up' : 'down' }}"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>Total Transaksi</th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'class', 'direction' => ($sort == 'class' && $direction == 'asc') ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark">
                                        Klasifikasi
                                        @if($sort == 'class')
                                            <i class="fas fa-sort-{{ $direction == 'asc' ? 'up' : 'down' }}"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pelanggan as $p)
                                <tr>
                                    <td class="ps-4 fw-bold text-muted">{{ $p->nik }}</td>
                                    <td>{{ $p->nama }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $p->tgl_kunjungan }}</span></td>
                                    <td>Rp {{ number_format($p->total) }}</td>
                                    <td>
                                        @if ($p->class == 'Platinum')
                                            <span class="badge bg-dark px-3 py-2">PLATINUM</span>
                                        @elseif($p->class == 'Gold')
                                            <span class="badge bg-warning text-dark px-3 py-2">GOLD</span>
                                        @elseif($p->class == 'Silver')
                                            <span class="badge bg-secondary px-3 py-2">SILVER</span>
                                        @else
                                            <span class="badge bg-light text-dark border px-3 py-2">BASIC</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('pelanggan.show', $p->id) }}" class="btn btn-sm btn-primary me-1" title="Detail"><i class="fas fa-eye"></i></a>
                                        @if(Auth::user()->role?->name === 'Admin')
                                            <a href="{{ route('pelanggan.edit', $p->id) }}" class="btn btn-sm btn-info text-white me-1" title="Edit"><i class="fas fa-edit"></i></a>
                                            <form action="{{ route('pelanggan.destroy', $p->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger" title="Hapus"><i class="fas fa-trash"></i></button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Tidak ada data pelanggan untuk periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($pelanggan instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted small">
                            Menampilkan {{ $pelanggan->firstItem() }} - {{ $pelanggan->lastItem() }} dari {{ $pelanggan->total() }} data
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                {{-- First Page --}}
                                <li class="page-item {{ $pelanggan->currentPage() == 1 ? 'disabled' : '' }}">
                                    <a class="page-link" href="{{ $pelanggan->url(1) }}" tabindex="-1" aria-disabled="true">
                                        <i class="fas fa-angle-double-left"></i> First
                                    </a>
                                </li>
                                
                                {{-- Previous Page --}}
                                <li class="page-item {{ $pelanggan->previousPageUrl() ? '' : 'disabled' }}">
                                    <a class="page-link" href="{{ $pelanggan->previousPageUrl() }}" tabindex="-1" aria-disabled="true">
                                        <i class="fas fa-angle-left"></i> Prev
                                    </a>
                                </li>

                                {{-- Page Numbers --}}
                                @foreach ($pelanggan->getUrlRange(max(1, $pelanggan->currentPage() - 2), min($pelanggan->lastPage(), $pelanggan->currentPage() + 2)) as $page => $url)
                                    <li class="page-item {{ $page == $pelanggan->currentPage() ? 'active' : '' }}">
                                        <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                    </li>
                                @endforeach

                                {{-- Next Page --}}
                                <li class="page-item {{ $pelanggan->nextPageUrl() ? '' : 'disabled' }}">
                                    <a class="page-link" href="{{ $pelanggan->nextPageUrl() }}">
                                        Next <i class="fas fa-angle-right"></i>
                                    </a>
                                </li>

                                {{-- Last Page --}}
                                <li class="page-item {{ $pelanggan->currentPage() == $pelanggan->lastPage() ? 'disabled' : '' }}">
                                    <a class="page-link" href="{{ $pelanggan->url($pelanggan->lastPage()) }}">
                                        Last <i class="fas fa-angle-double-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                @endif
            </div>
        </div>
    @endif
@endsection

@section('scripts')
<script>
// Filter form handling
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.querySelector('select[name="type"]');
    const bulanContainer = document.getElementById('bulan-container');
    const tahunContainer = document.getElementById('tahun-container');
    
    if (typeSelect) {
        typeSelect.addEventListener('change', function() {
            // Handle Bulan visibility
            if (bulanContainer) {
                if (this.value === 'pertahun' || this.value === 'semua' || this.value === '') {
                    bulanContainer.style.display = 'none';
                } else {
                    bulanContainer.style.display = 'block';
                }
            }
            
            // Handle Tahun visibility - hide only for 'semua' or empty
            if (tahunContainer) {
                if (this.value === 'semua' || this.value === '') {
                    tahunContainer.style.display = 'none';
                } else {
                    tahunContainer.style.display = 'block';
                }
            }
        });
        
        // Initial check
        if (bulanContainer) {
            if (typeSelect.value === 'pertahun' || typeSelect.value === 'semua' || typeSelect.value === '') {
                bulanContainer.style.display = 'none';
            }
        }
        
        if (tahunContainer) {
            if (typeSelect.value === 'semua' || typeSelect.value === '') {
                tahunContainer.style.display = 'none';
            }
        }
    }
});

// Import form handling
document.addEventListener('DOMContentLoaded', function() {
    const importForm = document.getElementById('importForm');
    const importBtn = document.getElementById('importBtn');
    const fileInput = document.getElementById('fileInput');
    const btnText = document.getElementById('btnText');
    const btnLoading = document.getElementById('btnLoading');
    
    if (importForm && importBtn && fileInput) {
        importForm.addEventListener('submit', function(e) {
            const file = fileInput.files[0];
            
            // Validation
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
            
            // Show loading
            if (btnText && btnLoading) {
                importBtn.disabled = true;
                btnText.style.display = 'none';
                btnLoading.style.display = 'inline';
            }
            
            // Form will submit normally
            console.log('Form submitted, loading state activated');
        });
        
        // Reset validation on change
        fileInput.addEventListener('change', function() {
            this.classList.remove('is-invalid');
        });
    } else {
        console.error('Import form elements not found');
    }
});
</script>
@endsection
