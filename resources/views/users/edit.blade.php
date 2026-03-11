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
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <label class="form-label fw-semibold mb-0">
                                    <i class="fas fa-building text-primary me-1"></i>Akses Cabang
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
                        <div class="border rounded-3 p-3 bg-light">
                            <div class="row g-2">
                                @foreach($cabangs as $cabang)
                                @php $isChecked = in_array($cabang->id, old('cabangs', $userCabangIds)); @endphp
                                <div class="col-md-4 col-sm-6">
                                    <label for="cabang_{{ $cabang->id }}"
                                           class="cabang-card d-flex align-items-center gap-2 p-2 rounded-2 border cursor-pointer w-100 {{ $isChecked ? 'cabang-checked' : '' }}"
                                           style="cursor:pointer; transition: all 0.2s; background: {{ $isChecked ? '#e8f0fe' : '#fff' }}; border-color: {{ $isChecked ? '#0056b3' : '#dee2e6' }} !important;">
                                        <input class="cabang-checkbox" type="checkbox"
                                               name="cabangs[]" value="{{ $cabang->id }}"
                                               id="cabang_{{ $cabang->id }}"
                                               {{ $isChecked ? 'checked' : '' }}
                                               style="display:none;">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                             style="width:32px;height:32px;background:{{ $isChecked ? '#0056b3' : '#e9ecef' }}; transition: background 0.2s;">
                                            <i class="fas {{ $isChecked ? 'fa-check' : 'fa-building' }} text-{{ $isChecked ? 'white' : 'secondary' }}"
                                               style="font-size:0.75rem;"></i>
                                        </div>
                                        <div class="flex-grow-1 overflow-hidden">
                                            <div class="fw-semibold text-dark" style="font-size:0.8rem; line-height:1.2;">{{ $cabang->kode }}</div>
                                            <div class="text-muted text-truncate" style="font-size:0.72rem;">{{ $cabang->nama }}</div>
                                        </div>
                                    </label>
                                </div>
                                @endforeach
                            </div>
                            <div class="mt-2 pt-2 border-top">
                                <small class="text-muted" id="cabangCountText">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <span id="cabangSelectedCount">{{ count(old('cabangs', $userCabangIds)) }}</span> dari {{ count($cabangs) }} cabang dipilih
                                </small>
                            </div>
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
// ── Toggle section cabang jika role IT ──────────────────────────────────────
const roleSelect    = document.getElementById('roleSelect');
const cabangSection = document.getElementById('cabangSection');

function toggleCabangSection() {
    const selectedText = roleSelect.options[roleSelect.selectedIndex]?.text ?? '';
    cabangSection.style.display = (selectedText === 'IT') ? 'none' : '';
}
roleSelect.addEventListener('change', toggleCabangSection);
toggleCabangSection();

// ── Cabang Card Toggle ───────────────────────────────────────────────────────
function updateCabangCard(label) {
    const checkbox  = label.querySelector('.cabang-checkbox');
    const circle    = label.querySelector('.rounded-circle');
    const icon      = circle.querySelector('i');
    const isChecked = checkbox.checked;

    label.style.background   = isChecked ? '#e8f0fe' : '#fff';
    label.style.borderColor  = isChecked ? '#0056b3' : '#dee2e6';
    circle.style.background  = isChecked ? '#0056b3' : '#e9ecef';
    icon.className = 'fas ' + (isChecked ? 'fa-check text-white' : 'fa-building text-secondary');
    icon.style.fontSize = '0.75rem';

    updateCabangCount();
}

function updateCabangCount() {
    const total    = document.querySelectorAll('.cabang-checkbox').length;
    const selected = document.querySelectorAll('.cabang-checkbox:checked').length;
    const el = document.getElementById('cabangSelectedCount');
    if (el) el.textContent = selected;
}

function selectAllCabangs(state) {
    document.querySelectorAll('.cabang-checkbox').forEach(cb => {
        cb.checked = state;
        updateCabangCard(cb.closest('label'));
    });
}

document.querySelectorAll('.cabang-card').forEach(label => {
    label.addEventListener('click', function(e) {
        if (e.target.tagName === 'INPUT') return; // handled by browser
        const cb = this.querySelector('.cabang-checkbox');
        cb.checked = !cb.checked;
        updateCabangCard(this);
    });
    // Sync visual state on page load
    updateCabangCard(label);
});
</script>
@endsection
