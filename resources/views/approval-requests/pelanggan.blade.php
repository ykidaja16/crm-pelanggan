@extends('layouts.main')

@section('title', 'Approval Data Pelanggan - Medical Lab CRM')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="text-primary mb-0 fw-semibold">
            <i class="fas fa-users me-2"></i>Approval Data Pelanggan
        </h4>
        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-3 py-2">
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
            <form method="GET" action="{{ route('approval.pelanggan') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-medium small">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2 mt-1">
                    <button type="submit" class="btn btn-primary btn-sm px-3">
                        <i class="fas fa-filter me-1"></i>Terapkan Filter
                    </button>
                    <a href="{{ route('approval.pelanggan') }}" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="fas fa-times me-1"></i>Reset Filter
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:50px;">ID</th>
                        <th style="width:120px;">Aksi</th>
                        <th style="width:100px;">Status</th>
                        <th>Pengaju</th>
                        <th>Catatan Pengajuan</th>
                        <th>Catatan Keputusan</th>
                        <th style="width:90px;" class="text-center">Informasi</th>
                        <th style="width:220px;">Aksi Superadmin</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $item)
                        <tr>
                            <td class="fw-semibold text-muted">#{{ $item->id }}</td>
                            <td>
                                @if($item->action === 'delete')
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Hapus</span>
                                @elseif($item->action === 'bulk_delete')
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Hapus Massal</span>
                                @elseif($item->action === 'edit')
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary">Edit</span>
                                @else
                                    <span class="badge bg-secondary">{{ $item->action }}</span>
                                @endif
                            </td>
                            <td>
                                @if($item->status === 'pending')
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">PENDING</span>
                                @elseif($item->status === 'approved')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success">APPROVED</span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">REJECTED</span>
                                @endif
                            </td>
                            <td class="small">{{ $item->requester?->name ?? $item->requester?->username ?? '-' }}</td>
                            <td class="small text-muted" style="max-width:180px; white-space:normal;">{{ Str::limit($item->request_note ?? '-', 80) }}</td>
                            <td class="small text-muted" style="max-width:180px; white-space:normal;">{{ Str::limit($item->decision_note ?? '-', 80) }}</td>
                            <td class="text-center">
                                <button type="button"
                                        class="btn btn-info btn-sm"
                                        title="Lihat Detail Pengajuan"
                                        data-bs-toggle="modal"
                                        data-bs-target="#infoModal{{ $item->id }}">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                            </td>
                            <td>
                                @if($item->status === 'pending')
                                    <form method="POST" action="{{ route('approval.process', $item->id) }}">
                                        @csrf
                                        <div class="mb-2">
                                            <select name="action" class="form-select form-select-sm" required>
                                                <option value="">-- Pilih Keputusan --</option>
                                                <option value="approve">✅ Approve</option>
                                                <option value="reject">❌ Reject</option>
                                            </select>
                                        </div>
                                        <div class="mb-2">
                                            <input type="text"
                                                   name="decision_note"
                                                   class="form-control form-control-sm"
                                                   placeholder="Catatan keputusan..."
                                                   required>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm w-100">
                                            <i class="fas fa-paper-plane me-1"></i>Proses
                                        </button>
                                    </form>
                                @else
                                    <div class="text-center">
                                        <span class="text-muted small">
                                            <i class="fas fa-check-circle me-1 text-{{ $item->status === 'approved' ? 'success' : 'danger' }}"></i>
                                            Sudah diproses
                                        </span>
                                        @if($item->reviewer)
                                            <div class="text-muted" style="font-size:0.75rem;">
                                                oleh {{ $item->reviewer?->name ?? $item->reviewer?->username ?? '-' }}
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-2x mb-3 d-block text-secondary opacity-50"></i>
                                Tidak ada data approval pelanggan.
                            </td>
                        </tr>
                    @endforelse
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
    </div>
</div>

{{-- ============================================================
     MODALS — ditempatkan di LUAR tabel agar HTML valid
     ============================================================ --}}
@foreach($requests as $item)
    @php $payload = $item->payload ?? []; @endphp
    <div class="modal fade" id="infoModal{{ $item->id }}" tabindex="-1" aria-labelledby="infoModalLabel{{ $item->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); border-bottom: 2px solid #dc3545;">
                    <h5 class="modal-title fw-semibold text-danger" id="infoModalLabel{{ $item->id }}">
                        <i class="fas fa-users me-2"></i>Detail Pengajuan Pelanggan #{{ $item->id }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Kolom Kiri: Data Pelanggan -->
                        <div class="col-md-6">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body">
                                    <h6 class="fw-semibold text-primary mb-3">
                                        <i class="fas fa-user me-2"></i>Data Pelanggan
                                    </h6>
                                    @if($item->action === 'bulk_delete')
                                        <div class="alert alert-warning py-2 mb-2 small">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            <strong>Hapus Massal:</strong> {{ count($payload['ids'] ?? []) }} pelanggan
                                        </div>
                                        <div class="bg-white rounded p-2 border small text-muted">
                                            <strong>ID Pelanggan:</strong><br>
                                            {{ implode(', ', $payload['ids'] ?? []) }}
                                        </div>
                                    @else
                                        <table class="table table-sm table-borderless mb-0">
                                            <tr>
                                                <td class="text-muted small fw-medium" style="width:40%">PID</td>
                                                <td class="small fw-semibold">
                                                    @if(!empty($payload['pid']))
                                                        <code class="bg-white px-2 py-1 rounded border">{{ $payload['pid'] }}</code>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted small fw-medium">Nama</td>
                                                <td class="small fw-semibold">{{ $payload['nama'] ?? '-' }}</td>
                                            </tr>
                                            @if(!empty($payload['cabang']))
                                            <tr>
                                                <td class="text-muted small fw-medium">Cabang</td>
                                                <td class="small">
                                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary">
                                                        {{ $payload['cabang'] }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endif
                                            @if(!empty($payload['class']))
                                            <tr>
                                                <td class="text-muted small fw-medium">Kelas</td>
                                                <td class="small">
                                                    @php
                                                        $cls = $payload['class'];
                                                        $clsBadge = match($cls) {
                                                            'Prioritas' => 'bg-danger bg-opacity-10 text-danger border border-danger',
                                                            'Loyal'     => 'bg-success bg-opacity-10 text-success border border-success',
                                                            'Potensial' => 'bg-warning bg-opacity-10 text-warning border border-warning',
                                                            default     => 'bg-secondary bg-opacity-10 text-secondary border border-secondary',
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $clsBadge }}">{{ $cls }}</span>
                                                </td>
                                            </tr>
                                            @endif
                                        </table>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Kolom Kanan: Detail Aksi & Alasan -->
                        <div class="col-md-6">
                            <div class="card border-0 bg-light h-100">
                                <div class="card-body">
                                    <h6 class="fw-semibold text-danger mb-3">
                                        <i class="fas fa-trash-alt me-2"></i>Detail Aksi
                                    </h6>
                                    <table class="table table-sm table-borderless mb-0">
                                        <tr>
                                            <td class="text-muted small fw-medium" style="width:40%">Jenis Aksi</td>
                                            <td class="small">
                                                @if($item->action === 'delete')
                                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Hapus Pelanggan</span>
                                                @elseif($item->action === 'bulk_delete')
                                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Hapus Massal</span>
                                                @elseif($item->action === 'edit')
                                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary">Edit Data</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ ucfirst($item->action) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @if($item->action === 'bulk_delete')
                                        <tr>
                                            <td class="text-muted small fw-medium">Jumlah</td>
                                            <td class="small fw-semibold text-danger">
                                                {{ $payload['count'] ?? count($payload['ids'] ?? []) }} pelanggan
                                            </td>
                                        </tr>
                                        @endif
                                    </table>
                                    <hr class="my-2">
                                    <h6 class="fw-semibold text-secondary mb-2 small">
                                        <i class="fas fa-clipboard me-1"></i>Alasan Pengajuan
                                    </h6>
                                    <div class="bg-white rounded p-2 border small text-muted" style="min-height:60px;">
                                        {{ $item->request_note ?? '-' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Info Pengaju & Waktu -->
                    <div class="mt-3 p-3 bg-light rounded border">
                        <div class="row g-2 small">
                            <div class="col-md-4">
                                <span class="text-muted">Diajukan oleh:</span>
                                <strong class="ms-1">{{ $item->requester?->name ?? $item->requester?->username ?? '-' }}</strong>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted">Tanggal Pengajuan:</span>
                                <strong class="ms-1">{{ $item->created_at?->format('d-m-Y H:i') ?? '-' }}</strong>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted">Status:</span>
                                @if($item->status === 'pending')
                                    <span class="badge bg-warning ms-1">PENDING</span>
                                @elseif($item->status === 'approved')
                                    <span class="badge bg-success ms-1">APPROVED</span>
                                @else
                                    <span class="badge bg-danger ms-1">REJECTED</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
@endforeach

@endsection
