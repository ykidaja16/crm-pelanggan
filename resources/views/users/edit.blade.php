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

                    <!-- AksesCabang -->
                    <div class="mb-4" id="cabangSection">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <label class="form-label fw-semibold mb-0">
                                    <i class="fas fa-building text-primary me-1"></i> Akses Cabang
                                </label>
                                <div class="text-muted small">Pilih cabang yang dapat diakses oleh user ini.</div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectAllCabangs(true)">
                                    <i class="fas fa-check-double me-1"></i>Pilih Semua
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="selectAllCabangs(false)">
                                    <i class="fas fa-times me-1"></i>Hapus Semua
                                </button>
                            </div>
                        </div>
                        @error('cabangs') 
                            <div class="alert alert-danger py-2 small mb-2">
                                <i class="fas fa-exclamation-circle me-1"></i>{{ $message }}
                            </div>
                        @enderror
                        <div class="border rounded p-2">
                            <div class="row g-1">
                                @foreach($cabangs as $cabang)
                                @php $isChecked = in_array($cabang->id, old('cabangs', $userCabangIds)); @endphp
                                <div class="col-md-6 col-12">
                                    <label for="cabang_{{ $cabang->id }}" 
                                           class="cabang-item d-flex align-items-center p-2 rounded border {{ $isChecked ? 'border-primary bg-light' : 'border-secondary-subtle' }} cursor-pointer"
                                           style="transition: all 0.15s ease;">
                                        <input class="form-check-input mt-0 cabang-checkbox me-2" type="checkbox"
                                               name="cabangs[]" value="{{ $cabang->id }}"
                                               id="cabang_{{ $cabang->id }}"
                                               {{ $isChecked ? 'checked' : '' }}>
                                        <span class="fw-semibold">{{ $cabang->kode }}</span>
                                        <span class="text-muted ms-1 small">{{ $cabang->nama }}</span>
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <small class="text-muted mt-1 d-block">
                            <i class="fas fa-info-circle me-1"></i>
                            <span id="cabangSelectedCount">{{ count(old('cabangs', $userCabangIds)) }}</span> dari {{ count($cabangs) }} cabang dipilih
                        </small>
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
const roleSelect    = document.getElementById('roleSelect');
const cabangSection = document.getElementById('cabangSection');

function toggleCabangSection() {
    const selectedText = roleSelect.options[roleSelect.selectedIndex]?.text ?? '';
    cabangSection.style.display = (selectedText === 'IT') ? 'none' : '';
}
roleSelect.addEventListener('change', toggleCabangSection);
toggleCabangSection();

function updateCabangCount() {
    const selected = document.querySelectorAll('.cabang-checkbox:checked').length;
    const el = document.getElementById('cabangSelectedCount');
    if (el) el.textContent = selected;
}

function updateCabangStyle(checkbox) {
    const label = checkbox.closest('.cabang-item');
    if (checkbox.checked) {
        label.classList.add('border-primary', 'bg-light');
        label.classList.remove('border-secondary-subtle');
    } else {
        label.classList.remove('border-primary', 'bg-light');
        label.classList.add('border-secondary-subtle');
    }
}

function selectAllCabangs(state) {
    document.querySelectorAll('.cabang-checkbox').forEach(cb => {
        cb.checked = state;
        updateCabangStyle(cb);
    });
    updateCabangCount();
}

document.querySelectorAll('.cabang-checkbox').forEach(cb => {
    cb.addEventListener('change', function() {
        updateCabangStyle(this);
        updateCabangCount();
    });
    updateCabangStyle(cb);
});
</script>
@endsection

