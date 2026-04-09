@extends('layouts.main')

@section('title', 'Birthday Reminder - Medical Lab CRM')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="text-primary mb-0 fw-semibold">
            <i class="fas fa-birthday-cake me-2"></i>Birthday Reminder
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
            <form method="GET" action="{{ route('special-day.birthday') }}" class="row g-2 align-items-end" id="filterForm">

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
                        <option value="Prioritas" {{ request('kelas') === 'Prioritas' ? 'selected' : '' }}>Prioritas</option>
                        <option value="Loyal"     {{ request('kelas') === 'Loyal'     ? 'selected' : '' }}>Loyal</option>
                        <option value="Potensial" {{ request('kelas') === 'Potensial' ? 'selected' : '' }}>Potensial</option>
                    </select>
                </div>

                <!-- Filter Tanggal Mulai -->
                <div class="col-md-2">
                    <label class="form-label fw-medium small">Tanggal Mulai</label>
                    <input type="date" name="tgl_mulai" class="form-control form-control-sm"
                           value="{{ request('tgl_mulai', $tglMulai) }}">
                </div>

                <!-- Filter Tanggal Akhir -->
                <div class="col-md-2">
                    <label class="form-label fw-medium small">Tanggal Akhir</label>
                    <input type="date" name="tgl_akhir" class="form-control form-control-sm"
                           value="{{ request('tgl_akhir', $tglAkhir) }}">
                </div>

                <!-- Tombol -->
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-3">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                    <a href="{{ route('special-day.birthday') }}" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="fas fa-times me-1"></i>Reset
                    </a>
                    <a href="{{ route('special-day.birthday.export') }}?{{ http_build_query(request()->all()) }}"
                       class="btn btn-success btn-sm px-3">
                        <i class="fas fa-file-excel me-1"></i>Export
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Info Range -->
    <div class="alert alert-info border-0 shadow-sm py-2 mb-3">
        <i class="fas fa-info-circle me-2"></i>
        Menampilkan pelanggan yang <strong>ulang tahun</strong> antara
        <strong>{{ \Carbon\Carbon::parse($tglMulai)->format('d M Y') }}</strong>
        s.d.
        <strong>{{ \Carbon\Carbon::parse($tglAkhir)->format('d M Y') }}</strong>
        (berdasarkan bulan-hari, tanpa memperhatikan tahun lahir)
    </div>

    <!-- Tabel -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            @if($pelanggans->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-birthday-cake fa-3x mb-3 opacity-25"></i>
                    <p class="mb-0">Tidak ada pelanggan yang ulang tahun pada rentang tanggal ini.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width:50px">No</th>
                                <th>PID</th>
                                <th>Nama</th>
                                <th>Cabang</th>
                                <th class="text-center">Tgl Lahir</th>
                                <th class="text-center">Kelas</th>
                                <th>No. Telp</th>
                                <th class="text-center">Kunjungan Terakhir</th>
                                {{-- <th class="text-center">Kelompok</th> --}}
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pelanggans as $i => $p)
                            <tr>
                                <td class="text-center text-muted small">
                                    {{ ($pelanggans->currentPage() - 1) * $pelanggans->perPage() + $i + 1 }}
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary font-monospace small">
                                        {{ $p->pid }}
                                    </span>
                                </td>
                                <td class="fw-medium">{{ $p->nama }}</td>
                                <td class="small text-muted">{{ $p->cabang?->nama ?? '-' }}</td>
                                <td class="text-center small">
                                    @if($p->dob)
                                        <span class="text-danger fw-semibold">
                                            <i class="fas fa-birthday-cake me-1"></i>
                                            {{ $p->dob->format('d M') }}
                                        </span>
                                        <div class="text-muted" style="font-size:0.75rem">{{ $p->dob->format('Y') }}</div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @php
                                        $kelasColor = match($p->class) {
                                            'Prioritas' => 'danger',
                                            'Loyal'     => 'warning',
                                            'Potensial' => 'info',
                                            default     => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $kelasColor }} bg-opacity-15 text-{{ $kelasColor }} border border-{{ $kelasColor }} small text-dark">
                                        {{ $p->class ?? '-' }}
                                    </span>
                                </td>
                                <td class="small">{{ $p->no_telp ?? '-' }}</td>
                                <td class="text-center small">
                                    {{ $p->kunjungans_max_tanggal_kunjungan
                                        ? \Carbon\Carbon::parse($p->kunjungans_max_tanggal_kunjungan)->format('d-m-Y')
                                        : '-' }}
                                </td>
                                {{-- <td class="text-center small">
                                    {{ $p->latestKunjungan?->kelompokPelanggan?->nama ?? '-' }}
                                </td> --}}
                                <td class="text-center">
                                    <button type="button"
                                            class="btn btn-outline-primary btn-sm py-0 px-2"
                                            data-bs-toggle="modal"
                                            data-bs-target="#detailModal{{ $p->id }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
                    <small class="text-muted">
                        Menampilkan {{ $pelanggans->firstItem() }}–{{ $pelanggans->lastItem() }}
                        dari {{ $pelanggans->total() }} pelanggan
                    </small>
                    {{ $pelanggans->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Detail Modals --}}
@foreach($pelanggans as $p)
<div class="modal fade" id="detailModal{{ $p->id }}" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title mb-0">
                    <i class="fas fa-birthday-cake me-2"></i>Detail Pelanggan
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-id-card me-1"></i>PID</div>
                            <div class="small fw-medium font-monospace">{{ $p->pid }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-user me-1"></i>Nama</div>
                            <div class="small fw-medium">{{ $p->nama }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-birthday-cake me-1"></i>Tanggal Lahir</div>
                            <div class="small fw-semibold text-danger">
                                {{ $p->dob ? $p->dob->format('d M Y') : '-' }}
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-phone me-1"></i>No. Telp</div>
                            <div class="small">{{ $p->no_telp ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-hospital me-1"></i>Cabang</div>
                            <div class="small">{{ $p->cabang?->nama ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-map-marker-alt me-1"></i>Kota</div>
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
                    {{-- @if($p->latestKunjungan?->kelompokPelanggan)
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-users me-1"></i>Kelompok</div>
                            <div class="small fw-medium">{{ $p->latestKunjungan->kelompokPelanggan->nama }}</div>
                        </div>
                    </div>
                    @endif --}}
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
