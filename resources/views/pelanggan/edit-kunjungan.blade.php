@extends('layouts.main')

@section('title', 'Edit Kunjungan - Medical Lab CRM')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-primary mb-0 fw-semibold">Edit Kunjungan</h3>
        <a href="{{ route('pelanggan.show', $kunjungan->pelanggan_id) }}" class="btn btn-outline-secondary btn-lg">
            <i class="fas fa-arrow-left me-2"></i> Kembali
        </a>
    </div>

    <!-- Info Pelanggan -->
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body p-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <small class="text-muted text-uppercase">Pelanggan</small>
                    <div class="fw-semibold fs-5">{{ $kunjungan->pelanggan->nama }}</div>
                    <code class="bg-white px-2 py-1 rounded">{{ $kunjungan->pelanggan->pid }}</code>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted text-uppercase">Kunjungan Saat Ini</small>
                    <div class="fw-semibold">{{ \Carbon\Carbon::parse($kunjungan->tanggal_kunjungan)->format('d-m-Y') }}</div>
                    <span class="text-success fw-semibold">Rp {{ number_format($kunjungan->biaya, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Edit Kunjungan -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom">
            <h5 class="mb-0 fw-semibold text-warning">
                <i class="fas fa-edit me-2"></i>Form Edit Kunjungan
            </h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('approval.kunjungan.edit.store', $kunjungan->id) }}" method="POST">
                @csrf

                <div class="row g-3">
                    <!-- Tanggal Kunjungan -->
                    <div class="col-md-6">
                        <label for="tanggal_kunjungan" class="form-label fw-semibold">
                            <i class="fas fa-calendar-alt me-1 text-primary"></i> Tanggal Kunjungan <span class="text-danger">*</span>
                        </label>
                        <input type="date" 
                               class="form-control form-control-lg @error('tanggal_kunjungan') is-invalid @enderror" 
                               id="tanggal_kunjungan" 
                               name="tanggal_kunjungan" 
                               value="{{ old('tanggal_kunjungan', \Carbon\Carbon::parse($kunjungan->tanggal_kunjungan)->format('Y-m-d')) }}" 
                               required>
                        @error('tanggal_kunjungan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Biaya -->
                    <div class="col-md-6">
                        <label for="biaya" class="form-label fw-semibold">
                            <i class="fas fa-money-bill-wave me-1 text-success"></i> Biaya (Rp) <span class="text-danger">*</span>
                        </label>
                        <input type="number" 
                               class="form-control form-control-lg @error('biaya') is-invalid @enderror" 
                               id="biaya" 
                               name="biaya" 
                               value="{{ old('biaya', $kunjungan->biaya) }}" 
                               min="0" 
                               step="1"
                               placeholder="Masukkan biaya kunjungan"
                               required>
                        @error('biaya')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text text-muted">
                            Contoh: 1500000 (tanpa titik atau koma)
                        </div>
                    </div>

                    <!-- Kelompok Pelanggan -->
                    <div class="col-md-6">
                        <label for="kelompok_pelanggan" class="form-label fw-semibold">
                            <i class="fas fa-users me-1 text-info"></i> Kelompok Pelanggan <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-lg @error('kelompok_pelanggan') is-invalid @enderror"
                                id="kelompok_pelanggan"
                                name="kelompok_pelanggan"
                                required>
                            <option value="mandiri" {{ old('kelompok_pelanggan', $kunjungan->kelompokPelanggan?->kode ?? 'mandiri') === 'mandiri' ? 'selected' : '' }}>
                                Mandiri
                            </option>
                            <option value="klinisi" {{ old('kelompok_pelanggan', $kunjungan->kelompokPelanggan?->kode ?? 'mandiri') === 'klinisi' ? 'selected' : '' }}>
                                Klinisi
                            </option>
                        </select>
                        @error('kelompok_pelanggan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text text-muted">
                            Pilih jenis kelompok pelanggan untuk kunjungan ini.
                        </div>
                    </div>

                </div>

                <!-- Alasan Perubahan -->
                <div class="row g-3 mt-1">
                    <div class="col-12">
                        <label for="alasan_perubahan" class="form-label fw-semibold">
                            <i class="fas fa-comment-dots me-1 text-danger"></i> Alasan Perubahan <span class="text-danger">*</span>
                        </label>
                        <textarea
                            class="form-control @error('request_note') is-invalid @enderror"
                            id="request_note"
                            name="request_note"
                            rows="3"
                            placeholder="Wajib diisi. Contoh: Koreksi salah input biaya/tanggal kunjungan."
                            required>{{ old('request_note') }}</textarea>
                        @error('request_note')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text text-muted">
                            Alasan ini akan dikirim sebagai pengajuan approval ke Superadmin dan dicatat ke log aktivitas.
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        <i class="fas fa-info-circle me-1"></i>
                        Perubahan tidak langsung diterapkan. Data akan diproses setelah disetujui Superadmin.
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('pelanggan.show', $kunjungan->pelanggan_id) }}" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-times me-2"></i> Batal
                        </a>
                        <button type="submit" class="btn btn-warning btn-lg">
                            <i class="fas fa-paper-plane me-2"></i> Ajukan Perubahan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
