@extends('layouts.main')

@section('title', 'Tambah Pelanggan - Medical Lab')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="mb-0 fw-semibold"><i class="fas fa-user-plus me-2"></i> Tambah Pelanggan</h5>
            </div>
            <div class="card-body p-4">

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form action="{{ route('pelanggan.store') }}" method="POST" id="pelangganForm">
                    @csrf
                    <input type="hidden" name="inputs[0][mode]" id="mode" value="new">
                    
                    @php
                        $oldInputs = session('inputs', []);
                        $errors = session('errors', []);
                        $firstError = !empty($errors) ? reset($errors) : null;
                    @endphp

                    @if($firstError)
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($firstError as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Mode Selector -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold text-secondary mb-2">Mode Input</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="mode_selector" id="mode_new" value="new" checked onchange="toggleMode('new')">
                            <label class="btn btn-outline-primary py-2" for="mode_new">
                                <i class="fas fa-user-plus me-2"></i>Pelanggan Baru
                            </label>
                            
                            <input type="radio" class="btn-check" name="mode_selector" id="mode_existing" value="existing" onchange="toggleMode('existing')">
                            <label class="btn btn-outline-success py-2" for="mode_existing">
                                <i class="fas fa-user-check me-2"></i>Pelanggan Lama
                            </label>
                        </div>
                        <div class="form-text mt-2">
                            <i class="fas fa-info-circle me-1"></i>
                            Pilih mode sesuai kebutuhan: daftarkan pelanggan baru atau tambah kunjungan untuk pelanggan yang sudah terdaftar.
                        </div>
                    </div>


                    <!-- Data Pelanggan - NEW MODE -->
                    <div id="new_customer_section" class="mb-4">
                        <h6 class="fw-semibold text-primary mb-3 pb-2 border-bottom">
                            <i class="fas fa-user me-2"></i>Data Pelanggan Baru
                        </h6>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">PID <span class="text-danger">*</span></label>
                                <input type="text" name="inputs[0][pid]" id="pid_new"
                                    class="form-control form-control-lg mode-new {{ isset($errors[0]) && collect($errors[0])->contains(fn($e) => str_contains($e, 'PID') || str_contains($e, 'pid')) ? 'is-invalid' : '' }}" 
                                    value="{{ old('inputs.0.pid', $oldInputs[0]['pid'] ?? '') }}" 
                                    placeholder="Contoh: LXB00000001" required>
                                <div class="form-text">Masukkan PID dari data yang dimiliki</div>
                                @if(isset($errors[0]) && collect($errors[0])->contains(fn($e) => str_contains($e, 'PID') || str_contains($e, 'pid')))
                                    <div class="invalid-feedback d-block">
                                        {{ collect($errors[0])->first(fn($e) => str_contains($e, 'PID') || str_contains($e, 'pid')) }}
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-medium">Cabang <span class="text-danger">*</span></label>
                                <select name="inputs[0][cabang_id]" id="cabang_id_new" class="form-select form-select-lg mode-new {{ isset($errors[0]) && collect($errors[0])->contains(fn($e) => str_contains($e, 'Cabang')) ? 'is-invalid' : '' }}" required>
                                    <option value="">Pilih Cabang</option>
                                    @foreach($cabangs as $cabang)
                                        <option value="{{ $cabang->id }}" data-kode="{{ $cabang->kode }}" {{ old('inputs.0.cabang_id', $oldInputs[0]['cabang_id'] ?? '') == $cabang->id ? 'selected' : '' }}>
                                            {{ $cabang->nama }} ({{ $cabang->kode }})
                                        </option>
                                    @endforeach
                                </select>
                                @if(isset($errors[0]) && collect($errors[0])->contains(fn($e) => str_contains($e, 'Cabang')))
                                    <div class="invalid-feedback d-block">
                                        {{ collect($errors[0])->first(fn($e) => str_contains($e, 'Cabang')) }}
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-medium">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" name="inputs[0][nama]" id="nama_new"
                                    class="form-control form-control-lg mode-new {{ isset($errors[0]) && collect($errors[0])->contains(fn($e) => str_contains($e, 'Nama') || str_contains($e, 'nama')) ? 'is-invalid' : '' }}" 
                                    value="{{ old('inputs.0.nama', $oldInputs[0]['nama'] ?? '') }}" 
                                    placeholder="Masukkan nama lengkap" required>
                                @if(isset($errors[0]) && collect($errors[0])->contains(fn($e) => str_contains($e, 'Nama') || str_contains($e, 'nama')))
                                    <div class="invalid-feedback d-block">
                                        {{ collect($errors[0])->first(fn($e) => str_contains($e, 'Nama') || str_contains($e, 'nama')) }}
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-medium">No. Telepon</label>
                                <input type="text" name="inputs[0][no_telp]" id="no_telp_new"
                                    class="form-control form-control-lg mode-new" 
                                    value="{{ old('inputs.0.no_telp', $oldInputs[0]['no_telp'] ?? '') }}"
                                    placeholder="Contoh: 08123456789">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-medium">Tanggal Lahir</label>
                                <input type="date" name="inputs[0][dob]" id="dob_new"
                                    class="form-control form-control-lg mode-new" 
                                    value="{{ old('inputs.0.dob', $oldInputs[0]['dob'] ?? '') }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-medium">Kota</label>
                                <input type="text" name="inputs[0][kota]" id="kota_new"
                                    class="form-control form-control-lg mode-new" 
                                    value="{{ old('inputs.0.kota', $oldInputs[0]['kota'] ?? '') }}"
                                    placeholder="Masukkan kota">
                            </div>


                            <div class="col-12">
                                <label class="form-label fw-medium">Alamat</label>
                                <textarea name="inputs[0][alamat]" id="alamat_new"
                                    class="form-control mode-new" rows="3"
                                    placeholder="Masukkan alamat lengkap">{{ old('inputs.0.alamat', $oldInputs[0]['alamat'] ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>


                    <!-- Data Pelanggan - EXISTING MODE -->
                    <div id="existing_customer_section" class="mb-4" style="display: none;">
                        <h6 class="fw-semibold text-success mb-3 pb-2 border-bottom">
                            <i class="fas fa-search me-2"></i>Cari Pelanggan Lama
                        </h6>
                        
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-medium">Masukkan PID <span class="text-danger">*</span></label>
                                <div class="input-group input-group-lg">
                                    <input type="text" id="search_pid" 
                                        class="form-control mode-existing" 
                                        placeholder="Contoh: LXB00000001">
                                    <button type="button" class="btn btn-info px-4" onclick="searchPelanggan()">
                                        <i class="fas fa-search me-2"></i>Cari
                                    </button>
                                </div>
                                <div class="form-text">Masukkan PID pelanggan yang sudah terdaftar</div>
                            </div>
                        </div>

                        <!-- Hasil Pencarian -->
                        <div id="search_result" class="mt-3" style="display: none;">
                            <div class="alert alert-info border-0 shadow-sm">
                                <h6 class="fw-semibold mb-3"><i class="fas fa-user-check me-2"></i>Pelanggan Ditemukan</h6>
                                <div class="row g-2">
                                    <div class="col-md-6"><strong>PID:</strong> <span id="found_pid"></span></div>
                                    <div class="col-md-6"><strong>Nama:</strong> <span id="found_nama"></span></div>
                                    <div class="col-md-6"><strong>Cabang:</strong> <span id="found_cabang"></span></div>
                                    <div class="col-md-6"><strong>Klasifikasi:</strong> <span id="found_class"></span></div>
                                </div>
                            </div>
                            <input type="hidden" name="inputs[0][existing_pelanggan_id]" id="existing_pelanggan_id">
                            <input type="hidden" name="inputs[0][existing_cabang_id]" id="existing_cabang_id">
                        </div>

                        <div id="search_not_found" class="alert alert-danger mt-3" style="display: none;">
                            <i class="fas fa-exclamation-circle me-2"></i>Pelanggan dengan PID tersebut tidak ditemukan.
                        </div>

                        {{-- Warning: pelanggan khusus tidak boleh diinput di sini --}}
                        <div id="search_khusus_warning" class="alert alert-warning mt-3" style="display: none;">
                            <div class="d-flex align-items-start gap-2">
                                <i class="fas fa-exclamation-triangle mt-1 shrink-0"></i>
                                <div>
                                    <strong>Pelanggan Khusus Terdeteksi!</strong><br>
                                    PID ini terdaftar sebagai <strong>Pelanggan Khusus</strong>.
                                    Menu <em>Tambah Pelanggan</em> ini hanya untuk <strong>Pelanggan Biasa</strong>.<br>
                                    Silakan gunakan menu <a href="{{ route('pelanggan.khusus.index') }}" class="alert-link fw-semibold">Pelanggan Khusus</a> untuk menambah kunjungan pelanggan ini.
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Data Kunjungan -->
                    <div class="mb-4">
                        <h6 class="fw-semibold text-info mb-3 pb-2 border-bottom">
                            <i class="fas fa-calendar-check me-2"></i>Data Kunjungan
                        </h6>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Kelompok Pelanggan <span class="text-danger">*</span></label>
                                <select name="inputs[0][kelompok_pelanggan]" id="kelompok_pelanggan_kunjungan"
                                    class="form-select form-select-lg {{ isset($errors[0]) && collect($errors[0])->contains(fn($e) => str_contains(strtolower($e), 'kelompok')) ? 'is-invalid' : '' }}" required>
                                    <option value="mandiri" {{ old('inputs.0.kelompok_pelanggan', $oldInputs[0]['kelompok_pelanggan'] ?? 'mandiri') == 'mandiri' ? 'selected' : '' }}>Mandiri</option>
                                    <option value="klinisi" {{ old('inputs.0.kelompok_pelanggan', $oldInputs[0]['kelompok_pelanggan'] ?? '') == 'klinisi' ? 'selected' : '' }}>Klinisi</option>
                                </select>
                                @if(isset($errors[0]) && collect($errors[0])->contains(fn($e) => str_contains(strtolower($e), 'kelompok')))
                                    <div class="invalid-feedback d-block">
                                        {{ collect($errors[0])->first(fn($e) => str_contains(strtolower($e), 'kelompok')) }}
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-medium">Tanggal Kunjungan <span class="text-danger">*</span></label>
                                <input type="date" name="inputs[0][tanggal_kunjungan]" id="tanggal_kunjungan"
                                    class="form-control form-control-lg {{ isset($errors[0]) && collect($errors[0])->contains(fn($e) => str_contains($e, 'Tanggal Kunjungan') || str_contains($e, 'tanggal_kunjungan')) ? 'is-invalid' : '' }}" 
                                    value="{{ old('inputs.0.tanggal_kunjungan', $oldInputs[0]['tanggal_kunjungan'] ?? date('Y-m-d')) }}" 
                                    required>
                                @if(isset($errors[0]) && collect($errors[0])->contains(fn($e) => str_contains($e, 'Tanggal Kunjungan') || str_contains($e, 'tanggal_kunjungan')))
                                    <div class="invalid-feedback d-block">
                                        {{ collect($errors[0])->first(fn($e) => str_contains($e, 'Tanggal Kunjungan') || str_contains($e, 'tanggal_kunjungan')) }}
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-medium">Biaya <span class="text-danger">*</span></label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text fw-semibold">Rp</span>
                                    <input type="text" name="inputs[0][biaya]" id="biaya"
                                        class="form-control biaya-input {{ isset($errors[0]) && collect($errors[0])->contains(fn($e) => str_contains($e, 'Biaya') || str_contains($e, 'biaya')) ? 'is-invalid' : '' }}" 
                                        value="{{ old('inputs.0.biaya', $oldInputs[0]['biaya'] ?? '') }}" 
                                        placeholder="0" required>
                                </div>
                                @if(isset($errors[0]) && collect($errors[0])->contains(fn($e) => str_contains($e, 'Biaya') || str_contains($e, 'biaya')))
                                    <div class="invalid-feedback d-block">
                                        {{ collect($errors[0])->first(fn($e) => str_contains($e, 'Biaya') || str_contains($e, 'biaya')) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('pelanggan.index') }}" class="btn btn-outline-secondary btn-lg px-4">
                            <i class="fas fa-arrow-left me-2"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg px-5">
                            <i class="fas fa-save me-2"></i> Simpan Data
                        </button>
                    </div>

                </form>

                <script>
                    // Format biaya input dengan thousand separator
                    function formatBiaya(input) {
                        let value = input.value.replace(/\D/g, ''); // Remove non-digits
                        value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.'); // Add dots as thousand separators
                        input.value = value;
                    }

                    // Event listener untuk format biaya
                    document.querySelectorAll('.biaya-input').forEach(input => {
                        input.addEventListener('input', function() {
                            formatBiaya(this);
                        });
                    });

                    // Toggle mode (new vs existing)
                    function toggleMode(mode) {
                        document.getElementById('mode').value = mode;
                        
                        if (mode === 'new') {
                            document.getElementById('new_customer_section').style.display = 'block';
                            document.getElementById('existing_customer_section').style.display = 'none';
                            
                            // Enable new customer fields
                            document.querySelectorAll('.mode-new').forEach(el => {
                                el.disabled = false;
                                el.required = el.id !== 'no_telp_new' && el.id !== 'dob_new' && el.id !== 'kota_new' && el.id !== 'alamat_new';
                            });
                            
                            // Disable existing customer fields
                            document.querySelectorAll('.mode-existing').forEach(el => {
                                el.disabled = true;
                                el.required = false;
                            });
                            
                            // Reset existing customer data
                            document.getElementById('search_result').style.display = 'none';
                            document.getElementById('search_not_found').style.display = 'none';
                            document.getElementById('search_khusus_warning').style.display = 'none';
                            document.getElementById('existing_pelanggan_id').value = '';
                            document.getElementById('existing_cabang_id').value = '';
                            
                        } else {
                            document.getElementById('new_customer_section').style.display = 'none';
                            document.getElementById('existing_customer_section').style.display = 'block';
                            
                            // Disable new customer fields
                            document.querySelectorAll('.mode-new').forEach(el => {
                                el.disabled = true;
                                el.required = false;
                            });
                            
                            // Enable existing customer fields
                            document.querySelectorAll('.mode-existing').forEach(el => {
                                el.disabled = false;
                                el.required = el.id === 'search_pid';
                            });
                        }
                    }

                    // Search pelanggan by PID
                    function searchPelanggan() {
                        const pid = document.getElementById('search_pid').value.trim();
                        
                        if (!pid) {
                            alert('Masukkan PID terlebih dahulu');
                            return;
                        }
                        
                        // AJAX request to search pelanggan
                        fetch(`/api/pelanggan/search?pid=${encodeURIComponent(pid)}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.found) {
                                    // ── Cek apakah pelanggan adalah Pelanggan Khusus ──
                                    if (data.pelanggan.is_pelanggan_khusus) {
                                        // Tampilkan warning, sembunyikan hasil normal
                                        document.getElementById('search_result').style.display = 'none';
                                        document.getElementById('search_not_found').style.display = 'none';
                                        document.getElementById('search_khusus_warning').style.display = 'block';
                                        // Kosongkan hidden fields agar form tidak bisa disubmit
                                        document.getElementById('existing_pelanggan_id').value = '';
                                        document.getElementById('existing_cabang_id').value = '';
                                    } else {
                                        // Pelanggan biasa: tampilkan info normal
                                        document.getElementById('search_khusus_warning').style.display = 'none';
                                        document.getElementById('search_not_found').style.display = 'none';
                                        document.getElementById('found_pid').textContent = data.pelanggan.pid;
                                        document.getElementById('found_nama').textContent = data.pelanggan.nama;
                                        document.getElementById('found_cabang').textContent = data.cabang;
                                        document.getElementById('found_class').textContent = data.pelanggan.class;
                                        document.getElementById('existing_pelanggan_id').value = data.pelanggan.id;
                                        document.getElementById('existing_cabang_id').value = data.pelanggan.cabang_id;
                                        document.getElementById('search_result').style.display = 'block';
                                    }
                                } else {
                                    document.getElementById('search_result').style.display = 'none';
                                    document.getElementById('search_khusus_warning').style.display = 'none';
                                    document.getElementById('search_not_found').style.display = 'block';
                                    document.getElementById('existing_pelanggan_id').value = '';
                                    document.getElementById('existing_cabang_id').value = '';
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('Terjadi kesalahan saat mencari pelanggan');
                            });
                    }

                    // Form validation
                    document.getElementById('pelangganForm').addEventListener('submit', function(e) {
                        const mode = document.getElementById('mode').value;
                        const biaya = document.getElementById('biaya').value;
                        const tanggalKunjungan = document.getElementById('tanggal_kunjungan').value;

                        if (!biaya || !tanggalKunjungan) {
                            e.preventDefault();
                            alert('Harap lengkapi Tanggal Kunjungan dan Biaya');
                            return false;
                        }

                        if (mode === 'new') {
                            const pid = document.getElementById('pid_new').value;
                            const cabangId = document.getElementById('cabang_id_new').value;
                            const nama = document.getElementById('nama_new').value;

                            if (!pid || !cabangId || !nama) {
                                e.preventDefault();
                                alert('Harap lengkapi PID, Cabang, dan Nama Lengkap untuk pelanggan baru');
                                return false;
                            }
                        } else {
                            const existingId = document.getElementById('existing_pelanggan_id').value;
                            
                            if (!existingId) {
                                e.preventDefault();
                                alert('Harap cari dan pilih pelanggan yang sudah terdaftar');
                                return false;
                            }
                        }
                    });

                    // Initialize on load
                    toggleMode('new');
                </script>
            </div>
        </div>
    </div>
</div>
@endsection
