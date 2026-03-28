@extends('layouts.main')

@section('title', 'Detail Pelanggan - Medical Lab CRM')

@section('content')
@php $role = Auth::user()->role?->name; @endphp
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-primary mb-0 fw-semibold">Detail Pelanggan</h3>
        <a href="{{ route('pelanggan.index') }}" class="btn btn-outline-secondary btn-lg">
            <i class="fas fa-arrow-left me-2"></i> Kembali
        </a>
    </div>

    <!-- Customer Info Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="mb-0 fw-semibold text-primary">    
                <i class="fas fa-user me-2"></i>Data Pelanggan
            </h5>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="text-muted small fw-medium text-uppercase">PID</label>
                        <div class="fs-5 fw-semibold">
                            <code class="bg-light px-2 py-1 rounded">{{ $pelanggan->pid }}</code>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-medium text-uppercase">Nama Lengkap</label>
                        <div class="fs-5 fw-semibold">{{ $pelanggan->nama }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-medium text-uppercase">Alamat</label>
                        <div class="fs-6">{{ $pelanggan->alamat ?? '-' }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-medium text-uppercase">Kota</label>
                        <div class="fs-6">{{ $pelanggan->kota ?? '-' }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="text-muted small fw-medium text-uppercase">Klasifikasi</label>
                        <div class="fs-5">
                            @if ($pelanggan->class == 'Prioritas')
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-3 py-2 fs-6">PRIORITAS</span>
                            @elseif($pelanggan->class == 'Loyal')
                                <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2 fs-6">LOYAL</span>
                            @elseif($pelanggan->class == 'Potensial')
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-3 py-2 fs-6">POTENSIAL</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary px-3 py-2 fs-6">-</span>
                            @endif
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-medium text-uppercase">Total Transaksi</label>
                        <div class="fs-4 fw-bold text-success">Rp {{ number_format($totalTransaksi, 0, ',', '.') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-medium text-uppercase">Total Kunjungan</label>
                        <div class="fs-5 fw-semibold">
                            <span class="badge bg-info bg-opacity-10 text-info border border-info px-3 py-2">{{ $pelanggan->total_kedatangan }} kali</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-medium text-uppercase">Cabang</label>
                        <div class="fs-6">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary">{{ $pelanggan->cabang?->nama ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Class History Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold text-warning">
                <i class="fas fa-chart-line me-2"></i>Riwayat Perubahan Kelas
            </h5>
            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-3 py-2">
                {{ $classHistories->count() }} Perubahan
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-3">No</th>
                            <th class="py-3">Diubah User Tanggal</th>
                            <th class="py-3">Tanggal Perubahan Kelas</th>
                            <th class="py-3">Perubahan Kelas</th>
                            <th class="py-3">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($classHistories as $index => $history)
                            <tr>
                                <td class="px-4">{{ $index + 1 }}</td>
                                <td>{{ $history->created_at ? $history->created_at->format('d-m-Y H:i') : '-' }}</td>
                                <td>{{ $history->changed_at ? $history->changed_at->format('d-m-Y H:i') : '-' }}</td>
                                <td>
                                    @if ($history->previous_class)
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">{{ $history->previous_class }}</span>
                                        <i class="fas fa-arrow-right mx-2 text-muted"></i>
                                    @else
                                        <span class="text-muted">-</span>
                                        <i class="fas fa-arrow-right mx-2 text-muted"></i>
                                    @endif
                                    @if ($history->new_class == 'Prioritas')
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">PRIORITAS</span>
                                    @elseif($history->new_class == 'Loyal')
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success">LOYAL</span>
                                    @elseif($history->new_class == 'Potensial')
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">POTENSIAL</span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">{{ $history->new_class }}</span>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $history->reason }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-3 text-secondary opacity-50"></i>
                                    <p class="mb-0">Belum ada riwayat perubahan kelas.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- Pagination Riwayat Perubahan Kelas -->
            <div class="d-flex justify-content-between align-items-center p-3 border-top bg-light small">
                <div class="text-muted">
                    Menampilkan <strong>{{ $classHistories->firstItem() ?? 0 }} - {{ $classHistories->lastItem() ?? 0 }}</strong> dari <strong>{{ $classHistories->total() }}</strong> data
                </div>
                <div>
                    {{ $classHistories->links('pagination::bootstrap-5') }}
                </div>
            </div>

        </div>
    </div>


    <!-- Visit History Card -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold text-info">
                <i class="fas fa-history me-2"></i>Riwayat Kunjungan
            </h5>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-info bg-opacity-10 text-info border border-info px-3 py-2">
                    {{ $kunjungans->count() }} Kunjungan
                </span>
                @if(in_array($role, ['Admin', 'Super Admin']))
                <a href="{{ route('pelanggan.export-kunjungan', $pelanggan->id) }}"
                   class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel me-1"></i>Export Excel
                </a>
                @endif
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-3">No</th>
                            <th class="py-3">Tanggal Kunjungan</th>
                            <th class="py-3 text-center">Total Kedatangan</th>
                            <th class="py-3">Biaya</th>
                            <th class="py-3 text-center">Kelompok Pelanggan</th>
                            <th class="py-3 text-center">Status</th>
                            <th class="py-3 text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($kunjungans as $index => $k)
                            @php
                                $hasPending = $pendingApprovals->has($k->id);
                                $pendingGroup = $hasPending ? $pendingApprovals->get($k->id) : collect();
                                $pendingAction = $pendingGroup->first()?->action;
                            @endphp
                            <tr>
                                <td class="px-4">{{ $index + 1 }}</td>
                                <td>{{ \Carbon\Carbon::parse($k->tanggal_kunjungan)->format('d-m-Y') }}</td>
                                <td class="text-center">
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info px-2 py-1">{{ $k->total_kedatangan }} kali</span>
                                </td>
                                <td class="fw-semibold">Rp {{ number_format($k->biaya, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    @php
                                        $kelompokKode = strtolower($k->kelompokPelanggan->kode ?? 'mandiri');
                                        $isKlinisi = $kelompokKode === 'klinisi';
                                    @endphp
                                    <span class="badge {{ $isKlinisi ? 'bg-primary bg-opacity-10 text-primary border border-primary' : 'bg-secondary bg-opacity-10 text-secondary border border-secondary' }}">
                                        {{ $isKlinisi ? 'Klinisi' : 'Mandiri' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($hasPending)
                                        @if($pendingAction === 'delete')
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">
                                                <i class="fas fa-clock me-1"></i>Menunggu Hapus
                                            </span>
                                        @else
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">
                                                <i class="fas fa-clock me-1"></i>Menunggu Edit
                                            </span>
                                        @endif
                                    @else
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success">Selesai</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                @if($role === 'User')
                                        {{-- Point 5: User tidak boleh edit/delete kunjungan --}}
                                        <span class="text-muted small"><i class="fas fa-eye me-1"></i>View Only</span>
                                    @elseif($hasPending)
                                        <span class="text-muted small">
                                            <i class="fas fa-lock me-1"></i>Terkunci
                                        </span>
                                    @else
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('kunjungan.edit', $k->id) }}" class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button"
                                                    class="btn btn-sm btn-danger"
                                                    title="Hapus"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteKunjunganModal{{ $k->id }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>

                                        <!-- Modal Hapus Kunjungan -->
                                        <div class="modal fade" id="deleteKunjunganModal{{ $k->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form action="{{ route('approval.kunjungan.delete.store', $k->id) }}" method="POST">
                                                        @csrf

                                                        <div class="modal-header">
                                                            <h5 class="modal-title text-danger">
                                                                <i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus Kunjungan
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>

                                                        <div class="modal-body">
                                                            <p class="mb-3">
                                                                Anda akan menghapus kunjungan tanggal
                                                                <strong>{{ \Carbon\Carbon::parse($k->tanggal_kunjungan)->format('d-m-Y') }}</strong>
                                                                dengan biaya
                                                                <strong>Rp {{ number_format($k->biaya, 0, ',', '.') }}</strong>.
                                                            </p>

                                                            <label for="request_note_{{ $k->id }}" class="form-label fw-semibold">
                                                                Alasan Pengajuan Hapus <span class="text-danger">*</span>
                                                            </label>
                                                            <textarea
                                                                id="request_note_{{ $k->id }}"
                                                                name="request_note"
                                                                class="form-control"
                                                                rows="3"
                                                                placeholder="Wajib diisi. Contoh: Data kunjungan duplikat / salah input."
                                                                required></textarea>
                                                            <div class="form-text">Alasan akan dikirim sebagai pengajuan approval ke Superadmin dan dicatat di activity log.</div>
                                                        </div>

                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-danger">
                                                                <i class="fas fa-paper-plane me-1"></i> Ajukan Hapus
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-3 text-secondary opacity-50"></i>
                                    <p class="mb-0">Tidak ada riwayat kunjungan.</p>
                                </td>
                            </tr>

                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- Pagination Riwayat Kunjungan -->
            <div class="d-flex justify-content-between align-items-center p-3 border-top bg-light small">
                <div class="text-muted">
                    Menampilkan <strong>{{ $kunjungans->firstItem() ?? 0 }} - {{ $kunjungans->lastItem() ?? 0 }}</strong> dari <strong>{{ $kunjungans->total() }}</strong> data
                </div>
                <div>
                    {{ $kunjungans->links('pagination::bootstrap-5') }}
                </div>
            </div>

        </div>
    </div>

    <!-- Approval History Card -->
    <div class="card shadow-sm border-0 mt-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold text-secondary">
                <i class="fas fa-clipboard-list me-2"></i>Riwayat Pengajuan Perubahan
            </h5>
            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary px-3 py-2">
                {{ $approvalHistories->count() }} Pengajuan
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-3">No</th>
                            <th class="py-3">Tanggal Pengajuan</th>
                            <th class="py-3 text-center">Jenis</th>
                            <th class="py-3">Diajukan Oleh</th>
                            <th class="py-3 text-center">Status</th>
                            <th class="py-3">Catatan Pengajuan</th>
                            <th class="py-3">Catatan Keputusan</th>
                            <th class="py-3">Diproses Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($approvalHistories as $idx => $ar)
                            <tr>
                                <td class="px-4">{{ $idx + 1 }}</td>
                                <td class="small">{{ $ar->created_at ? $ar->created_at->format('d-m-Y H:i') : '-' }}</td>
                                <td class="text-center">
                                    @if($ar->action === 'delete')
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Hapus</span>
                                    @else
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">Edit</span>
                                    @endif
                                </td>
                                <td class="small">{{ $ar->requester?->name ?? $ar->requester?->username ?? '-' }}</td>
                                <td class="text-center">
                                    @if($ar->status === 'pending')
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">
                                            <i class="fas fa-clock me-1"></i>Pending
                                        </span>
                                    @elseif($ar->status === 'approved')
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                            <i class="fas fa-check me-1"></i>Disetujui
                                        </span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">
                                            <i class="fas fa-times me-1"></i>Ditolak
                                        </span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $ar->request_note ?? '-' }}</td>
                                <td class="small text-muted">{{ $ar->decision_note ?? '-' }}</td>
                                <td class="small">{{ $ar->reviewer?->name ?? $ar->reviewer?->username ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-3 text-secondary opacity-50"></i>
                                    <p class="mb-0">Belum ada riwayat pengajuan perubahan.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{-- Pagination Riwayat Pengajuan Perubahan --}}
            <div class="d-flex justify-content-between align-items-center p-3 border-top bg-light small">
                <div class="text-muted">
                    Menampilkan <strong>{{ $approvalHistories->firstItem() ?? 0 }} - {{ $approvalHistories->lastItem() ?? 0 }}</strong>
                    dari <strong>{{ $approvalHistories->total() }}</strong> pengajuan
                </div>
                <div>
                    {{ $approvalHistories->appends(request()->query())->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
@endsection
