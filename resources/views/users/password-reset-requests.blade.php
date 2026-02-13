@extends('layouts.main')

@section('title', 'Permintaan Reset Password - Medical Lab CRM')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-primary mb-0">Permintaan Reset Password</h3>
        <a href="{{ route('users.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali ke Users</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">No</th>
                            <th>Nama User</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Tanggal Permintaan</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $index => $request)
                            <tr>
                                <td class="ps-4">{{ $requests->firstItem() + $index }}</td>
                                <td class="fw-bold">{{ $request->user->name }}</td>
                                <td>{{ $request->user->email }}</td>
                                <td>{{ $request->user->username }}</td>
                                <td>{{ $request->user->role->name ?? '-' }}</td>
                                <td>{{ $request->requested_at->format('d-m-Y H:i') }}</td>
                                <td>
                                    @if ($request->status == 'pending')
                                        <span class="badge bg-warning text-dark">Menunggu</span>
                                    @elseif($request->status == 'approved')
                                        <span class="badge bg-success">Disetujui</span>
                                    @else
                                        <span class="badge bg-danger">Ditolak</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($request->status == 'pending')
                                        <a href="{{ route('users.reset-password.form', $request->user->id) }}" class="btn btn-sm btn-primary me-1">
                                            <i class="fas fa-key"></i> Reset Password
                                        </a>
                                        <form action="{{ route('users.password-reset.reject', $request->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menolak permintaan ini?')">
                                            @csrf
                                            <button class="btn btn-sm btn-danger"><i class="fas fa-times"></i> Tolak</button>
                                        </form>
                                    @else
                                        <span class="text-muted">Sudah diproses</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Tidak ada permintaan reset password.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($requests instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="d-flex justify-content-end mt-3">
                    {{ $requests->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
@endsection
