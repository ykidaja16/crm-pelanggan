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

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('approval.pelanggan') }}" class="row g-2 align-items-end">
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
                    <a href="{{ route('approval.pelanggan') }}" class="btn btn-outline-secondary btn-sm px-3">
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
                    <p class="mb-0">Tidak ada pengajuan approval pelanggan.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width:50px">No</th>
                                <th>Tipe Aksi</th>
                                <th>Diajukan Oleh</th>
                                <th>Ditugaskan Ke</th>
                                <th>Tanggal Pengajuan</th>
                                <th>Status</th>
                                <th class="text-center" style="width:90px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requests as $i => $item)
                            <tr>
                                <td class="ps-3 text-muted small">{{ $requests->firstItem() + $i }}</td>
                                <td>
                                    @if($item->action === 'edit')
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-edit me-1"></i>Edit Data
                                        </span>
                                    @elseif($item->action === 'delete')
                                        <span class="badge bg-danger text-dark">
                                            <i class="fas fa-trash me-1"></i>Hapus
                                        </span>
                                    @elseif($item->action === 'bulk_delete')
                                        <span class="badge bg-danger text-dark">
                                            <i class="fas fa-trash-alt me-1"></i>Hapus Massal
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">{{ $item->action }}</span>
                                    @endif
                                </td>
                                <td class="small">{{ $item->requester?->name ?? $item->requester?->username ?? '-' }}</td>
                                <td class="small">
                                    @if($item->assignedTo)
                                        <span class="text-primary">
                                            <i class="fas fa-user-shield me-1"></i>
                                            {{ $item->assignedTo->name ?? $item->assignedTo->username }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $item->created_at?->format('d-m-Y H:i') }}</td>
                                <td>
                                    @if($item->status === 'pending')
                                        <span class="badge bg-warning text-dark">PENDING</span>
                                    @elseif($item->status === 'approved')
                                        <span class="badge bg-success">APPROVED</span>
                                    @else
                                        <span class="badge bg-danger">REJECTED</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button type="button"
                                            class="btn btn-outline-primary btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalInfo{{ $item->id }}"
                                            title="Lihat Informasi">
                                        <i class="fas fa-info-circle"></i>
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

{{-- ─── Modal Info per Request ─────────────────────────────────────────────── --}}
@foreach($requests as $item)
@php
    $payload = $item->payload ?? [];
    $orig    = $payload['original_data'] ?? null;   // data sebelum perubahan
    // Data sesudah = payload itu sendiri (minus original_data)
    $nd      = collect($payload)->except('original_data')->toArray();
@endphp

<div class="modal fade" id="modalInfo{{ $item->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog {{ $item->action === 'edit' ? 'modal-xl' : 'modal-lg' }} modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">

            {{-- Header --}}
            <div class="modal-header py-3
                @if($item->action === 'edit') bg-warning text-dark
                @elseif(in_array($item->action, ['delete','bulk_delete'])) bg-danger text-white
                @else bg-primary text-white @endif">
                <h5 class="modal-title fw-semibold">
                    @if($item->action === 'edit')
                        <i class="fas fa-edit me-2"></i>Detail Pengajuan Edit Pelanggan
                    @elseif($item->action === 'delete')
                        <i class="fas fa-trash me-2"></i>Detail Pengajuan Hapus Pelanggan
                    @elseif($item->action === 'bulk_delete')
                        <i class="fas fa-trash-alt me-2"></i>Detail Pengajuan Hapus Massal
                    @else
                        <i class="fas fa-info-circle me-2"></i>Detail Pengajuan
                    @endif
                </h5>
                <button type="button" class="btn-close {{ $item->action === 'edit' ? '' : 'btn-close-white' }}" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">

                {{-- ── EDIT: 3 kolom Identitas | Sebelum | Sesudah ─────────────── --}}
                @if($item->action === 'edit')
                @php
                    // Identitas pelanggan: ambil dari data original jika ada, fallback ke payload
                    $identitas = $orig ?? $nd;
                    $cabangIdIdentitas = $identitas['cabang_id'] ?? null;
                    $cabangNamaIdentitas = $cabangIdIdentitas && isset($cabangs[$cabangIdIdentitas])
                        ? $cabangs[$cabangIdIdentitas]->nama
                        : '-';

                    // Cabang sesudah
                    $cabangIdNd = $nd['cabang_id'] ?? null;
                    $cabangNamaNd = $cabangIdNd && isset($cabangs[$cabangIdNd])
                        ? $cabangs[$cabangIdNd]->nama
                        : '-';
                @endphp

                <div class="row g-3">
                    {{-- Kolom 1: Identitas Pelanggan --}}
                    <div class="col-md-4">
                        <div class="card border-primary h-100">
                            <div class="card-header bg-primary text-white py-2 small fw-semibold">
                                <i class="fas fa-id-card me-1"></i>Identitas Pelanggan
                            </div>
                            <div class="card-body p-3">
                                <table class="table table-sm table-borderless mb-0 small">
                                    <tr>
                                        <td class="text-muted fw-medium" style="width:40%">PID</td>
                                        <td>
                                            <code class="bg-light px-1 rounded">{{ $identitas['pid'] ?? '-' }}</code>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-medium">Nama</td>
                                        <td class="fw-semibold">{{ $identitas['nama'] ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-medium">Cabang</td>
                                        <td>{{ $cabangNamaIdentitas }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Kolom 2: Data Sebelum --}}
                    <div class="col-md-4">
                        <div class="card border-secondary h-100">
                            <div class="card-header bg-secondary text-white py-2 small fw-semibold">
                                <i class="fas fa-history me-1"></i>Data Sebelum
                            </div>
                            <div class="card-body p-3">
                                @if($orig)
                                    <table class="table table-sm table-borderless mb-0 small">
                                        <tr>
                                            <td class="text-muted fw-medium" style="width:40%">Nama</td>
                                            <td>{{ $orig['nama'] ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted fw-medium">No. Telepon</td>
                                            <td>{{ $orig['no_telp'] ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted fw-medium">Tgl. Lahir</td>
                                            <td>
                                                {{ $orig['dob']
                                                    ? \Carbon\Carbon::parse($orig['dob'])->format('d-m-Y')
                                                    : '-' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted fw-medium">Alamat</td>
                                            <td>{{ $orig['alamat'] ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted fw-medium">Kota</td>
                                            <td>{{ $orig['kota'] ?? '-' }}</td>
                                        </tr>
                                    </table>
                                @else
                                    <div class="text-muted small fst-italic">Data sebelum tidak tersedia.</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Kolom 3: Data Sesudah --}}
                    <div class="col-md-4">
                        <div class="card border-success h-100">
                            <div class="card-header bg-success text-white py-2 small fw-semibold">
                                <i class="fas fa-arrow-right me-1"></i>Data Sesudah (Diajukan)
                            </div>
                            <div class="card-body p-3">
                                <table class="table table-sm table-borderless mb-0 small">
                                    <tr>
                                        <td class="text-muted fw-medium" style="width:40%">PID</td>
                                        <td>
                                            <code class="bg-light px-1 rounded">{{ $nd['pid'] ?? '-' }}</code>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-medium">Cabang</td>
                                        <td>{{ $cabangNamaNd }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-medium">Nama</td>
                                        <td class="fw-semibold">{{ $nd['nama'] ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-medium">No. Telepon</td>
                                        <td>{{ $nd['no_telp'] ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-medium">Tgl. Lahir</td>
                                        <td>
                                            {{ isset($nd['dob']) && $nd['dob']
                                                ? \Carbon\Carbon::parse($nd['dob'])->format('d-m-Y')
                                                : '-' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-medium">Alamat</td>
                                        <td>{{ $nd['alamat'] ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-medium">Kota</td>
                                        <td>{{ $nd['kota'] ?? '-' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Alasan Pengajuan --}}
                <div class="mt-3">
                    <h6 class="fw-semibold small text-muted text-uppercase mb-1">
                        <i class="fas fa-clipboard me-1"></i>Alasan Pengajuan
                    </h6>
                    <div class="bg-light rounded p-2 border small text-muted" style="min-height:50px;">
                        {{ $item->request_note ?? '-' }}
                    </div>
                </div>

                @endif {{-- end action === 'edit' --}}

                {{-- ── DELETE: info pelanggan + alasan ─────────────────────────── --}}
                @if($item->action === 'delete')
                @php
                    $pelangganTarget = \App\Models\Pelanggan::withTrashed()->find($item->target_id);
                @endphp
                <div class="alert alert-danger border-0 mb-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Pengajuan hapus data pelanggan.</strong>
                </div>
                @if($pelangganTarget)
                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="row g-2 small">
                            <div class="col-md-4">
                                <span class="text-muted">PID:</span>
                                <code class="ms-1">{{ $pelangganTarget->pid }}</code>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted">Nama:</span>
                                <strong class="ms-1">{{ $pelangganTarget->nama }}</strong>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted">Cabang:</span>
                                <span class="ms-1">{{ $pelangganTarget->cabang?->nama ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-muted small fst-italic mb-3">Data pelanggan tidak ditemukan (mungkin sudah dihapus).</div>
                @endif
                <div>
                    <h6 class="fw-semibold small text-muted text-uppercase mb-1">
                        <i class="fas fa-clipboard me-1"></i>Alasan Pengajuan
                    </h6>
                    <div class="bg-light rounded p-2 border small text-muted" style="min-height:50px;">
                        {{ $item->request_note ?? '-' }}
                    </div>
                </div>
                @endif {{-- end action === 'delete' --}}

                {{-- ── BULK DELETE: info jumlah + alasan ───────────────────────── --}}
                @if($item->action === 'bulk_delete')
                @php
                    $bulkCount = $payload['count'] ?? count($payload['ids'] ?? []);
                    $bulkIds   = $payload['ids'] ?? [];
                @endphp
                <div class="alert alert-danger border-0 mb-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Pengajuan hapus massal {{ $bulkCount }} pelanggan.</strong>
                </div>
                @if(!empty($bulkIds))
                    <div class="p-3 bg-light rounded border mb-3 small">
                        <span class="text-muted">ID Pelanggan yang akan dihapus:</span>
                        <div class="mt-1">
                            @foreach(array_slice($bulkIds, 0, 20) as $bid)
                                <span class="badge bg-secondary me-1 mb-1">{{ $bid }}</span>
                            @endforeach
                            @if(count($bulkIds) > 20)
                                <span class="text-muted">... dan {{ count($bulkIds) - 20 }} lainnya</span>
                            @endif
                        </div>
                    </div>
                @endif
                <div>
                    <h6 class="fw-semibold small text-muted text-uppercase mb-1">
                        <i class="fas fa-clipboard me-1"></i>Alasan Pengajuan
                    </h6>
                    <div class="bg-light rounded p-2 border small text-muted" style="min-height:50px;">
                        {{ $item->request_note ?? '-' }}
                    </div>
                </div>
                @endif {{-- end action === 'bulk_delete' --}}

                {{-- ── Info Pengaju, Ditugaskan, Status ────────────────────────── --}}
                <div class="mt-3 p-3 bg-light rounded border">
                    <div class="row g-2 small">
                        <div class="col-md-3">
                            <span class="text-muted">Diajukan oleh:</span>
                            <strong class="ms-1">{{ $item->requester?->name ?? $item->requester?->username ?? '-' }}</strong>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted">Ditugaskan ke:</span>
                            <strong class="ms-1 text-primary">
                                {{ $item->assignedTo?->name ?? $item->assignedTo?->username ?? '-' }}
                            </strong>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted">Tanggal Pengajuan:</span>
                            <strong class="ms-1">{{ $item->created_at?->format('d-m-Y H:i') ?? '-' }}</strong>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted">Status:</span>
                            @if($item->status === 'pending')
                                <span class="badge bg-warning text-dark ms-1">PENDING</span>
                            @elseif($item->status === 'approved')
                                <span class="badge bg-success ms-1">APPROVED</span>
                            @else
                                <span class="badge bg-danger ms-1">REJECTED</span>
                            @endif
                        </div>
                        @if($item->status !== 'pending')
                            <div class="col-md-6">
                                <span class="text-muted">Direview oleh:</span>
                                <strong class="ms-1">{{ $item->reviewer?->name ?? $item->reviewer?->username ?? '-' }}</strong>
                            </div>
                            <div class="col-md-6">
                                <span class="text-muted">Catatan Keputusan:</span>
                                <span class="ms-1">{{ $item->decision_note ?? '-' }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ── Form Approve/Reject (hanya untuk Super Admin & status pending) ── --}}
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
