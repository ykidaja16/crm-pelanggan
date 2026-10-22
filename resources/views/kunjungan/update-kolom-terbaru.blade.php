@extends('layouts.main')

@section('title', 'Update Kolom Terbaru - Medical Lab CRM')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="text-primary mb-1 fw-semibold">
                <i class="fas fa-columns me-2"></i>Update Kolom Terbaru Data Existing
            </h4>
            <p class="text-muted small mb-0">Perbarui rincian kolom data kunjungan dan profil pelanggan lama/existing menggunakan berkas Excel.</p>
        </div>
        <a href="{{ route('kunjungan.update-kolom-terbaru.template') }}" class="btn btn-outline-success">
            <i class="fas fa-file-download me-2"></i>Download Template Excel (18 Kolom)
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
        <div class="col-lg-5 col-md-12">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-semibold text-primary">
                        <i class="fas fa-file-upload me-2"></i>Upload File Excel
                    </h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('kunjungan.update-kolom-terbaru.import') }}" method="POST" enctype="multipart/form-data" id="updateForm">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label fw-medium">Pilih Berkas Excel / CSV <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control form-control-lg @error('file') is-invalid @enderror" 
                                   accept=".xlsx,.xls,.csv" required id="fileInput">
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text mt-2">
                                Format yang didukung: <strong>.xlsx, .xls, .csv</strong>. Pastikan susunan kolom mengikuti format template 18 kolom.
                            </div>
                        </div>

                        <div class="alert alert-light border small mb-4">
                            <h6 class="fw-semibold text-primary mb-2"><i class="fas fa-shield-alt me-1"></i>Ketentuan Update:</h6>
                            <ul class="mb-0 ps-3">
                                <li>Pencocokan data dilakukan berdasarkan <strong>PID</strong> dan <strong>TANGGAL KUNJUNGAN</strong>.</li>
                                <li>Kolom yang dibiarkan kosong pada file Excel <strong>tidak akan</strong> menimpa data lama (data lama tetap aman).</li>
                                <li>Master <strong>Dokter</strong> dan <strong>Marketing</strong> baru akan terdaftar otomatis secara rapi jika belum ada.</li>
                            </ul>
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

        <!-- Panduan Format File (18 Kolom) -->
        <div class="col-lg-7 col-md-12">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold text-info">
                        <i class="fas fa-table me-2"></i>Panduan Susunan 18 Kolom Template
                    </h6>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary small">18 Kolom</span>
                </div>
                <div class="card-body p-4">
                    <p class="small text-muted mb-3">
                        File Excel harus memiliki <strong>18 kolom</strong> dengan urutan judul header berikut:
                    </p>

                    <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                        <table class="table table-bordered table-sm small align-middle mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="text-center" style="width: 45px;">No</th>
                                    <th>Nama Kolom Header</th>
                                    <th>Target Data</th>
                                    <th>Contoh Nilai</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="table-primary table-opacity-10">
                                    <td class="fw-bold text-center">1</td>
                                    <td><code>PID</code></td>
                                    <td>Kunci Pasien (Pelanggan)</td>
                                    <td>LX001</td>
                                </tr>
                                <tr class="table-primary table-opacity-10">
                                    <td class="fw-bold text-center">2</td>
                                    <td><code>TANGGAL KUNJUNGAN</code></td>
                                    <td>Kunci Kunjungan</td>
                                    <td>2021-04-09</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">3</td>
                                    <td><code>JK</code></td>
                                    <td>Jenis Kelamin Pelanggan</td>
                                    <td>L / P</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">4</td>
                                    <td><code>Kecamatan</code></td>
                                    <td>Kecamatan Pelanggan</td>
                                    <td>Coblong</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">5</td>
                                    <td><code>Tipe Omset</code></td>
                                    <td>Tipe Omset Kunjungan</td>
                                    <td>Klinis / Rujukan / MCU</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">6</td>
                                    <td><code>Kode Kel.Pelanggan</code></td>
                                    <td>Kode Kelompok Pelanggan</td>
                                    <td>MND / KLN</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">7</td>
                                    <td><code>KODE DOKTER</code></td>
                                    <td>Kode Dokter Perujuk</td>
                                    <td>DR01</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">8</td>
                                    <td><code>DOKTER</code></td>
                                    <td>Nama Dokter Perujuk</td>
                                    <td>dr. Budi Santoso, Sp.PK</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">9</td>
                                    <td><code>MOU</code></td>
                                    <td>Nomor MOU / Agreement</td>
                                    <td>MOU-2021-01</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">10</td>
                                    <td><code>MARKETING</code></td>
                                    <td>Nama Petugas Marketing</td>
                                    <td>Ahmad Marketing</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">11</td>
                                    <td><code>BRUTO</code></td>
                                    <td>Nominal Bruto</td>
                                    <td>500000</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">12</td>
                                    <td><code>DISC</code></td>
                                    <td>Nominal Diskon</td>
                                    <td>50000</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">13</td>
                                    <td><code>PAY</code></td>
                                    <td>Nominal Pembayaran (Net)</td>
                                    <td>450000</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">14</td>
                                    <td><code>JML TEST</code></td>
                                    <td>Jumlah Test / Item Pemeriksaan</td>
                                    <td>3</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">15</td>
                                    <td><code>PEMERIKSAAN</code></td>
                                    <td>Rincian Nama Pemeriksaan</td>
                                    <td>Diabetes, Kolestrol, Urine Lengkap</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">16</td>
                                    <td><code>NIP</code></td>
                                    <td>Nomor Induk Pegawai Pelanggan</td>
                                    <td>198501012010011001</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">17</td>
                                    <td><code>JABATAN</code></td>
                                    <td>Jabatan Pelanggan</td>
                                    <td>Manager IT</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-center">18</td>
                                    <td><code>LOCATION</code></td>
                                    <td>Lokasi Kerja / Satker</td>
                                    <td>Gedung A Lantai 2</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.getElementById('updateForm')?.addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Memproses...';
        }
    });
</script>
@endsection
