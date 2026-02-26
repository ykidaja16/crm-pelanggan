@extends('layouts.main')

@section('title', 'Detail Pelanggan - Medical Lab CRM')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-primary mb-0 fw-semibold">Detail Pelanggan</h3>
        <a href="{{ route('pelanggan.index') }}" class="btn btn-outline-secondary btn-lg">
            <i class="fas fa-arrow-left me-2"></i> Kembali
        </a>
    </div>

    <!-- Customer Info Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="mb-0 fw-semibold text-primary">
                <i class="fas fa-user me-2"></i>Data Pelanggan
            </h5>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="text-muted small fw-medium text-uppercase">PID</label>
                        <div class="fs-5 fw-semibold">
                            <code class="bg-light px-2 py-1 rounded">{{ $pelanggan->pid }}</code>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-medium text-uppercase">Nama Lengkap</label>
                        <div class="fs-5 fw-semibold">{{ $pelanggan->nama }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-medium text-uppercase">Alamat</label>
                        <div class="fs-6">{{ $pelanggan->alamat ?? '-' }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-medium text-uppercase">Kota</label>
                        <div class="fs-6">{{ $pelanggan->kota ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="text-muted small fw-medium text-uppercase">Klasifikasi</label>
                        <div class="fs-5">
                            @if ($pelanggan->class == 'Prioritas')
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-3 py-2 fs-6">PRIORITAS</span>
                            @elseif($pelanggan->class == 'Loyal')
                                <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2 fs-6">LOYAL</span>
                            @elseif($pelanggan->class == 'Potensial')
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-3 py-2 fs-6">POTENSIAL</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary px-3 py-2 fs-6">-</span>
                            @endif
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-medium text-uppercase">Total Transaksi</label>
                        <div class="fs-4 fw-bold text-success">Rp {{ number_format($totalTransaksi, 0, ',', '.') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-medium text-uppercase">Total Kunjungan</label>
                        <div class="fs-5 fw-semibold">
                            <span class="badge bg-info bg-opacity-10 text-info border border-info px-3 py-2">{{ $kunjungans->count() }} kali</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-medium text-uppercase">Cabang</label>
                        <div class="fs-6">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary">{{ $pelanggan->cabang?->nama ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Class History Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold text-warning">
                <i class="fas fa-chart-line me-2"></i>Riwayat Perubahan Kelas
            </h5>
            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-3 py-2">
                {{ $classHistories->count() }} Perubahan
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-3">No</th>
                            <th class="py-3">Tanggal Perubahan</th>
                            <th class="py-3">Perubahan Kelas</th>
                            <th class="py-3">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($classHistories as $index => $history)
                            <tr>
                                <td class="px-4">{{ $index + 1 }}</td>
                                <td>{{ $history->changed_at->format('d-m-Y H:i') }}</td>
                                <td>
                                    @if ($history->previous_class)
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">{{ $history->previous_class }}</span>
                                        <i class="fas fa-arrow-right mx-2 text-muted"></i>
                                    @else
                                        <span class="text-muted">-</span>
                                        <i class="fas fa-arrow-right mx-2 text-muted"></i>
                                    @endif
                                    @if ($history->new_class == 'Prioritas')
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">PRIORITAS</span>
                                    @elseif($history->new_class == 'Loyal')
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success">LOYAL</span>
                                    @elseif($history->new_class == 'Potensial')
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">POTENSIAL</span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">{{ $history->new_class }}</span>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $history->reason }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-3 text-secondary opacity-50"></i>
                                    <p class="mb-0">Belum ada riwayat perubahan kelas.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Visit History Card -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold text-info">
                <i class="fas fa-history me-2"></i>Riwayat Kunjungan
            </h5>
            <span class="badge bg-info bg-opacity-10 text-info border border-info px-3 py-2">
                {{ $kunjungans->count() }} Kunjungan
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-3">No</th>
                            <th class="py-3">Tanggal Kunjungan</th>
                            <th class="py-3">Biaya</th>
                            <th class="py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($kunjungans as $index => $k)
                            <tr>
                                <td class="px-4">{{ $index + 1 }}</td>
                                <td>{{ \Carbon\Carbon::parse($k->tanggal_kunjungan)->format('d-m-Y') }}</td>
                                <td class="fw-semibold">Rp {{ number_format($k->biaya, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success">Selesai</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-3 text-secondary opacity-50"></i>
                                    <p class="mb-0">Tidak ada riwayat kunjungan.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
