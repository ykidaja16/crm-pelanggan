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

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('special-day.index') }}" class="row g-2 align-items-end">

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
                        <option value="Prioritas" {{ request('kelas') === 'Prioritas' ? 'selected' : '' }}>Prioritas</option>
                        <option value="Loyal"     {{ request('kelas') === 'Loyal'     ? 'selected' : '' }}>Loyal</option>
                        <option value="Potensial" {{ request('kelas') === 'Potensial' ? 'selected' : '' }}>Potensial</option>
                    </select>
                </div>

                <!-- Buttons -->
                <div class="col-12 d-flex gap-2 mt-1">
                    <button type="submit" class="btn btn-primary btn-sm px-3">
                        <i class="fas fa-filter me-1"></i>Terapkan Filter
                    </button>
                    <a href="{{ route('special-day.index') }}" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="fas fa-undo me-1"></i>Reset Filter
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Info Banner -->
    @if($filter === 'birthday')
        <div class="alert alert-warning border-0 shadow-sm py-2 mb-3" role="alert">
            <i class="fas fa-birthday-cake me-2 text-warning"></i>
            <strong>Ulang Tahun Hari Ini</strong> — Menampilkan pelanggan yang berulang tahun pada
            <strong>{{ now()->translatedFormat('d F Y') }}</strong>.
        </div>
    @elseif($filter === 'birthday_month')
        <div class="alert alert-warning border-0 shadow-sm py-2 mb-3" role="alert">
            <i class="fas fa-calendar-alt me-2 text-warning"></i>
            <strong>Ulang Tahun Bulan Ini</strong> — Menampilkan pelanggan yang berulang tahun di bulan
            <strong>{{ now()->translatedFormat('F Y') }}</strong>.
        </div>
    @else
        <div class="alert alert-info border-0 shadow-sm py-2 mb-3" role="alert">
            <i class="fas fa-calendar-check me-2 text-info"></i>
            <strong>Anniversary Kunjungan</strong> — Menampilkan pelanggan yang kunjungan terakhirnya tepat
            <strong>1 tahun yang lalu ({{ now()->subYear()->format('d-m-Y') }})</strong>.
        </div>
    @endif

    <!-- Table Card -->
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width:45px;">No</th>
                        <th>PID</th>
                        <th>Nama Pasien</th>
                        <th>Cabang</th>
                        <th>No Telp</th>
                        <th>
                            @if($filter === 'anniversary')
                                Kunjungan Terakhir
                            @else
                                DOB
                            @endif
                        </th>
                        <th>Kelas</th>
                        <th>Tipe Pelanggan</th>
                        <th class="text-center" style="width:80px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pelanggans as $index => $p)
                        <tr>
                            <td class="ps-3 text-muted">{{ $pelanggans->firstItem() + $index }}</td>
                            <td>
                                <code class="bg-light px-1 rounded small">{{ $p->pid }}</code>
                            </td>
                            <td class="fw-medium">{{ $p->nama }}</td>
                            <td class="text-muted">{{ $p->cabang?->nama ?? '-' }}</td>
                            <td class="text-muted">{{ $p->no_telp ?? '-' }}</td>
                            <td>
                                @if($filter === 'anniversary')
                                    <span class="text-info fw-medium">
                                        <i class="fas fa-calendar me-1"></i>
                                        {{ $p->kunjungans_max_tanggal_kunjungan
                                            ? \Carbon\Carbon::parse($p->kunjungans_max_tanggal_kunjungan)->format('d-m-Y')
                                            : '-' }}
                                    </span>
                                @else
                                    <span class="text-danger fw-medium">
                                        <i class="fas fa-birthday-cake me-1"></i>
                                        {{ $p->dob ? $p->dob->format('d-m-Y') : '-' }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @php $kelas = $p->class ?? 'Potensial'; @endphp
                                @if($kelas === 'Prioritas')
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Prioritas</span>
                                @elseif($kelas === 'Loyal')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success">Loyal</span>
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">Potensial</span>
                                @endif
                            </td>
                            <td>
                                @if($p->is_pelanggan_khusus)
                                    <span class="badge bg-purple text-white" style="background-color:#7c3aed!important;">
                                        <i class="fas fa-star me-1"></i>Pelanggan Khusus
                                    </span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">
                                        Pelanggan Biasa
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button"
                                        class="btn btn-info btn-sm px-2 py-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalInfo{{ $p->id }}"
                                        title="Informasi Detail">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-2x mb-3 text-secondary opacity-50 d-block"></i>
                                @if($filter === 'birthday')
                                    Tidak ada pelanggan yang berulang tahun hari ini.
                                @elseif($filter === 'birthday_month')
                                    Tidak ada pelanggan yang berulang tahun bulan ini.
                                @else
                                    Tidak ada pelanggan dengan kunjungan terakhir tepat 1 tahun yang lalu.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($pelanggans->hasPages() || $pelanggans->total() > 0)
        <div class="d-flex justify-content-between align-items-center p-3 border-top bg-light small">
            <div class="text-muted">
                Menampilkan <strong>{{ $pelanggans->firstItem() ?? 0 }} - {{ $pelanggans->lastItem() ?? 0 }}</strong>
                dari <strong>{{ $pelanggans->total() }}</strong> pelanggan
            </div>
            <div>
                {{ $pelanggans->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
        </div>
        @endif
    </div>
</div>

{{-- ── Modal Informasi Detail ─────────────────────────────────────────────── --}}
@foreach($pelanggans as $p)
<div class="modal fade" id="modalInfo{{ $p->id }}" tabindex="-1" aria-labelledby="modalInfoLabel{{ $p->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info bg-opacity-10 border-bottom">
                <h6 class="modal-title fw-semibold text-info" id="modalInfoLabel{{ $p->id }}">
                    <i class="fas fa-info-circle me-2"></i>Informasi Detail Pelanggan
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">

                <!-- Badge Kelas & Tipe -->
                <div class="d-flex gap-2 mb-3 flex-wrap">
                    @php $kelas = $p->class ?? 'Potensial'; @endphp
                    @if($kelas === 'Prioritas')
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 py-1">
                            <i class="fas fa-crown me-1"></i>Prioritas
                        </span>
                    @elseif($kelas === 'Loyal')
                        <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1">
                            <i class="fas fa-heart me-1"></i>Loyal
                        </span>
                    @else
                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-2 py-1">
                            <i class="fas fa-star me-1"></i>Potensial
                        </span>
                    @endif
                    @if($p->is_pelanggan_khusus)
                        <span class="badge text-white px-2 py-1" style="background-color:#7c3aed;">
                            <i class="fas fa-star me-1"></i>Pelanggan Khusus
                        </span>
                    @else
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary px-2 py-1">
                            Pelanggan Biasa
                        </span>
                    @endif
                </div>

                <!-- Grid Info -->
                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1">PID</div>
                            <code class="fw-semibold">{{ $p->pid }}</code>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1">Cabang</div>
                            <div class="fw-medium small">{{ $p->cabang?->nama ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1">Nama Lengkap</div>
                            <div class="fw-semibold">{{ $p->nama }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-phone me-1"></i>No Telepon</div>
                            <div class="small">{{ $p->no_telp ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-birthday-cake me-1"></i>DOB</div>
                            <div class="small">
                                {{ $p->dob ? $p->dob->format('d-m-Y') : '-' }}
                                @if($p->dob)
                                    <span class="text-muted">({{ $p->dob->age }} thn)</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-map-marker-alt me-1"></i>Alamat Lengkap</div>
                            <div class="small">{{ $p->alamat ?? '-' }}</div>
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
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-user-tag me-1"></i>Tipe Pelanggan</div>
                            <div class="small fw-medium">
                                @if($p->is_pelanggan_khusus)
                                    <span class="text-purple" style="color:#7c3aed;">
                                        <i class="fas fa-star me-1"></i>Pelanggan Khusus
                                    </span>
                                @else
                                    <span class="text-secondary">Pelanggan Biasa</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endforeach

@endsection
