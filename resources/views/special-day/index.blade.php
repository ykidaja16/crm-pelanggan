@extends('layouts.main')

@section('title', 'Special Day Member - Medical Lab CRM')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="text-primary mb-0 fw-semibold">
            <i class="fas fa-birthday-cake me-2"></i>Special Day Member
        </h4>
        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-3 py-2">
            Total: {{ $pelanggans->total() }} pelanggan
        </span>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('special-day.index') }}" class="row g-2 align-items-end" id="filterForm">

                <!-- Filter Tipe -->
                <div class="col-md-3">
                    <label class="form-label fw-medium small">Tipe Filter</label>
                    <select name="filter" class="form-select form-select-sm">
                        <option value="birthday"       {{ $filter === 'birthday'       ? 'selected' : '' }}>🎂 Ulang Tahun Hari Ini</option>
                        <option value="birthday_month" {{ $filter === 'birthday_month' ? 'selected' : '' }}>🗓️ Ulang Tahun Bulan Ini</option>
                        <option value="anniversary"    {{ $filter === 'anniversary'    ? 'selected' : '' }}>📅 1 Tahun Kunjungan Terakhir</option>
                    </select>
                </div>

                <!-- Filter Cabang -->
                <div class="col-md-3">
                    <label class="form-label fw-medium small">Cabang</label>
                    <select name="cabang_id" class="form-select form-select-sm">
                        <option value="">Semua Cabang</option>
                        @foreach($cabangs as $cabang)
                            <option value="{{ $cabang->id }}" {{ request('cabang_id') == $cabang->id ? 'selected' : '' }}>
                                {{ $cabang->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Kelas -->
                <div class="col-md-2">
                    <label class="form-label fw-medium small">Kelas</label>
                    <select name="kelas" class="form-select form-select-sm">
                        <option value="">Semua Kelas</option>
                        <option value="A" {{ request('kelas') === 'A' ? 'selected' : '' }}>Kelas A</option>
                        <option value="B" {{ request('kelas') === 'B' ? 'selected' : '' }}>Kelas B</option>
                        <option value="C" {{ request('kelas') === 'C' ? 'selected' : '' }}>Kelas C</option>
                        <option value="D" {{ request('kelas') === 'D' ? 'selected' : '' }}>Kelas D</option>
                    </select>
                </div>

                <!-- Tombol Aksi -->
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-3">
                        <i class="fas fa-filter me-1"></i>Terapkan
                    </button>
                    <a href="{{ route('special-day.index') }}" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="fas fa-times me-1"></i>Reset
                    </a>
                    {{-- Tombol Export Excel --}}
                    <a href="{{ route('special-day.export', request()->query()) }}"
                       class="btn btn-success btn-sm px-3 ms-auto">
                        <i class="fas fa-file-excel me-1"></i>Export Excel
                    </a>
                </div>

            </form>
        </div>
    </div>

    <!-- Tabel Data -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            @if($pelanggans->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-birthday-cake fa-3x mb-3 opacity-25"></i>
                    <p class="mb-0">Tidak ada data pelanggan untuk filter ini.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width:45px">No</th>
                                <th>PID</th>
                                <th>Nama</th>
                                <th>Cabang</th>
                                <th>No. Telepon</th>
                                @if($filter === 'birthday' || $filter === 'birthday_month')
                                    <th>Tanggal Lahir</th>
                                @else
                                    <th>Kunjungan Terakhir</th>
                                @endif
                                <th>Kelas</th>
                                <th>Tipe</th>
                                <th class="text-center" style="width:80px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pelanggans as $i => $p)
                            <tr>
                                <td class="ps-3 text-muted small">{{ $pelanggans->firstItem() + $i }}</td>
                                <td>
                                    <code class="bg-light px-2 py-1 rounded small">{{ $p->pid }}</code>
                                </td>
                                <td class="fw-medium">{{ $p->nama }}</td>
                                <td class="small text-muted">{{ $p->cabang?->nama ?? '-' }}</td>
                                <td class="small">{{ $p->no_telp ?? '-' }}</td>
                                @if($filter === 'birthday' || $filter === 'birthday_month')
                                    <td class="small">
                                        @if($p->dob)
                                            <span class="text-danger fw-medium">
                                                <i class="fas fa-birthday-cake me-1"></i>
                                                {{ \Carbon\Carbon::parse($p->dob)->format('d-m-Y') }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                @else
                                    <td class="small">
                                        @if($p->kunjungans_max_tanggal_kunjungan)
                                            <span class="text-info fw-medium">
                                                <i class="fas fa-calendar-check me-1"></i>
                                                {{ \Carbon\Carbon::parse($p->kunjungans_max_tanggal_kunjungan)->format('d-m-Y') }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                @endif
                                <td>
                                    @if($p->class)
                                        <span class="badge bg-secondary">{{ $p->class }}</span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($p->is_pelanggan_khusus)
                                        <span class="badge" style="background:#7c3aed;">
                                            <i class="fas fa-star me-1"></i>Khusus
                                        </span>
                                    @else
                                        <span class="badge bg-light text-secondary border">Biasa</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button type="button"
                                            class="btn btn-outline-primary btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalDetail{{ $p->id }}"
                                            title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($pelanggans->hasPages())
                    <div class="px-3 py-2 border-top">
                        {{ $pelanggans->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

{{-- ─── Modal Detail per Pelanggan ─────────────────────────────────────────── --}}
@foreach($pelanggans as $p)
<div class="modal fade" id="modalDetail{{ $p->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-semibold">
                    <i class="fas fa-user-circle me-2"></i>Detail Pelanggan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">

                {{-- Header nama & PID --}}
                <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-light rounded border">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold"
                         style="width:50px;height:50px;font-size:1.2rem;">
                        {{ strtoupper(substr($p->nama, 0, 1)) }}
                    </div>
                    <div>
                        <div class="fw-bold fs-5">{{ $p->nama }}</div>
                        <code class="bg-white px-2 py-1 rounded border small">{{ $p->pid }}</code>
                        @if($p->is_pelanggan_khusus)
                            <span class="badge ms-2" style="background:#7c3aed;">
                                <i class="fas fa-star me-1"></i>Pelanggan Khusus
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Grid info --}}
                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-hospital me-1"></i>Cabang</div>
                            <div class="small fw-medium">{{ $p->cabang?->nama ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-phone me-1"></i>No. Telepon</div>
                            <div class="small">{{ $p->no_telp ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-birthday-cake me-1"></i>Tanggal Lahir</div>
                            <div class="small fw-medium text-danger">
                                {{ $p->dob ? \Carbon\Carbon::parse($p->dob)->format('d-m-Y') : '-' }}
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-city me-1"></i>Kota</div>
                            <div class="small">{{ $p->kota ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-calendar-check me-1"></i>Kunjungan Terakhir</div>
                            <div class="small">
                                {{ $p->kunjungans_max_tanggal_kunjungan
                                    ? \Carbon\Carbon::parse($p->kunjungans_max_tanggal_kunjungan)->format('d-m-Y')
                                    : '-' }}
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-layer-group me-1"></i>Kelas</div>
                            <div class="small fw-medium">{{ $p->class ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-map-marker-alt me-1"></i>Alamat</div>
                            <div class="small">{{ $p->alamat ?? '-' }}</div>
                        </div>
                    </div>
                    @if($p->latestKunjungan?->kelompokPelanggan)
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-users me-1"></i>Kelompok</div>
                            <div class="small fw-medium">{{ $p->latestKunjungan->kelompokPelanggan->nama }}</div>
                        </div>
                    </div>
                    @endif
                </div>

            </div>
            <div class="modal-footer">
                <a href="{{ route('pelanggan.show', $p->id) }}" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-external-link-alt me-1"></i>Lihat Profil Lengkap
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endforeach

@endsection
