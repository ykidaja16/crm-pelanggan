@extends('layouts.main')

@section('title', 'Search by Phone')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="text-primary mb-0 fw-semibold">
        <i class="fas fa-phone-square-alt me-2"></i>Search by Phone
    </h4>
</div>

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Upload Form --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="mb-0 fw-semibold text-primary">
            <i class="fas fa-upload me-2"></i>Upload File Excel
        </h6>
    </div>
    <div class="card-body">
        <div class="alert alert-info mb-3">
            <i class="fas fa-info-circle me-2"></i>
            Upload file Excel/CSV dengan 2 kolom: <strong>Nama</strong> dan <strong>Nomer Telepon</strong>.
            Pencarian dilakukan ke seluruh cabang.
        </div>

        <form action="{{ route('pelanggan.search-by-phone.search') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="file" class="form-label fw-semibold">File Excel / CSV</label>
                    <input type="file" name="file" id="file"
                           class="form-control @error('file') is-invalid @enderror"
                           accept=".xlsx,.xls,.csv,.txt" required>
                    @error('file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Format: xlsx, xls, csv, txt. Maks 10 MB.</div>
                </div>
                <div class="col-md-auto d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-search me-1"></i>Proses Pencarian
                    </button>
                    <a href="{{ route('pelanggan.search-by-phone.template') }}" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-download me-1"></i>Download Template
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

@if($hasResults)

@php
    $totalFound         = $foundPage->total();
    $totalNotFound      = $notFoundPage->total();
    $accessibleCabangIds = Auth::user()->getAccessibleCabangIds(); // kosong = akses semua
@endphp

{{-- Ringkasan --}}
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm bg-success bg-opacity-10">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success bg-opacity-25 p-3">
                    <i class="fas fa-check-circle text-success fa-lg"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-success">{{ number_format($totalFound) }}</div>
                    <div class="small text-muted">Nomor Ditemukan</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm bg-danger bg-opacity-10">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-circle bg-danger bg-opacity-25 p-3">
                    <i class="fas fa-times-circle text-danger fa-lg"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold text-danger">{{ number_format($totalNotFound) }}</div>
                    <div class="small text-muted">Nomor Tidak Ditemukan</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══ Tabel: Ditemukan ═══ --}}
@if($totalFound > 0)
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-semibold text-success">
            <i class="fas fa-check-circle me-2"></i>Data Pelanggan Ditemukan
            <span class="badge bg-success ms-1">{{ number_format($totalFound) }}</span>
        </h6>
        <a href="{{ route('pelanggan.search-by-phone.export-found') }}" class="btn btn-success btn-sm">
            <i class="fas fa-file-excel me-1"></i>Export Excel
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle small">
                <thead class="table-light">
                    <tr>
                        <th class="px-3" style="width:50px">No</th>
                        <th>PID</th>
                        <th>Cabang</th>
                        <th>Nama (DB)</th>
                        <th>Nomer Telepon</th>
                        <th>Alamat</th>
                        <th>Kunjungan Terakhir</th>
                        <th class="text-center">Total Kedatangan</th>
                        <th>Kelas</th>
                        <th>Nama di File Excel</th>
                    </tr>
                </thead>
                <tbody>
                    @php $rowNo = $foundRecordOffset + 1; @endphp
                    @foreach($foundPage as $item)
                        @foreach($item['records'] as $rec)
                        @php
                            $canAccess = empty($accessibleCabangIds)
                                || in_array($rec['cabang_id'], $accessibleCabangIds);
                            $kelasColor = match($rec['class']) {
                                'Prioritas' => 'danger',
                                'Loyal'     => 'warning',
                                'Potensial' => 'info',
                                default     => 'secondary',
                            };
                        @endphp
                        <tr>
                            <td class="px-3 text-muted">{{ $rowNo++ }}</td>
                            <td>
                                @if($canAccess)
                                    <a href="{{ route('pelanggan.show', $rec['id']) }}" class="text-decoration-none fw-semibold">{{ $rec['pid'] }}</a>
                                @else
                                    <span class="text-muted">{{ $rec['pid'] }}</span>
                                @endif
                            </td>
                            <td>{{ $rec['cabang'] }}</td>
                            <td>{{ $rec['nama_db'] }}</td>
                            <td>{{ $rec['no_telp'] ?? '-' }}</td>
                            <td class="text-truncate" style="max-width:160px" title="{{ $rec['alamat'] }}">{{ $rec['alamat'] }}</td>
                            <td>{{ $rec['latest_visit'] ?: '-' }}</td>
                            <td class="text-center">{{ number_format($rec['total_kedatangan']) }}</td>
                            <td><span class="badge bg-{{ $kelasColor }}">{{ $rec['class'] }}</span></td>
                            <td class="fw-semibold text-primary">{{ $rec['nama_excel'] }}</td>
                        </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @if($foundPage->hasPages())
    <div class="card-footer bg-white border-top py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted">
            Menampilkan {{ $foundPage->firstItem() }}–{{ $foundPage->lastItem() }} dari {{ number_format($foundPage->total()) }} data
        </small>
        {{ $foundPage->appends(request()->except('found_page'))->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>
@endif

{{-- ═══ Tabel: Tidak Ditemukan ═══ --}}
@if($totalNotFound > 0)
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-semibold text-danger">
            <i class="fas fa-times-circle me-2"></i>Nomor Tidak Ditemukan di Database
            <span class="badge bg-danger ms-1">{{ number_format($totalNotFound) }}</span>
        </h6>
        <a href="{{ route('pelanggan.search-by-phone.export-not-found') }}" class="btn btn-danger btn-sm">
            <i class="fas fa-file-excel me-1"></i>Export Excel
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle small">
                <thead class="table-light">
                    <tr>
                        <th class="px-3" style="width:50px">No</th>
                        <th>Nama (dari File Excel)</th>
                        <th>Nomer Telepon (dari File Excel)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($notFoundPage as $i => $item)
                    <tr>
                        <td class="px-3 text-muted">{{ $notFoundOffset + $i + 1 }}</td>
                        <td>{{ $item['nama'] }}</td>
                        <td>{{ $item['no_telp_raw'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @if($notFoundPage->hasPages())
    <div class="card-footer bg-white border-top py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted">
            Menampilkan {{ $notFoundPage->firstItem() }}–{{ $notFoundPage->lastItem() }} dari {{ number_format($notFoundPage->total()) }} data
        </small>
        {{ $notFoundPage->appends(request()->except('nf_page'))->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>
@endif

@endif
@endsection
