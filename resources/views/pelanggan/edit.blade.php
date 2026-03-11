@extends('layouts.main')

@section('title', 'Edit Pelanggan - Medical Lab')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">

        {{-- ─── ADMIN: Alur Approval ─────────────────────────────────────────── --}}
        @if($role === 'Admin')
        <div class="alert alert-info border-0 shadow-sm mb-3 d-flex align-items-center gap-2">
            <i class="fas fa-info-circle fa-lg"></i>
            <div>Sebagai <strong>Admin</strong>, perubahan data pelanggan harus melalui <strong>persetujuan Superadmin</strong>. Isi form di bawah dan ajukan perubahan.</div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-warning text-dark py-3">
                <h5 class="mb-0 fw-semibold"><i class="fas fa-paper-plane me-2"></i> Ajukan Perubahan Data Pelanggan</h5>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('approval.pelanggan.edit.store', $pelanggan->id) }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">PID <span class="text-danger">*</span></label>
                            <input type="text" name="pid" class="form-control form-control-lg @error('pid') is-invalid @enderror" value="{{ old('pid', $pelanggan->pid) }}" required>
                            @error('pid')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                            @error('cabang_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control form-control-lg @error('nama') is-invalid @enderror" value="{{ old('nama', $pelanggan->nama) }}" required>
                            @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">No. Telepon</label>
                            <input type="text" name="no_telp" class="form-control form-control-lg @error('no_telp') is-invalid @enderror" value="{{ old('no_telp', $pelanggan->no_telp) }}" placeholder="Contoh: 08123456789">
                            @error('no_telp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Tanggal Lahir</label>
                            <input type="date" name="dob" class="form-control form-control-lg @error('dob') is-invalid @enderror" value="{{ old('dob', $pelanggan->dob?->format('Y-m-d')) }}">
                            @error('dob')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Kota</label>
                            <input type="text" name="kota" class="form-control form-control-lg @error('kota') is-invalid @enderror" value="{{ old('kota', $pelanggan->kota) }}" placeholder="Masukkan kota">
                            @error('kota')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-medium">Alamat</label>
                            <textarea name="alamat" class="form-control @error('alamat') is-invalid @enderror" rows="3" placeholder="Masukkan alamat lengkap">{{ old('alamat', $pelanggan->alamat) }}</textarea>
                            @error('alamat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- Alasan Perubahan --}}
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-comment-dots me-1 text-danger"></i> Alasan Perubahan <span class="text-danger">*</span>
                            </label>
                            <textarea name="request_note" class="form-control @error('request_note') is-invalid @enderror" rows="3"
                                placeholder="Wajib diisi. Contoh: Koreksi nama/nomor telepon yang salah input." required>{{ old('request_note') }}</textarea>
                            @error('request_note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text text-muted">Alasan ini akan dicatat dan dikirim ke Superadmin untuk disetujui.</div>
                        </div>
                    </div>

                    {{-- Pilih Superadmin Tujuan --}}
                    @if(isset($superadmins) && $superadmins->count() > 0)
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-user-shield me-1 text-primary"></i> Tujuan Superadmin <span class="text-danger">*</span>
                            </label>
                            @if($superadmins->count() === 1)
                                <input type="hidden" name="assigned_to" value="{{ $superadmins->first()->id }}">
                                <div class="form-control form-control-lg bg-light text-muted" style="cursor:default;">
                                    <i class="fas fa-user-check me-2 text-success"></i>
                                    {{ $superadmins->first()->name ?? $superadmins->first()->username }}
                                    <span class="badge bg-success ms-2 small">Auto-assign</span>
                                </div>
                                <div class="form-text text-muted">Pengajuan akan otomatis dikirim ke superadmin ini.</div>
                            @else
                                <select name="assigned_to" class="form-select form-select-lg @error('assigned_to') is-invalid @enderror" required>
                                    <option value="">-- Pilih Superadmin --</option>
                                    @foreach($superadmins as $sa)
                                        <option value="{{ $sa->id }}" {{ old('assigned_to') == $sa->id ? 'selected' : '' }}>
                                            {{ $sa->name ?? $sa->username }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('assigned_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text text-muted">Pilih superadmin yang akan menerima pengajuan ini.</div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <hr class="my-4">

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('pelanggan.show', $pelanggan->id) }}" class="btn btn-outline-secondary btn-lg px-4">
                            <i class="fas fa-arrow-left me-2"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-warning btn-lg px-5 text-dark">
                            <i class="fas fa-paper-plane me-2"></i> Ajukan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ─── SUPER ADMIN: Direct Update ──────────────────────────────────── --}}
        @else
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
                            @error('pid')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                            @error('cabang_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control form-control-lg @error('nama') is-invalid @enderror" value="{{ old('nama', $pelanggan->nama) }}" required>
                            @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">No. Telepon</label>
                            <input type="text" name="no_telp" class="form-control form-control-lg @error('no_telp') is-invalid @enderror" value="{{ old('no_telp', $pelanggan->no_telp) }}" placeholder="Contoh: 08123456789">
                            @error('no_telp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Tanggal Lahir</label>
                            <input type="date" name="dob" class="form-control form-control-lg @error('dob') is-invalid @enderror" value="{{ old('dob', $pelanggan->dob?->format('Y-m-d')) }}">
                            @error('dob')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium">Kota</label>
                            <input type="text" name="kota" class="form-control form-control-lg @error('kota') is-invalid @enderror" value="{{ old('kota', $pelanggan->kota) }}" placeholder="Masukkan kota">
                            @error('kota')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-medium">Alamat</label>
                            <textarea name="alamat" class="form-control @error('alamat') is-invalid @enderror" rows="3" placeholder="Masukkan alamat lengkap">{{ old('alamat', $pelanggan->alamat) }}</textarea>
                            @error('alamat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('pelanggan.show', $pelanggan->id) }}" class="btn btn-outline-secondary btn-lg px-4">
                            <i class="fas fa-arrow-left me-2"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-warning btn-lg px-5 text-dark">
                            <i class="fas fa-save me-2"></i> Update Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
