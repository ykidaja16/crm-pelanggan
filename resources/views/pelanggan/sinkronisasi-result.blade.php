@extends('layouts.main')

@section('title', 'Hasil Sinkronisasi Data Pelanggan')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h5 class="fw-semibold mb-0 text-primary">
                <i class="fas fa-sync-alt me-2"></i>Hasil Sinkronisasi Data Pelanggan
            </h5>
            <small class="text-muted">Dijalankan pada: {{ \Carbon\Carbon::parse($syncAt)->format('d-m-Y H:i:s') }}</small>
        </div>
        <a href="{{ route('pelanggan.sinkronisasi') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>Kembali
        </a>
    </div>

    {{-- Summary Card --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-4 text-center">
            <div class="display-6 fw-bold text-primary mb-1">{{ $totalSynced }}</div>
            <div class="text-muted">Total Pelanggan Berhasil Disinkronisasi</div>
            <div class="row justify-content-center mt-3 g-3">
                <div class="col-auto">
                    <div class="border rounded-3 px-4 py-2 bg-light">
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger me-1">Prioritas</span>
                        <i class="fas fa-arrow-right text-muted mx-1"></i>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success me-1">Loyal</span>
                        <span class="fw-semibold ms-1">{{ $prioritasToLoyal->count() }} data</span>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="border rounded-3 px-4 py-2 bg-light">
                        <span class="badge bg-success bg-opacity-10 text-success border border-success me-1">Loyal</span>
                        <i class="fas fa-arrow-right text-muted mx-1"></i>
                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning me-1">Potensial</span>
                        <span class="fw-semibold ms-1">{{ $loyalToPotensial->count() }} data</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Prioritas → Loyal --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <h6 class="mb-0 fw-semibold">
                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger me-1">Prioritas</span>
                <i class="fas fa-arrow-right text-muted mx-1"></i>
                <span class="badge bg-success bg-opacity-10 text-success border border-success me-1">Loyal</span>
                <span class="ms-2 text-muted fw-normal">({{ $prioritasToLoyal->count() }} data)</span>
            </h6>
            <div class="d-flex gap-2">
                @if($prioritasToLoyal->count() > 0)
                <a href="{{ route('pelanggan.sinkronisasi.export', 'prioritas-loyal') }}?sync_at={{ urlencode($syncAt) }}"
                   class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel me-1"></i>Export Excel
                </a>
                @endif
                <button class="btn btn-outline-primary btn-sm" type="button"
                        data-bs-toggle="collapse" data-bs-target="#detailP2L"
                        aria-expanded="false">
                    <i class="fas fa-eye me-1"></i>Lihat Detail
                </button>
            </div>
        </div>
        <div class="collapse" id="detailP2L">
            <div class="card-body p-0">
                @if($prioritasToLoyal->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle small">
                        <thead class="table-light">
                            <tr>
                                <th class="px-3 py-2">No</th>
                                <th class="py-2">PID</th>
                                <th class="py-2">Nama Pasien</th>
                                <th class="py-2">NIK</th>
                                <th class="py-2">Cabang</th>
                                <th class="py-2">No Telp</th>
                                <th class="py-2">DOB</th>
                                <th class="py-2">Alamat</th>
                                <th class="py-2 text-center">Kunjungan</th>
                                <th class="py-2 text-center">Kunjungan Terakhir</th>
                                <th class="py-2 text-end">Total Biaya</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($prioritasToLoyal as $i => $history)
                            @php $p = $history->pelanggan; @endphp
                            <tr>
                                <td class="px-3">{{ $i + 1 }}</td>
                                <td>
                                    @if($p)
                                    <a href="{{ route('pelanggan.show', $p->id) }}" class="text-decoration-none">{{ $p->pid }}</a>
                                    @else -
                                    @endif
                                </td>
                                <td>{{ $p?->nama ?? '-' }}</td>
                                <td>{{ $p?->nik ?? '-' }}</td>
                                <td>{{ $p?->cabang?->nama ?? '-' }}</td>
                                <td>{{ $p?->no_telp ?? '-' }}</td>
                                <td>{{ $p?->dob?->format('d-m-Y') ?? '-' }}</td>
                                <td>{{ $p?->alamat ?? '-' }}</td>
                                <td class="text-center">{{ $p?->total_kedatangan ?? 0 }}</td>
                                <td class="text-center">{{ $p?->latestKunjungan?->tanggal_kunjungan?->format('d-m-Y') ?? '-' }}</td>
                                <td class="text-end">{{ number_format($p?->total_biaya ?? 0, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted small">
                    <i class="fas fa-inbox fa-2x mb-2 opacity-50"></i>
                    <p class="mb-0">Tidak ada data.</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Loyal → Potensial --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <h6 class="mb-0 fw-semibold">
                <span class="badge bg-success bg-opacity-10 text-success border border-success me-1">Loyal</span>
                <i class="fas fa-arrow-right text-muted mx-1"></i>
                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning me-1">Potensial</span>
                <span class="ms-2 text-muted fw-normal">({{ $loyalToPotensial->count() }} data)</span>
            </h6>
            <div class="d-flex gap-2">
                @if($loyalToPotensial->count() > 0)
                <a href="{{ route('pelanggan.sinkronisasi.export', 'loyal-potensial') }}?sync_at={{ urlencode($syncAt) }}"
                   class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel me-1"></i>Export Excel
                </a>
                @endif
                <button class="btn btn-outline-primary btn-sm" type="button"
                        data-bs-toggle="collapse" data-bs-target="#detailL2P"
                        aria-expanded="false">
                    <i class="fas fa-eye me-1"></i>Lihat Detail
                </button>
            </div>
        </div>
        <div class="collapse" id="detailL2P">
            <div class="card-body p-0">
                @if($loyalToPotensial->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle small">
                        <thead class="table-light">
                            <tr>
                                <th class="px-3 py-2">No</th>
                                <th class="py-2">PID</th>
                                <th class="py-2">Nama Pasien</th>
                                <th class="py-2">NIK</th>
                                <th class="py-2">Cabang</th>
                                <th class="py-2">No Telp</th>
                                <th class="py-2">DOB</th>
                                <th class="py-2">Alamat</th>
                                <th class="py-2 text-center">Kunjungan</th>
                                <th class="py-2 text-center">Kunjungan Terakhir</th>
                                <th class="py-2 text-end">Total Biaya</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($loyalToPotensial as $i => $history)
                            @php $p = $history->pelanggan; @endphp
                            <tr>
                                <td class="px-3">{{ $i + 1 }}</td>
                                <td>
                                    @if($p)
                                    <a href="{{ route('pelanggan.show', $p->id) }}" class="text-decoration-none">{{ $p->pid }}</a>
                                    @else -
                                    @endif
                                </td>
                                <td>{{ $p?->nama ?? '-' }}</td>
                                <td>{{ $p?->nik ?? '-' }}</td>
                                <td>{{ $p?->cabang?->nama ?? '-' }}</td>
                                <td>{{ $p?->no_telp ?? '-' }}</td>
                                <td>{{ $p?->dob?->format('d-m-Y') ?? '-' }}</td>
                                <td>{{ $p?->alamat ?? '-' }}</td>
                                <td class="text-center">{{ $p?->total_kedatangan ?? 0 }}</td>
                                <td class="text-center">{{ $p?->latestKunjungan?->tanggal_kunjungan?->format('d-m-Y') ?? '-' }}</td>
                                <td class="text-end">{{ number_format($p?->total_biaya ?? 0, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted small">
                    <i class="fas fa-inbox fa-2x mb-2 opacity-50"></i>
                    <p class="mb-0">Tidak ada data.</p>
                </div>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection
