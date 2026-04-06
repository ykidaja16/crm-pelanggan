@extends('layouts.main')

@section('title', 'Approval Ubah Kelas - Medical Lab CRM')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="text-primary mb-0 fw-semibold">
            <i class="fas fa-exchange-alt me-2"></i>Approval Ubah Kelas
        </h4>
        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-3 py-2">
            Total: {{ $requests->total() }} pengajuan
        </span>
    </div>

{{-- @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif --}}

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('approval.naik-kelas') }}" class="row g-2 align-items-end">
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
                    <a href="{{ route('approval.naik-kelas') }}" class="btn btn-outline-secondary btn-sm px-3">
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
                    <p class="mb-0">Tidak ada pengajuan ubah kelas.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Diajukan Oleh</th>
                                <th>Jumlah Pelanggan</th>
                                <th>Catatan Pengajuan</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requests as $item)
                            @php
                                $payload    = $item->payload ?? [];
                                $count      = $payload['count'] ?? count($payload['ids'] ?? []);
                                $pelanggans = $payload['pelanggans'] ?? [];
                            @endphp
                            <tr>
                                <td class="ps-3 text-muted small">{{ $item->id }}</td>
                                <td>
                                    <div class="fw-medium small">{{ $item->requester?->name ?? '-' }}</div>
                                    <div class="text-muted" style="font-size:11px">{{ $item->requester?->role?->name ?? '-' }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info">
                                        {{ $count }} pelanggan
                                    </span>
                                </td>
                                <td class="small text-muted" style="max-width:200px">
                                    {{ Str::limit($item->request_note, 60) }}
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
                                <td class="small text-muted">{{ $item->created_at->format('d-m-Y H:i') }}</td>
                                <td class="text-center">
                                    <button type="button"
                                        class="btn btn-outline-primary btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalNaikKelas{{ $item->id }}">
                                        <i class="fas fa-pencil-alt"></i>
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
    $payload    = $item->payload ?? [];
    $count      = $payload['count'] ?? count($payload['ids'] ?? []);
    $pelanggans = $payload['pelanggans'] ?? [];
@endphp
<div class="modal fade" id="modalNaikKelas{{ $item->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">

            {{-- Header --}}
            <div class="modal-header bg-warning bg-opacity-10 border-bottom">
                <h6 class="modal-title fw-semibold">
                    <i class="fas fa-exchange-alt text-warning me-2"></i>
                    Detail Pengajuan Ubah Kelas #{{ $item->id }}
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
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-calendar me-1"></i>Tanggal Pengajuan</div>
                            <div class="small fw-medium">{{ $item->created_at->format('d-m-Y H:i') }}</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-sticky-note me-1"></i>Catatan Pengajuan</div>
                            <div class="small">{{ $item->request_note ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="p-2 bg-light rounded border">
                            <div class="text-muted small mb-1"><i class="fas fa-info-circle me-1"></i>Status</div>
                            <div class="small">
                                @if($item->status === 'pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @elseif($item->status === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                    @if($item->decision_note)
                                        <span class="text-muted ms-2">— {{ $item->decision_note }}</span>
                                    @endif
                                @else
                                    <span class="badge bg-danger">Rejected</span>
                                    @if($item->decision_note)
                                        <span class="text-muted ms-2">— {{ $item->decision_note }}</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Daftar Pelanggan --}}
                <h6 class="fw-semibold small text-uppercase mb-2">
                    <i class="fas fa-users me-1"></i>Daftar Pelanggan ({{ $count }})
                </h6>
                @if(!empty($pelanggans))
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-3">
                        <thead class="table-light">
                            <tr>
                                <th class="small">#</th>
                                <th class="small">PID</th>
                                <th class="small">Nama</th>
                                <th class="small">Kelas Saat Ini</th>
                                <th class="small">Ubah Ke</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pelanggans as $idx => $p)
                            @php
                                $targetClass = $p['target_class'] ?? ($payload['target_class'] ?? 'Prioritas');
                                $targetBadge = match($targetClass) {
                                    'Prioritas' => 'bg-danger',
                                    'Loyal'     => 'bg-success',
                                    'Potensial' => 'bg-warning text-dark',
                                    'Umum'      => 'bg-secondary',
                                    default     => 'bg-secondary',
                                };
                            @endphp
                            <tr>
                                <td class="small text-muted">{{ $idx + 1 }}</td>
                                <td class="small fw-medium">{{ $p['pid'] ?? '-' }}</td>
                                <td class="small">{{ $p['nama'] ?? '-' }}</td>
                                <td class="small">
                                    @php
                                        $curClass = $p['class'] ?? 'Umum';
                                        $curBadge = match($curClass) {
                                            'Prioritas' => 'bg-danger',
                                            'Loyal'     => 'bg-success',
                                            'Potensial' => 'bg-warning text-dark',
                                            'Umum'      => 'bg-secondary',
                                            default     => 'bg-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $curBadge }}">{{ $curClass }}</span>
                                </td>
                                <td class="small">
                                    <span class="badge {{ $targetBadge }}">{{ $targetClass }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                    <p class="text-muted small">Data pelanggan tidak tersedia.</p>
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
                                <label class="form-label small fw-medium mb-2">Keputusan</label>
                                <div class="d-flex gap-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="action" id="nk_approve_{{ $item->id }}" value="approve" required
                                               onchange="updateApprovalBtn(this)">
                                        <label class="form-check-label text-success fw-semibold" for="nk_approve_{{ $item->id }}">
                                            ✅ Approve
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="action" id="nk_reject_{{ $item->id }}" value="reject"
                                               onchange="updateApprovalBtn(this)">
                                        <label class="form-check-label text-danger fw-semibold" for="nk_reject_{{ $item->id }}">
                                            ❌ Reject
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-medium">Catatan Keputusan <span class="text-danger">*</span></label>
                                <textarea name="decision_note" class="form-control form-control-sm"
                                          rows="3" placeholder="Tulis catatan keputusan..." required maxlength="500"></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-sm px-4 approval-submit-btn">
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

@push('scripts')
<script>
function updateApprovalBtn(radio) {
    const form = radio.closest('form');
    if (!form) return;
    const btn = form.querySelector('.approval-submit-btn');
    if (!btn) return;
    if (radio.value === 'approve') {
        btn.className = 'btn btn-success btn-sm px-4 approval-submit-btn';
        btn.innerHTML = '<i class="fas fa-check me-1"></i>Approve';
    } else {
        btn.className = 'btn btn-danger btn-sm px-4 approval-submit-btn';
        btn.innerHTML = '<i class="fas fa-times me-1"></i>Reject';
    }
}
</script>
@endpush

@endsection
