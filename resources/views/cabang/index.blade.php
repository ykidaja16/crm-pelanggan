@extends('layouts.main')

@section('title', 'Manajemen Cabang - Medical Lab CRM')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="text-primary mb-0 fw-semibold">
        <i class="fas fa-building me-2"></i>Manajemen Cabang
    </h4>
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

<div class="row g-4">
    <!-- Form Tambah Cabang Baru -->
    <div class="col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-semibold text-success">
                    <i class="fas fa-plus-circle me-2"></i>Buka Cabang Baru
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('cabang.store') }}" class="row g-3">
                    @csrf

                    <div class="col-12">
                        <label class="form-label fw-medium">Kode Cabang <span class="text-danger">*</span></label>
                        <input type="text"
                               name="kode"
                               class="form-control text-uppercase @error('kode') is-invalid @enderror"
                               maxlength="2"
                               placeholder="Contoh: LX"
                               value="{{ old('kode') }}"
                               required
                               style="letter-spacing: 4px; font-weight: bold; font-size: 1.1rem;">
                        <div class="form-text text-muted small">
                            <i class="fas fa-info-circle me-1"></i>
                            2 huruf kapital, digunakan sebagai prefix PID pelanggan (contoh: <strong>LX</strong>00001).
                            <strong class="text-danger">Tidak dapat diubah setelah disimpan.</strong>
                        </div>
                        @error('kode')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-medium">Nama Cabang <span class="text-danger">*</span></label>
                        <input type="text"
                               name="nama"
                               class="form-control @error('nama') is-invalid @enderror"
                               placeholder="Contoh: Ciliwung"
                               value="{{ old('nama') }}"
                               required>
                        @error('nama')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-medium">Tipe <span class="text-danger">*</span></label>
                        <select name="tipe" class="form-select @error('tipe') is-invalid @enderror" required>
                            <option value="">Pilih Tipe</option>
                            <option value="cabang" {{ old('tipe') === 'cabang' ? 'selected' : '' }}>Cabang</option>
                            <option value="regional" {{ old('tipe') === 'regional' ? 'selected' : '' }}>Regional</option>
                        </select>
                        @error('tipe')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-medium">Keterangan</label>
                        <textarea name="keterangan"
                                  class="form-control @error('keterangan') is-invalid @enderror"
                                  rows="2"
                                  placeholder="Keterangan tambahan (opsional)">{{ old('keterangan') }}</textarea>
                        @error('keterangan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-plus me-2"></i>Buka Cabang Baru
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Info Card -->
        <div class="card shadow-sm border-0 border-start border-4 border-warning mt-3">
            <div class="card-body py-3">
                <h6 class="fw-semibold text-warning mb-2">
                    <i class="fas fa-exclamation-triangle me-2"></i>Catatan Penting
                </h6>
                <ul class="mb-0 small text-muted ps-3">
                    <li>Kode cabang <strong>tidak dapat diubah</strong> setelah disimpan.</li>
                    <li>Kode cabang digunakan sebagai 2 karakter pertama PID pelanggan.</li>
                    <li>Contoh: Kode <strong>LX</strong> → PID pelanggan: <code>LX00001</code>, <code>LX00002</code>, dst.</li>
                    <li>Cabang hanya dapat dihapus jika tidak ada pelanggan terdaftar.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Daftar Cabang -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-semibold text-primary">
                    <i class="fas fa-list me-2"></i>Daftar Cabang ({{ $cabangs->count() }})
                </h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 70px;">Kode</th>
                            <th>Nama Cabang</th>
                            <th class="text-center" style="width: 90px;">Tipe</th>
                            <th>Keterangan</th>
                            <th class="text-center" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cabangs as $cabang)
                            <tr>
                                <td class="text-center">
                                    <span class="badge bg-primary fs-6 px-3 py-2" style="letter-spacing: 2px;">
                                        {{ $cabang->kode }}
                                    </span>
                                </td>
                                <td class="fw-semibold">{{ $cabang->nama }}</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $cabang->tipe === 'regional' ? 'info' : 'secondary' }} bg-opacity-10 text-{{ $cabang->tipe === 'regional' ? 'info' : 'secondary' }} border border-{{ $cabang->tipe === 'regional' ? 'info' : 'secondary' }}">
                                        {{ ucfirst($cabang->tipe) }}
                                    </span>
                                </td>
                                <td class="small text-muted">{{ $cabang->keterangan ?? '-' }}</td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary me-1"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editModal{{ $cabang->id }}"
                                            title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" action="{{ route('cabang.destroy', $cabang->id) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('Yakin ingin menghapus cabang {{ $cabang->nama }}?\nCabang hanya bisa dihapus jika tidak ada pelanggan terdaftar.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Modal Edit Cabang -->
                            <div class="modal fade" id="editModal{{ $cabang->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-semibold">
                                                <i class="fas fa-edit me-2"></i>Edit Cabang
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="{{ route('cabang.update', $cabang->id) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label fw-medium">Kode Cabang</label>
                                                    <input type="text"
                                                           class="form-control bg-light text-uppercase fw-bold"
                                                           value="{{ $cabang->kode }}"
                                                           disabled
                                                           style="letter-spacing: 4px;">
                                                    <div class="form-text text-danger small">
                                                        <i class="fas fa-lock me-1"></i>Kode tidak dapat diubah.
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-medium">Nama Cabang <span class="text-danger">*</span></label>
                                                    <input type="text"
                                                           name="nama"
                                                           class="form-control"
                                                           value="{{ $cabang->nama }}"
                                                           required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-medium">Tipe <span class="text-danger">*</span></label>
                                                    <select name="tipe" class="form-select" required>
                                                        <option value="cabang" {{ $cabang->tipe === 'cabang' ? 'selected' : '' }}>Cabang</option>
                                                        <option value="regional" {{ $cabang->tipe === 'regional' ? 'selected' : '' }}>Regional</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-medium">Keterangan</label>
                                                    <textarea name="keterangan" class="form-control" rows="2">{{ $cabang->keterangan }}</textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save me-1"></i>Simpan Perubahan
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-building fa-2x mb-3 text-secondary opacity-50"></i>
                                    <p class="mb-0">Belum ada cabang terdaftar.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Auto uppercase kode input
document.querySelector('input[name="kode"]').addEventListener('input', function() {
    this.value = this.value.toUpperCase().replace(/[^A-Z]/g, '');
});
</script>
@endsection
