@extends('layouts.main')

@section('title', 'Edit Pelanggan - Medical Lab')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-warning text-dark py-3">
                <h5 class="mb-0 fw-semibold"><i class="fas fa-user-edit me-2"></i> Edit Data Pelanggan</h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('pelanggan.update', $pelanggan->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">PID <span class="text-danger">*</span></label>
                            <input type="text" name="pid" class="form-control form-control-lg @error('pid') is-invalid @enderror" value="{{ old('pid', $pelanggan->pid) }}" required>
                            @error('pid')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Cabang <span class="text-danger">*</span></label>
                            <select name="cabang_id" class="form-select form-select-lg @error('cabang_id') is-invalid @enderror" required>
                                <option value="">Pilih Cabang</option>
                                @foreach($cabangs as $cabang)
                                    <option value="{{ $cabang->id }}" {{ old('cabang_id', $pelanggan->cabang_id) == $cabang->id ? 'selected' : '' }}>
                                        {{ $cabang->nama }} ({{ $cabang->kode }})
                                    </option>
                                @endforeach
                            </select>
                            @error('cabang_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control form-control-lg @error('nama') is-invalid @enderror" value="{{ old('nama', $pelanggan->nama) }}" required>
                            @error('nama')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">No. Telepon</label>
                            <input type="text" name="no_telp" class="form-control form-control-lg @error('no_telp') is-invalid @enderror" value="{{ old('no_telp', $pelanggan->no_telp) }}" placeholder="Contoh: 08123456789">
                            @error('no_telp')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Tanggal Lahir</label>
                            <input type="date" name="dob" class="form-control form-control-lg @error('dob') is-invalid @enderror" value="{{ old('dob', $pelanggan->dob?->format('Y-m-d')) }}">
                            @error('dob')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Kota</label>
                            <input type="text" name="kota" class="form-control form-control-lg @error('kota') is-invalid @enderror" value="{{ old('kota', $pelanggan->kota) }}" placeholder="Masukkan kota">
                            @error('kota')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-medium">Alamat</label>
                            <textarea name="alamat" class="form-control @error('alamat') is-invalid @enderror" rows="3" placeholder="Masukkan alamat lengkap">{{ old('alamat', $pelanggan->alamat) }}</textarea>
                            @error('alamat')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('pelanggan.index') }}" class="btn btn-outline-secondary btn-lg px-4">
                            <i class="fas fa-arrow-left me-2"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-warning btn-lg px-5 text-dark">
                            <i class="fas fa-save me-2"></i> Update Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
