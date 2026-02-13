@extends('layouts.main')

@section('title', 'Detail Pelanggan - Medical Lab CRM')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-primary mb-0">Detail Pelanggan</h3>
        <a href="{{ route('dashboard') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <!-- Customer Info Card -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-user text-primary me-2"></i> Data Pelanggan</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td class="text-muted fw-bold" style="width: 150px;">NIK</td>
                            <td>: {{ $pelanggan->nik }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Nama</td>
                            <td>: {{ $pelanggan->nama }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Alamat</td>
                            <td>: {{ $pelanggan->alamat ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td class="text-muted fw-bold" style="width: 150px;">Klasifikasi</td>
                            <td>: 
                                @if ($pelanggan->class == 'Platinum')
                                    <span class="badge bg-dark px-3 py-2">PLATINUM</span>
                                @elseif($pelanggan->class == 'Gold')
                                    <span class="badge bg-warning text-dark px-3 py-2">GOLD</span>
                                @elseif($pelanggan->class == 'Silver')
                                    <span class="badge bg-secondary px-3 py-2">SILVER</span>
                                @else
                                    <span class="badge bg-light text-dark border px-3 py-2">BASIC</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Total Transaksi</td>
                            <td>: <strong>Rp {{ number_format($totalTransaksi) }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold">Total Kunjungan</td>
                            <td>: {{ $kunjungans->count() }} kali</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Visit History Card -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-history text-info me-2"></i> Riwayat Kunjungan</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">No</th>
                            <th>Tanggal Kunjungan</th>
                            <th>Biaya</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($kunjungans as $index => $k)
                            <tr>
                                <td class="ps-4">{{ $index + 1 }}</td>
                                <td>{{ \Carbon\Carbon::parse($k->tanggal_kunjungan)->format('d-m-Y') }}</td>
                                <td>Rp {{ number_format($k->biaya) }}</td>
                                <td><span class="badge bg-success">Selesai</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Tidak ada riwayat kunjungan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
