@extends('layouts.main')

@section('title', 'Update NIK')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-id-card me-2"></i>Update NIK</span>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Upload file Excel/CSV dengan 2 kolom: <strong>PID</strong> dan <strong>NIK</strong>.
            Jika ada 1 baris gagal (misalnya PID tidak ditemukan), maka seluruh file akan ditolak.
        </div>

        <form action="{{ route('pelanggan.update-nik.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="file" class="form-label fw-semibold">File Excel/CSV</label>
                <input type="file" name="file" id="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx,.xls,.csv,.txt" required>
                @error('file')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text">Format yang didukung: xlsx, xls, csv, txt.</div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-upload me-1"></i>Import Update NIK
                </button>
                <a href="{{ route('pelanggan.update-nik.download-template') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-download me-1"></i>Download Template
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
