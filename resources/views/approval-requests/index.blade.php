@extends('layouts.main')

@section('content')
<div class="container-fluid py-3">
    <h4 class="mb-3">Approval Requests</h4>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('approval.index') }}" class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipe</label>
                    <select name="type" class="form-select">
                        <option value="">Semua</option>
                        <option value="pelanggan_khusus" {{ request('type') === 'pelanggan_khusus' ? 'selected' : '' }}>Pelanggan Khusus</option>
                        <option value="kunjungan" {{ request('type') === 'kunjungan' ? 'selected' : '' }}>Kunjungan</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipe</th>
                        <th>Aksi</th>
                        <th>Status</th>
                        <th>Requester</th>
                        <th>Request Note</th>
                        <th>Decision Note</th>
                        <th>Aksi Superadmin</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $item)
                        <tr>
                            <td>#{{ $item->id }}</td>
                            <td>{{ $item->type }}</td>
                            <td>{{ $item->action }}</td>
                            <td>
                                <span class="badge bg-{{ $item->status === 'approved' ? 'success' : ($item->status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ strtoupper($item->status) }}
                                </span>
                            </td>
                            <td>{{ $item->requester->name ?? '-' }}</td>
                            <td>{{ $item->request_note }}</td>
                            <td>{{ $item->decision_note ?? '-' }}</td>
                            <td>
                                @if($item->status === 'pending')
                                    <form method="POST" action="{{ route('approval.approve', $item->id) }}" class="mb-2">
                                        @csrf
                                        <input type="text" name="decision_note" class="form-control form-control-sm mb-1" placeholder="Catatan approve" required>
                                        <button class="btn btn-success btn-sm w-100">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('approval.reject', $item->id) }}">
                                        @csrf
                                        <input type="text" name="decision_note" class="form-control form-control-sm mb-1" placeholder="Catatan reject" required>
                                        <button class="btn btn-danger btn-sm w-100">Reject</button>
                                    </form>
                                @else
                                    <span class="text-muted">Sudah diproses</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center py-3">Tidak ada data approval request.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $requests->links() }}
        </div>
    </div>
</div>
@endsection
