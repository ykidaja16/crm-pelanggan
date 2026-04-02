@extends('layouts.main')

@section('title', 'Riwayat Import - SIMA Lab')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold"><i class="fas fa-undo-alt me-2 text-primary"></i>Riwayat Import</h4>
            <small class="text-muted">Daftar sesi import data CSV/Excel beserta opsi rollback</small>
        </div>
    </div>

    {{-- Info Box --}}
    <div class="alert alert-info border-0 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-start gap-2">
            <i class="fas fa-info-circle mt-1 flex-shrink-0"></i>
            <div>
                <strong>Cara Kerja Rollback:</strong>
                Rollback akan <strong>menghapus semua kunjungan</strong> yang dibuat dari sesi import tersebut,
                dan <strong>mengembalikan data pelanggan</strong> ke kondisi sebelum import (total kedatangan, total biaya, dan kelas).
                Pelanggan baru yang dibuat saat import akan di-nonaktifkan (soft delete).
                <br><small class="text-muted mt-1 d-block">⚠️ Rollback hanya bisa dilakukan <strong>satu kali</strong> per sesi import dan tidak dapat dibatalkan.</small>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-2"></i>Filter Riwayat Import
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('import-batch.index') }}" id="filterForm">
                <div class="row g-3">
                    {{-- Filter Status --}}
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="">-- Semua Status --</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>
                                ✅ Completed
                            </option>
                            <option value="rolled_back" {{ request('status') === 'rolled_back' ? 'selected' : '' }}>
                                🔄 Rolled Back
                            </option>
                        </select>
                    </div>

                    {{-- Filter Cabang --}}
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Cabang</label>
                        <select name="cabang_id" class="form-select">
                            <option value="">-- Semua Cabang --</option>
                            @foreach($cabangs as $cabang)
                                <option value="{{ $cabang->id }}"
                                    {{ request('cabang_id') == $cabang->id ? 'selected' : '' }}>
                                    {{ $cabang->nama }}
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
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="d-flex gap-2 w-100">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="fas fa-search me-1"></i> Cari
                            </button>
                            <a href="{{ route('import-batch.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabel Riwayat Import --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list me-2"></i>Daftar Sesi Import</span>
            <span class="badge bg-secondary">Total: {{ $batches->total() }} sesi</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width:50px">#</th>
                            <th style="min-width:180px">Nama File</th>
                            <th style="width:130px">Cabang</th>
                            <th style="width:140px">Diimport Oleh</th>
                            <th style="width:150px">Tanggal Import</th>
                            <th class="text-center" style="width:100px">Total Baris</th>
                            <th class="text-center" style="width:110px">Status</th>
                            <th style="width:140px">Di-rollback Oleh</th>
                            <th style="width:150px">Tanggal Rollback</th>
                            <th class="text-center" style="width:100px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batches as $index => $batch)
                        <tr>
                            <td class="text-center text-muted small">
                                {{ ($batches->currentPage() - 1) * $batches->perPage() + $index + 1 }}
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-file-csv text-success"></i>
                                    <div>
                                        <div class="fw-semibold small">{{ $batch->filename }}</div>
                                        <div class="text-muted" style="font-size:0.72rem; font-family:monospace;">
                                            {{ substr($batch->batch_id, 0, 8) }}...
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="fas fa-building me-1"></i>
                                    {{ $batch->cabang?->nama ?? '-' }}
                                </span>
                            </td>
                            <td class="small">
                                {{ $batch->user?->name ?? '-' }}
                            </td>
                            <td class="small text-nowrap">
                                {{ $batch->imported_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary rounded-pill">
                                    {{ number_format($batch->total_rows) }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($batch->isRolledBack())
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-undo me-1"></i>Rolled Back
                                    </span>
                                @else
                                    <span class="badge bg-success">
                                        <i class="fas fa-check me-1"></i>Completed
                                    </span>
                                @endif
                            </td>
                            <td class="small">
                                {{ $batch->rolledBackByUser?->name ?? '-' }}
                            </td>
                            <td class="small text-nowrap">
                                {{ $batch->rolled_back_at?->format('d/m/Y H:i') ?? '-' }}
                            </td>
                            <td class="text-center">
                                @if(!$batch->isRolledBack())
                                    <button type="button"
                                            class="btn btn-danger btn-sm"
                                            title="Rollback import ini"
                                            onclick="confirmRollback(
                                                '{{ $batch->batch_id }}',
                                                '{{ addslashes($batch->filename) }}',
                                                {{ $batch->total_rows }}
                                            )">
                                        <i class="fas fa-undo-alt me-1"></i>Rollback
                                    </button>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                Tidak ada riwayat import ditemukan
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="d-flex justify-content-between align-items-center p-3 border-top bg-light small">
            <div class="text-muted">
                Menampilkan
                <strong>{{ $batches->firstItem() ?? 0 }} - {{ $batches->lastItem() ?? 0 }}</strong>
                dari <strong>{{ $batches->total() }}</strong> sesi import
            </div>
            <div>
                {{ $batches->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

{{-- Modal Konfirmasi Rollback --}}
<div class="modal fade" id="rollbackModal" tabindex="-1" aria-labelledby="rollbackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="rollbackModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Rollback Import
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning border-0 mb-3">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Perhatian!</strong> Tindakan ini <strong>tidak dapat dibatalkan</strong>.
                </div>
                <p class="mb-2">Anda akan melakukan rollback untuk:</p>
                <ul class="mb-3">
                    <li>File: <strong id="modalFilename">-</strong></li>
                    <li>Total baris: <strong id="modalTotalRows">-</strong> data</li>
                </ul>
                <p class="mb-0 text-muted small">
                    Semua kunjungan dari sesi import ini akan dihapus, dan data pelanggan akan dikembalikan ke kondisi sebelum import.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Batal
                </button>
                <form id="rollbackForm" method="POST" action="">
                    @csrf
                    <button type="submit" class="btn btn-danger" id="rollbackSubmitBtn">
                        <i class="fas fa-undo-alt me-1"></i>Ya, Rollback Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function confirmRollback(batchId, filename, totalRows) {
        // Isi data ke modal
        document.getElementById('modalFilename').textContent  = filename;
        document.getElementById('modalTotalRows').textContent = totalRows.toLocaleString('id-ID');

        // Set action form ke URL rollback yang benar
        const url = '{{ url("/import-batches") }}/' + batchId + '/rollback';
        document.getElementById('rollbackForm').action = url;

        // Tampilkan modal
        const modal = new bootstrap.Modal(document.getElementById('rollbackModal'));
        modal.show();
    }

    // Disable tombol submit saat form dikirim (cegah double submit)
    document.getElementById('rollbackForm').addEventListener('submit', function () {
        const btn = document.getElementById('rollbackSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';
    });
</script>
@endsection
