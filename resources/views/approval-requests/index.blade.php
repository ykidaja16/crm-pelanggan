@extends('layouts.main')

@section('title', 'Approval Requests - Medical Lab CRM')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="text-primary mb-0 fw-semibold">
            <i class="fas fa-check-double me-2"></i>Approval Requests
        </h4>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('approval.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-medium small">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium small">Tipe</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">Semua Tipe</option>
                        <option value="pelanggan_khusus" {{ request('type') === 'pelanggan_khusus' ? 'selected' : '' }}>Pelanggan Khusus</option>
                        <option value="kunjungan" {{ request('type') === 'kunjungan' ? 'selected' : '' }}>Kunjungan</option>
                        <option value="pelanggan" {{ request('type') === 'pelanggan' ? 'selected' : '' }}>Hapus Pelanggan</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                </div>
                @if(request('status') || request('type'))
                    <div class="col-md-2">
                        <a href="{{ route('approval.index') }}" class="btn btn-outline-secondary btn-sm w-100">
                            <i class="fas fa-times me-1"></i>Reset
                        </a>
                    </div>
                @endif
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
                        <th>Tipe</th>
                        <th>Aksi</th>
                        <th style="width:100px;">Status</th>
                        <th>Pengaju</th>
                        <th>Catatan Pengajuan</th>
                        <th>Catatan Keputusan</th>
                        <th style="width:220px;">Aksi Superadmin</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $item)
                        <tr>
                            <td class="fw-semibold text-muted">#{{ $item->id }}</td>
                            <td>
                                @if($item->type === 'pelanggan_khusus')
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">Pelanggan Khusus</span>
                                @elseif($item->type === 'kunjungan')
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info">Kunjungan</span>
                                @elseif($item->type === 'pelanggan')
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Hapus Pelanggan</span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">{{ $item->type }}</span>
                                @endif
                            </td>
                            <td>
                                @if($item->action === 'create')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success">Tambah</span>
                                @elseif($item->action === 'edit')
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary">Edit</span>
                                @elseif($item->action === 'delete')
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Hapus</span>
                                @elseif($item->action === 'bulk_delete')
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Hapus Massal</span>
                                @else
                                    {{ $item->action }}
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $item->status === 'approved' ? 'success' : ($item->status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ strtoupper($item->status) }}
                                </span>
                            </td>
                            <td class="small">{{ $item->requester?->name ?? $item->requester?->username ?? '-' }}</td>
                            <td class="small text-muted" style="max-width:200px;">{{ $item->request_note ?? '-' }}</td>
                            <td class="small text-muted" style="max-width:200px;">{{ $item->decision_note ?? '-' }}</td>
                            <td>
                                @if($item->status === 'pending')
                                    <form method="POST" action="{{ route('approval.process', $item->id) }}">
                                        @csrf
                                        <div class="d-flex gap-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="action"
                                                       id="idx_approve_{{ $item->id }}" value="approve" required
                                                       onchange="updateApprovalBtn(this)">
                                                <label class="form-check-label text-success fw-semibold small" for="idx_approve_{{ $item->id }}">
                                                    ✅ Approve
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="action"
                                                       id="idx_reject_{{ $item->id }}" value="reject"
                                                       onchange="updateApprovalBtn(this)">
                                                <label class="form-check-label text-danger fw-semibold small" for="idx_reject_{{ $item->id }}">
                                                    ❌ Reject
                                                </label>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <textarea name="decision_note"
                                                      class="form-control form-control-sm"
                                                      rows="2"
                                                      placeholder="Catatan keputusan..."
                                                      required maxlength="500"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-sm w-100 approval-submit-btn">
                                            <i class="fas fa-paper-plane me-1"></i>Kirim Keputusan
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
                                <i class="fas fa-inbox fa-2x mb-3 text-secondary opacity-50"></i>
                                <p class="mb-0">Tidak ada data approval request.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($requests->hasPages() || $requests->total() > 0)
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
@push('scripts')
<script>
function updateApprovalBtn(radio) {
    const form = radio.closest('form');
    if (!form) return;
    const btn = form.querySelector('.approval-submit-btn');
    if (!btn) return;
    if (radio.value === 'approve') {
        btn.className = 'btn btn-success btn-sm w-100 approval-submit-btn';
        btn.innerHTML = '<i class="fas fa-check me-1"></i>Approve';
    } else {
        btn.className = 'btn btn-danger btn-sm w-100 approval-submit-btn';
        btn.innerHTML = '<i class="fas fa-times me-1"></i>Reject';
    }
}
</script>
@endpush

@endsection
