@extends('layouts.main')

@section('title', 'Update MOU / Agreement - Medical Lab CRM')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="text-primary mb-1 fw-semibold">
                <i class="fas fa-file-contract me-2"></i>Update MOU / Agreement Data Existing
            </h4>
            <p class="text-muted small mb-0">Perbarui kolom MOU / Agreement pada data kunjungan existing menggunakan file Excel.</p>
        </div>
        <a href="{{ route('kunjungan.update-mou.template') }}" class="btn btn-outline-success">
            <i class="fas fa-file-download me-2"></i>Download Template Excel
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle fa-2x me-3 text-success"></i>
                <div>
                    <h6 class="mb-1 fw-semibold">Berhasil!</h6>
                    <p class="mb-0">{{ session('success') }}</p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle fa-2x me-3 text-danger"></i>
                <div>
                    <h6 class="mb-1 fw-semibold">Terjadi Kesalahan!</h6>
                    <p class="mb-0">{{ session('error') }}</p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('import_details') && count(session('import_details')) > 0)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-warning bg-opacity-10 text-dark py-2">
                <i class="fas fa-info-circle me-2 text-warning"></i><strong>Detail Data yang Tidak Ditemukan / Dilewati:</strong>
            </div>
            <div class="card-body p-3">
                <ul class="mb-0 small text-muted" style="max-height: 200px; overflow-y: auto;">
                    @foreach(session('import_details') as $detail)
                        <li>{{ $detail }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <!-- Form Upload -->
        <div class="col-lg-7 col-md-12">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-semibold text-primary">
                        <i class="fas fa-file-upload me-2"></i>Upload File Excel
                    </h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('kunjungan.update-mou.import') }}" method="POST" enctype="multipart/form-data" id="updateForm">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label fw-medium">Pilih Berkas Excel / CSV <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control form-control-lg @error('file') is-invalid @enderror" 
                                   accept=".xlsx,.xls,.csv" required id="fileInput">
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text mt-2">
                                Format yang didukung: <strong>.xlsx, .xls, .csv</strong>. Pastikan file mengikuti format 3 kolom.
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Kembali
                            </a>
                            <button type="submit" class="btn btn-primary px-4 py-2" id="submitBtn">
                                <i class="fas fa-sync-alt me-2"></i>Mulai Update Data
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Panduan Format File -->
        <div class="col-lg-5 col-md-12">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-semibold text-dark">
                        <i class="fas fa-info-circle me-2 text-info"></i>Panduan Format File
                    </h6>
                </div>
                <div class="card-body p-4">
                    <p class="small text-muted mb-3">
                        File Excel harus memiliki <strong>3 kolom</strong> persis dengan urutan berikut:
                    </p>

                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-sm small mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kolom 1</th>
                                    <th>Kolom 2</th>
                                    <th>Kolom 3</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="fw-semibold">
                                    <td>PID</td>
                                    <td>Tanggal Kunjungan</td>
                                    <td>MOU/Agreement</td>
                                </tr>
                                <tr class="text-muted">
                                    <td>LX001</td>
                                    <td>2021-04-09</td>
                                    <td>Promo Kemerdekaan</td>
                                </tr>
                                <tr class="text-muted">
                                    <td>BD00002</td>
                                    <td>15/06/2022</td>
                                    <td>MOU Perusahaan ABC</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-info py-2 px-3 small mb-0">
                        <ul class="mb-0 ps-3">
                            <li><strong>PID</strong> dan <strong>Tanggal Kunjungan</strong> digunakan untuk mencocokkan riwayat kunjungan.</li>
                            <li>Format tanggal yang didukung: <code>YYYY-MM-DD</code>, <code>DD-MM-YYYY</code>, atau <code>DD/MM/YYYY</code>.</li>
                            <li>Jika kolom MOU/Agreement dikosongkan pada baris tertentu, data MOU pada kunjungan tersebut akan dikosongkan (set null).</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
