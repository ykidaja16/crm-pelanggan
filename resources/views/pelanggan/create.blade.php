@extends('layouts.main')

@section('title', 'Tambah Pelanggan - Medical Lab')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0 text-primary"><i class="fas fa-user-plus me-2"></i> Tambah Pelanggan Baru</h5>
            </div>
            <div class="card-body">
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

                <form action="{{ route('pelanggan.store') }}" method="POST">
                    @csrf
                    
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered" id="dynamicTable">
                            <thead class="table-light">
                                <tr>
                                    <th>NIK</th>
                                    <th>Nama Lengkap</th>
                                    <th>Alamat</th>
                                    <th>Biaya</th>
                                    <th>Tanggal Kunjungan</th>
                                    <th class="text-center" style="width: 50px;">#</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                @php
                                    $oldInputs = session('inputs', []);
                                    $errors = session('errors', []);
                                    $rowCount = max(count($oldInputs), 1);
                                @endphp
                                
                                @for($row = 0; $row < $rowCount; $row++)
                                <tr>
                                    <td>
                                        <input type="text" name="inputs[{{ $row }}][nik]" 
                                            class="form-control @isset($errors[$row]) is-invalid @endisset" 
                                            placeholder="NIK" 
                                            value="{{ old('inputs.' . $row . '.nik', $oldInputs[$row]['nik'] ?? '') }}" 
                                            required>
                                        @isset($errors[$row])
                                            @foreach($errors[$row] as $error)
                                                <div class="text-danger small">{{ $error }}</div>
                                            @endforeach
                                        @endisset
                                    </td>
                                    <td>
                                        <input type="text" name="inputs[{{ $row }}][nama]" 
                                            class="form-control" 
                                            placeholder="Nama Lengkap" 
                                            value="{{ old('inputs.' . $row . '.nama', $oldInputs[$row]['nama'] ?? '') }}" 
                                            required>
                                    </td>
                                    <td>
                                        <input type="text" name="inputs[{{ $row }}][alamat]" 
                                            class="form-control" 
                                            placeholder="Alamat" 
                                            value="{{ old('inputs.' . $row . '.alamat', $oldInputs[$row]['alamat'] ?? '') }}">
                                    </td>
                                    <td>
                                        <input type="text" name="inputs[{{ $row }}][biaya]" 
                                            class="form-control biaya-input" 
                                            placeholder="Biaya" 
                                            value="{{ old('inputs.' . $row . '.biaya', $oldInputs[$row]['biaya'] ?? '') }}" 
                                            required>
                                    </td>
                                    <td>
                                        <input type="date" name="inputs[{{ $row }}][tanggal_kunjungan]" 
                                            class="form-control" 
                                            value="{{ old('inputs.' . $row . '.tanggal_kunjungan', $oldInputs[$row]['tanggal_kunjungan'] ?? '') }}" 
                                            required>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-danger remove-tr" {{ $row == 0 ? 'disabled' : '' }}><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between">
                        <div>
                            <button type="button" id="add" class="btn btn-success text-white"><i class="fas fa-plus"></i> Tambah Baris</button>
                        </div>
                        <div>
                            <a href="{{ route('dashboard') }}" class="btn btn-secondary me-2">Kembali</a>
                            <button type="submit" class="btn btn-primary">Simpan Semua Data</button>
                        </div>
                    </div>
                </form>

                <script>
                    let i = 0;

                    // Function to format biaya input
                    function formatBiaya(input) {
                        let value = input.value.replace(/\D/g, ''); // Remove non-digits
                        value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.'); // Add dots as thousand separators
                        input.value = value;
                    }

                    // Event listener for existing biaya inputs
                    document.querySelectorAll('.biaya-input').forEach(input => {
                        input.addEventListener('input', function() {
                            formatBiaya(this);
                        });
                    });

                    document.getElementById('add').addEventListener('click', function() {
                        ++i;
                        let table = document.getElementById('dynamicTable').getElementsByTagName('tbody')[0];
                        let newRow = table.insertRow();
                        newRow.innerHTML = `
                            <td><input type="text" name="inputs[${i}][nik]" class="form-control" placeholder="NIK" required></td>
                            <td><input type="text" name="inputs[${i}][nama]" class="form-control" placeholder="Nama Lengkap" required></td>
                            <td><input type="text" name="inputs[${i}][alamat]" class="form-control" placeholder="Alamat"></td>
                            <td><input type="text" name="inputs[${i}][biaya]" class="form-control biaya-input" placeholder="Biaya" required></td>
                            <td><input type="date" name="inputs[${i}][tanggal_kunjungan]" class="form-control" required></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-danger remove-tr"><i class="fas fa-trash"></i></button></td>
                        `;

                        // Attach event listener for new biaya input
                        newRow.querySelector('.biaya-input').addEventListener('input', function() {
                            formatBiaya(this);
                        });

                        // Re-attach event listener for new delete button
                        newRow.querySelector('.remove-tr').addEventListener('click', function() {
                            this.closest('tr').remove();
                        });
                    });

                    // Initial remove buttons
                    document.querySelectorAll('.remove-tr').forEach(button => {
                        button.addEventListener('click', function() {
                            if(!this.disabled) {
                                this.closest('tr').remove();
                            }
                        });
                    });
                </script>
            </div>
        </div>
    </div>
</div>
@endsection
