@extends('layouts.main')

@section('title', 'Approval Pelanggan Khusus - Medical Lab CRM')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="text-primary mb-0 fw-semibold">
            <i class="fas fa-star me-2"></i>Approval Pelanggan Khusus
        </h4>
        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-3 py-2">
            Total: {{ $requests->total() }} pengajuan
        </span>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('approval.pelanggan-khusus') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-medium small">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary btn-sm px-3">
                        <i class="fas fa-filter me-1"></i>Terapkan Filter
                    </button>
                    <a href="{{ route('approval.pelanggan-khusus') }}" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="fas fa-times me-1"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            @if($requests->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
                    <p class="mb-0">Tidak ada pengajuan approval pelanggan khusus.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Jenis</th>
                                <th>Status</th>
                                <th>Diajukan Oleh</th>
                                <th>Catatan Pengajuan</th>
                                <th>Tanggal</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requests as $item)
                            <tr>
                                <td class="ps-3 text-muted small">{{ $item->id }}</td>
                                <td>
                                    @if($item->action === 'create')
                                        <span class="badge bg-success">
                                            <i class="fas fa-plus me-1"></i>Pelanggan Baru
                                        </span>
                                    @else
                                        <span class="badge bg-info">
                                            <i class="fas fa-calendar-plus me-1"></i>Tambah Kunjungan
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($item->status === 'pending')
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @elseif($item->status === 'approved')
                                        <span class="badge bg-success">Approved</span>
                                    @else
                                        <span class="badge bg-danger">Rejected</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-medium small">{{ $item->requester?->name ?? '-' }}</div>
                                    <div class="text-muted" style="font-size:11px">{{ $item->requester?->role?->name ?? '-' }}</div>
                                </td>
                                <td class="small text-muted" style="max-width:200px">
                                    {{ Str::limit($item->request_note, 60) }}
                                </td>
                                <td class="small text-muted">{{ $item->created_at->format('d-m-Y H:i') }}</td>
                                <td class="text-center">
                                    <button type="button"
                                        class="btn btn-outline-primary btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalKhusus{{ $item->id }}">
                                        <i class="fas fa-eye me-1"></i>Detail
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center p-3 border-top bg-light small">
                    <div class="text-muted">
                        Menampilkan <strong>{{ $requests->firstItem() ?? 0 }} - {{ $requests->lastItem() ?? 0 }}</strong>
                        dari <strong>{{ $requests->total() }}</strong> data
                    </div>
                    <div>
                        {{ $requests->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ── MODALS ── --}}
@foreach($requests as $item)
@php
    $payload = $item->payload ?? [];
@endphp
<div class="modal fade" id="modalKhusus{{ $item->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">

            {{-- Header --}}
            <div class="modal-header bg-warning bg-opacity-10 border-bottom">
                <h6 class="modal-title fw-semibold">
                    <i class="fas fa-star text-warning me-2"></i>
                    Detail Pengajuan Pelanggan Khusus #{{ $item->id }}
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            {{-- Body --}}
            <div class="modal-body">

                {{-- Info Pengajuan --}}
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-user me-1"></i>Diajukan Oleh</div>
                            <div class="small fw-medium">{{ $item->requester?->name ?? '-' }}</div>
                            <div class="text-muted" style="font-size:11px">{{ $item->requester?->role?->name ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-calendar me-1"></i>Tanggal Pengajuan</div>
                            <div class="small fw-medium">{{ $item->created_at->format('d-m-Y H:i') }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-tag me-1"></i>Jenis Pengajuan</div>
                            <div class="small fw-medium">
                                @if($item->action === 'create')
                                    <span class="badge bg-success bg-opacity-15 text-success border border-success">Pelanggan Baru</span>
                                @else
                                    <span class="badge bg-info bg-opacity-15 text-info border border-info">Tambah Kunjungan</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-info-circle me-1"></i>Status</div>
                            <div class="small">
                                @if($item->status === 'pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @elseif($item->status === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                    @if($item->decision_note)
                                        <div class="text-muted mt-1" style="font-size:11px">{{ $item->decision_note }}</div>
                                    @endif
                                @else
                                    <span class="badge bg-danger">Rejected</span>
                                    @if($item->decision_note)
                                        <div class="text-muted mt-1" style="font-size:11px">{{ $item->decision_note }}</div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-sticky-note me-1"></i>Catatan Pengajuan</div>
                            <div class="small">{{ $item->request_note ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                {{-- Data Pelanggan Baru (action=create) --}}
                @if($item->action === 'create')
                <h6 class="fw-semibold small text-uppercase mb-2">
                    <i class="fas fa-user-plus me-1"></i>Data Pelanggan Baru
                </h6>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1">PID</div>
                            <div class="small fw-medium font-monospace">{{ $payload['pid'] ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1">Cabang</div>
                            <div class="small fw-medium">{{ $cabangs[$payload['cabang_id'] ?? 0]?->nama ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1">Nama</div>
                            <div class="small fw-medium">{{ $payload['nama'] ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1">No. Telpon</div>
                            <div class="small">{{ $payload['no_telp'] ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1">Tanggal Lahir</div>
                            <div class="small">{{ $payload['dob'] ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1">Kota</div>
                            <div class="small">{{ $payload['kota'] ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1">Alamat</div>
                            <div class="small">{{ $payload['alamat'] ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1">Kategori Khusus</div>
                            <div class="small fw-medium text-warning">{{ $payload['kategori_khusus'] ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1">Kelompok Pelanggan</div>
                            <div class="small">{{ ucfirst($payload['kelompok_pelanggan'] ?? '-') }}</div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-semibold small text-uppercase mb-2">
                    <i class="fas fa-calendar-check me-1"></i>Data Kunjungan Pertama
                </h6>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1">Tanggal Kunjungan</div>
                            <div class="small fw-medium">{{ $payload['tanggal_kunjungan'] ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1">Biaya</div>
                            <div class="small fw-medium">Rp {{ number_format($payload['biaya_kunjungan'] ?? 0, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>

                {{-- Tambah Kunjungan ke Pelanggan Existing (action=add_visit) --}}
                @elseif($item->action === 'add_visit')
                @php
                    $targetPelanggan = \App\Models\Pelanggan::with('cabang')->find($item->target_id);
                @endphp
                <h6 class="fw-semibold small text-uppercase mb-2">
                    <i class="fas fa-user me-1"></i>Data Pelanggan
                </h6>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1">PID</div>
                            <div class="small fw-medium font-monospace">{{ $targetPelanggan?->pid ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1">Nama</div>
                            <div class="small fw-medium">{{ $targetPelanggan?->nama ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1">Cabang</div>
                            <div class="small">{{ $targetPelanggan?->cabang?->nama ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1">Kategori Khusus</div>
                            <div class="small text-warning fw-medium">{{ $targetPelanggan?->kategori_khusus ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-semibold small text-uppercase mb-2">
                    <i class="fas fa-calendar-plus me-1"></i>Data Kunjungan Baru
                </h6>
                <div class="row g-2 mb-3">
                    <div class="col-4">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1">Tanggal Kunjungan</div>
                            <div class="small fw-medium">{{ $payload['tanggal_kunjungan'] ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1">Biaya</div>
                            <div class="small fw-medium">Rp {{ number_format($payload['biaya_kunjungan'] ?? 0, 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1">Kelompok</div>
                            <div class="small">{{ ucfirst($payload['kelompok_pelanggan'] ?? '-') }}</div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Form Approve/Reject (hanya Super Admin & status pending) --}}
                @if(Auth::user()->role?->name === 'Super Admin' && $item->status === 'pending')
                <div class="mt-3 p-3 border rounded bg-white">
                    <h6 class="fw-semibold small text-uppercase mb-3">
                        <i class="fas fa-gavel me-1"></i>Keputusan
                    </h6>
                    <form action="{{ route('approval.process', $item->id) }}" method="POST">
                        @csrf
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label small fw-medium">Aksi</label>
                                <select name="action" class="form-select form-select-sm" required>
                                    <option value="">-- Pilih --</option>
                                    <option value="approve">✅ Approve</option>
                                    <option value="reject">❌ Reject</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small fw-medium">Catatan Keputusan <span class="text-danger">*</span></label>
                                <input type="text" name="decision_note" class="form-control form-control-sm"
                                       placeholder="Tulis catatan keputusan..." required maxlength="500">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-sm px-4">
                                    <i class="fas fa-paper-plane me-1"></i>Kirim Keputusan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                @endif

            </div>

            {{-- Footer --}}
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
