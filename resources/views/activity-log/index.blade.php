@extends('layouts.main')

@section('title', 'Log Aktivitas - SIMA Lab')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
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
    <div class="card mb-4 shadow-sm border-0 rounded-3">
        <div class="card-header bg-white py-3 border-bottom">
            <i class="fas fa-filter text-primary me-2"></i>Filter Log Aktivitas
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('activity-log.index') }}" id="filterForm">
                <div class="row g-3">
                    {{-- Filter User --}}
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">User</label>
                        <select name="user_id" class="form-select form-select-sm">
                            <option value="">-- Semua User --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}"
                                    {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->username }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter Cabang: Searchable Dropdown (Persis Pemeriksaan) --}}
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Cabang</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-info border-end-0">
                                <i class="fas fa-building"></i>
                            </span>
                            <input type="hidden" name="cabang_id" id="activityCabangId" value="{{ $cabangId ?? '' }}">
                            <input type="text"
                                   name="cabang"
                                   id="activityCabangInput"
                                   class="form-control border-start-0 ps-0"
                                   list="activityCabangDatalist"
                                   value="{{ $selectedCabangNama ?? '' }}"
                                   placeholder="Pilih / ketik nama cabang..."
                                   autocomplete="off">
                            <button class="btn btn-outline-info dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Pilih Cabang"></button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="max-height: 250px; overflow-y: auto;" id="activityCabangDropdown">
                                <li><a class="dropdown-item small text-muted" href="javascript:void(0)" onclick="selectActivityCabang('', '')">-- Semua Cabang --</a></li>
                                <li><hr class="dropdown-divider"></li>
                                @if(isset($cabangs) && count($cabangs) > 0)
                                    @foreach($cabangs as $c)
                                        <li>
                                            <a class="dropdown-item small" href="javascript:void(0)" onclick="selectActivityCabang('{{ $c->id }}', '{{ addslashes($c->nama) }}')">
                                                {{ $c->nama }}
                                            </a>
                                        </li>
                                    @endforeach
                                @endif
                            </ul>
                            <datalist id="activityCabangDatalist">
                                @if(isset($cabangs))
                                    @foreach($cabangs as $c)
                                        <option value="{{ $c->nama }}">
                                    @endforeach
                                @endif
                            </datalist>
                        </div>
                    </div>

                    {{-- Filter Aksi --}}
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">Jenis Aksi</label>
                        <select name="action" class="form-select form-select-sm">
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
                        <label class="form-label fw-semibold small">Modul</label>
                        <select name="module" class="form-select form-select-sm">
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
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Rentang Tanggal</label>
                        <div class="input-group input-group-sm">
                            <input type="date" name="tanggal_mulai" class="form-control" value="{{ request('tanggal_mulai') }}">
                            <span class="input-group-text bg-light">s/d</span>
                            <input type="date" name="tanggal_selesai" class="form-control" value="{{ request('tanggal_selesai') }}">
                        </div>
                    </div>

                    {{-- Tombol --}}
                    <div class="col-12 d-flex justify-content-end gap-2 pt-2 border-top">
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="fas fa-search me-1"></i> Terapkan Filter
                        </button>
                        <a href="{{ route('activity-log.index') }}" class="btn btn-outline-secondary btn-sm px-3">
                            <i class="fas fa-undo me-1"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabel Log --}}
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold"><i class="fas fa-list me-2 text-primary"></i>Daftar Log Aktivitas</span>
            <span class="badge bg-secondary">Total: {{ $logs->total() }} entri</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle small">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width:50px">#</th>
                            <th style="width:160px">Waktu</th>
                            <th style="width:140px">User</th>
                            <th style="width:130px">Cabang User</th>
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
                            <td class="text-center text-muted">
                                {{ ($logs->currentPage() - 1) * $logs->perPage() + $index + 1 }}
                            </td>
                            <td class="text-nowrap">
                                {{ $log->created_at?->format('d/m/Y H:i:s') ?? '-' }}
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">{{ $log->username }}</span>
                            </td>
                            <td>
                                @if($log->user && $log->user->cabangs->count() > 0)
                                    <span class="badge bg-light text-dark border">
                                        {{ $log->user->cabangs->pluck('nama')->implode(', ') }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge
                                    @if($log->role === 'Super Admin') bg-danger
                                    @elseif($log->role === 'Admin') bg-warning text-dark
                                    @elseif($log->role === 'IT') bg-info text-dark
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
                            <td>{{ $log->description }}</td>
                            <td class="text-muted font-monospace">{{ $log->ip_address ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
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
        <div class="d-flex justify-content-between align-items-center p-3 border-top bg-light small flex-wrap gap-2">
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

@section('scripts')
<script id="cabangsDataActivity" type="application/json">{!! json_encode(isset($cabangs) ? $cabangs->map(fn($c) => ['id' => $c->id, 'nama' => $c->nama])->values()->toArray() : []) !!}</script>
<script>
const allActivityCabangs = JSON.parse(document.getElementById('cabangsDataActivity')?.textContent || '[]');

function selectActivityCabang(id, nama) {
    const inputId = document.getElementById('activityCabangId');
    const inputNama = document.getElementById('activityCabangInput');
    if (inputId) inputId.value = id;
    if (inputNama) inputNama.value = nama;
}

function updateActivityCabangDropdown() {
    const input = document.getElementById('activityCabangInput');
    const dropdown = document.getElementById('activityCabangDropdown');
    const hiddenId = document.getElementById('activityCabangId');
    if (!input || !dropdown) return;

    const val = input.value.trim().toLowerCase();
    const matched = val 
        ? allActivityCabangs.filter(c => c.nama.toLowerCase().includes(val))
        : allActivityCabangs;

    // Reset hidden ID if input cleared or changed
    const exactMatch = allActivityCabangs.find(c => c.nama.toLowerCase() === val);
    if (exactMatch && hiddenId) {
        hiddenId.value = exactMatch.id;
    } else if (!val && hiddenId) {
        hiddenId.value = '';
    }

    let menuHtml = '<li><a class="dropdown-item small text-muted" href="javascript:void(0)" onclick="selectActivityCabang(\'\', \'\')">-- Semua Cabang --</a></li><li><hr class="dropdown-divider"></li>';
    if (matched.length > 0) {
        matched.forEach(c => {
            const safeNama = c.nama.replace(/'/g, "\\'");
            menuHtml += `<li><a class="dropdown-item small" href="javascript:void(0)" onclick="selectActivityCabang('${c.id}', '${safeNama}')">${c.nama}</a></li>`;
        });
    } else {
        menuHtml += '<li><span class="dropdown-item text-muted small">Tidak ada cabang cocok</span></li>';
    }
    dropdown.innerHTML = menuHtml;
}

document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('activityCabangInput');
    if (input) {
        input.addEventListener('input', updateActivityCabangDropdown);
        input.addEventListener('focus', updateActivityCabangDropdown);
    }
});
</script>
@endsection
