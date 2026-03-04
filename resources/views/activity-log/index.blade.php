@extends('layouts.main')

@section('title', 'Log Aktivitas - SIMA Lab')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-history me-2 text-primary"></i>Log Aktivitas</h4>
            <small class="text-muted">Riwayat semua aktivitas pengguna sistem</small>
        </div>
        <a href="{{ route('activity-log.export', request()->query()) }}"
           class="btn btn-success">
            <i class="fas fa-file-excel me-1"></i> Export Excel
        </a>
    </div>

    {{-- Filter Card --}}
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-2"></i>Filter Log
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('activity-log.index') }}" id="filterForm">
                <div class="row g-3">
                    {{-- Filter User --}}
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">User</label>
                        <select name="user_id" class="form-select">
                            <option value="">-- Semua User --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}"
                                    {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->username }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter Aksi --}}
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Jenis Aksi</label>
                        <select name="action" class="form-select">
                            <option value="">-- Semua Aksi --</option>
                            @foreach($actions as $key => $label)
                                <option value="{{ $key }}"
                                    {{ request('action') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter Modul --}}
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Modul</label>
                        <select name="module" class="form-select">
                            <option value="">-- Semua Modul --</option>
                            @foreach($modules as $key => $label)
                                <option value="{{ $key }}"
                                    {{ request('module') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter Tanggal Mulai --}}
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="form-control"
                               value="{{ request('tanggal_mulai') }}">
                    </div>

                    {{-- Filter Tanggal Selesai --}}
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" class="form-control"
                               value="{{ request('tanggal_selesai') }}">
                    </div>

                    {{-- Tombol --}}
                    <div class="col-md-1 d-flex align-items-end">
                        <div class="d-flex gap-2 w-100">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="fas fa-search"></i>
                            </button>
                            <a href="{{ route('activity-log.index') }}" class="btn btn-outline-secondary flex-fill">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabel Log --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list me-2"></i>Daftar Log</span>
            <span class="badge bg-secondary">Total: {{ $logs->total() }} entri</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width:50px">#</th>
                            <th style="width:160px">Waktu</th>
                            <th style="width:140px">Username</th>
                            <th style="width:110px">Role</th>
                            <th style="width:100px" class="text-center">Aksi</th>
                            <th style="width:110px" class="text-center">Modul</th>
                            <th>Deskripsi</th>
                            <th style="width:130px">IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $index => $log)
                        <tr>
                            <td class="text-center text-muted small">
                                {{ ($logs->currentPage() - 1) * $logs->perPage() + $index + 1 }}
                            </td>
                            <td class="small text-nowrap">
                                {{ $log->created_at?->format('d/m/Y H:i:s') ?? '-' }}
                            </td>
                            <td>
                                <span class="fw-semibold">{{ $log->username }}</span>
                            </td>
                            <td>
                                <span class="badge
                                    @if($log->role === 'Super Admin') bg-danger
                                    @elseif($log->role === 'Admin') bg-warning text-dark
                                    @else bg-secondary
                                    @endif">
                                    {{ $log->role }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ \App\Models\ActivityLog::actionBadgeClass($log->action) }}">
                                    {{ \App\Models\ActivityLog::actionLabel($log->action) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">
                                    @switch($log->module)
                                        @case('auth') <i class="fas fa-key me-1"></i>Auth @break
                                        @case('pelanggan') <i class="fas fa-users me-1"></i>Pelanggan @break
                                        @case('user') <i class="fas fa-user-cog me-1"></i>User @break
                                        @default {{ ucfirst($log->module) }}
                                    @endswitch
                                </span>
                            </td>
                            <td class="small">{{ $log->description }}</td>
                            <td class="small text-muted font-monospace">{{ $log->ip_address ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                Tidak ada log aktivitas ditemukan
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center p-3 border-top bg-light small">
            <div class="text-muted">
                Menampilkan <strong>{{ $logs->firstItem() ?? 0 }} - {{ $logs->lastItem() ?? 0 }}</strong> dari <strong>{{ $logs->total() }}</strong> entri
            </div>
            <div>
                {{ $logs->links('pagination::bootstrap-5') }}
            </div>
        </div>

    </div>
</div>
@endsection
