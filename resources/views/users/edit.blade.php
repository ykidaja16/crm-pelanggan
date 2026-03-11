@extends('layouts.main')

@section('title', 'Edit User - Medical Lab')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0 text-primary"><i class="fas fa-user-edit me-2"></i> Edit Data User</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('users.update', $user->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username', $user->username) }}" required>
                            @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password Baru <small class="text-muted">(Kosongkan jika tidak ingin mengubah)</small></label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role_id" class="form-select @error('role_id') is-invalid @enderror" required id="roleSelect">
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('role_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <!-- Akses Cabang (hanya tampil jika role bukan IT) -->
                    <div class="mb-3" id="cabangSection">
                        <label class="form-label fw-medium">Akses Cabang</label>
                        <div class="text-muted small mb-2">Centang cabang yang dapat diakses oleh user ini.</div>
                        @error('cabangs') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                        <div class="row g-2">
                            @foreach($cabangs as $cabang)
                            <div class="col-md-4">
                                <div class="form-check border rounded p-2">
                                    <input class="form-check-input" type="checkbox"
                                           name="cabangs[]" value="{{ $cabang->id }}"
                                           id="cabang_{{ $cabang->id }}"
                                           {{ in_array($cabang->id, old('cabangs', $userCabangIds)) ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="cabang_{{ $cabang->id }}">
                                        <strong>{{ $cabang->kode }}</strong> - {{ $cabang->nama }}
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-4 form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="isActive" value="1" {{ $user->is_active ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive">Akun Aktif</label>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('users.index') }}" class="btn btn-secondary">Kembali</a>
                        <button type="submit" class="btn btn-success text-white">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Sembunyikan section cabang jika role IT dipilih
const roleSelect = document.getElementById('roleSelect');
const cabangSection = document.getElementById('cabangSection');
const itRoleName = 'IT';

function toggleCabangSection() {
    const selectedText = roleSelect.options[roleSelect.selectedIndex]?.text ?? '';
    if (selectedText === itRoleName) {
        cabangSection.style.display = 'none';
    } else {
        cabangSection.style.display = '';
    }
}

roleSelect.addEventListener('change', toggleCabangSection);
toggleCabangSection(); // run on load
</script>
@endsection
