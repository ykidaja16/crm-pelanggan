@extends('layouts.main')

@section('title', 'Laporan Pelanggan - Medical Lab CRM')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="text-primary mb-0 fw-semibold">Laporan Pelanggan</h4>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="mb-0 fw-semibold text-info">
                <i class="fas fa-filter me-2"></i>Filter Laporan
            </h6>
        </div>
        <div class="card-body">
            <form id="filterForm" class="row g-3">
                @csrf
                <!-- Point 4: Kelompok Pelanggan -->
                {{-- <div class="col-md-3">
                    <label class="form-label fw-medium small">Kelompok Pelanggan</label>
                    <select name="kelompok_pelanggan" class="form-select">
                        <option value="">Semua Kelompok</option>
                        <option value="mandiri">Mandiri</option>
                        <option value="klinisi">Klinisi</option>
                    </select>
                </div> --}}

                <!-- Point 4: Tipe Pelanggan -->
                <div class="col-md-3">
                    <label class="form-label fw-medium small">Tipe Pelanggan</label>
                    <select name="tipe_pelanggan" class="form-select">
                        <option value="">Semua Tipe</option>
                        <option value="biasa">Pelanggan Biasa</option>
                        <option value="khusus">Pelanggan Khusus</option>
                    </select>
                </div>

                <!-- Periode -->
                <div class="col-md-3">
                    <label class="form-label fw-medium small">Periode</label>
                    <select name="type" id="typeSelect" class="form-select">
                        <option value="semua">Semua Data</option>
                        <option value="perbulan">Per Bulan</option>
                        <option value="pertahun">Per Tahun</option>
                        <option value="range">Range Tanggal</option>
                    </select>
                </div>

                <!-- Bulan -->
                <div class="col-md-3" id="bulanContainer" style="display:none;">
                    <label class="form-label fw-medium small">Bulan</label>
                    <select name="bulan" class="form-select">
                        @for($i = 1; $i <= 12; $i++)
                            <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" {{ date('m') == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                {{ DateTime::createFromFormat('!m', $i)->format('F') }}
                            </option>
                        @endfor
                    </select>
                </div>

                <!-- Tahun -->
                <div class="col-md-3" id="tahunContainer" style="display:none;">
                    <label class="form-label fw-medium small">Tahun</label>
                    <select name="tahun" class="form-select">
                        @for($i = date('Y'); $i >= date('Y') - 8; $i--)
                            <option value="{{ $i }}" {{ date('Y') == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>

                <!-- Range Tanggal -->
                <div class="col-md-3" id="rangeContainer" style="display:none;">
                    <label class="form-label fw-medium small">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" class="form-control">
                </div>
                <div class="col-md-3" id="rangeContainer2" style="display:none;">
                    <label class="form-label fw-medium small">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" class="form-control">
                </div>

                <!-- Cabang -->
                <div class="col-md-3">
                    <label class="form-label fw-medium small">Cabang</label>
                    <select name="cabang_id" class="form-select">
                        <option value="">Semua Cabang</option>
                        @foreach($cabangs as $cabang)
                            <option value="{{ $cabang->id }}">{{ $cabang->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Kelas -->
                <div class="col-md-3">
                    <label class="form-label fw-medium small">Kelas</label>
                    <select name="kelas" class="form-select">
                        <option value="">Semua Kelas</option>
                        <option value="Prioritas">Prioritas</option>
                        <option value="Loyal">Loyal</option>
                        <option value="Potensial">Potensial</option>
                    </select>
                </div>

                <!-- Range Omset -->
                <div class="col-md-3">
                    <label class="form-label fw-medium small">Range Omset</label>
                    <select name="omset_range" class="form-select">
                        <option value="">Semua Omset</option>
                        <option value="0">0 - < 1 Juta</option>
                        <option value="1">1 Juta - < 4 Juta</option>
                        <option value="2">4 Juta - Lebih</option>
                    </select>
                </div>

                <!-- Range Kedatangan -->
                <div class="col-md-3">
                    <label class="form-label fw-medium small">Jumlah Kunjungan</label>
                    <select name="kedatangan_range" class="form-select">
                        <option value="">Semua Kunjungan</option>
                        <option value="0">≤ 2 Kali</option>
                        <option value="1">3 - 4 Kali</option>
                        <option value="2">> 4 Kali</option>
                    </select>
                </div>

                <!-- Sorting -->
                <div class="col-md-3">
                    <label class="form-label fw-medium small">Urutkan Berdasarkan</label>
                    <select name="sort" class="form-select">
                        <option value="nama">Nama</option>
                        <option value="total_biaya">Total Biaya</option>
                        <option value="total_kedatangan">Jumlah Kunjungan</option>
                        <option value="tgl_kunjungan_terakhir">Tanggal Kunjungan Terakhir</option>
                        <option value="class">Kelas</option>
                    </select>
                </div>


                <div class="col-md-3">
                    <label class="form-label fw-medium small">Arah</label>
                    <select name="direction" class="form-select">
                        <option value="asc">Ascending (A-Z)</option>
                        <option value="desc">Descending (Z-A)</option>
                    </select>
                </div>

                <!-- Buttons -->
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="button" id="btnPreview" class="btn btn-info">
                            <i class="fas fa-eye me-2"></i>Preview Laporan
                        </button>
                        <button type="button" id="btnReset" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Reset Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Card (Hidden by default) -->
    <div id="summaryCard" class="card shadow-sm border-0 mb-4" style="display:none;">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="mb-0 fw-semibold text-success">
                <i class="fas fa-chart-bar me-2"></i>Ringkasan Laporan
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="alert alert-primary mb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted d-block">Total Pelanggan</small>
                                <strong class="fs-5" id="summaryTotalPelanggan">0</strong>
                            </div>
                            <i class="fas fa-users fa-2x text-primary opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-warning mb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted d-block">Total Kunjungan</small>
                                <strong class="fs-5" id="summaryTotalKunjungan">0</strong>
                            </div>
                            <i class="fas fa-calendar-check fa-2x text-warning opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Export Buttons (Hidden by default) -->
    <div id="exportButtons" class="d-flex gap-2 mb-4" style="display:none;">
        <button type="button" id="btnExportExcel" class="btn btn-success">
            <i class="fas fa-file-excel me-2"></i>Export Excel
        </button>
        <button type="button" id="btnPrint" class="btn btn-primary">
            <i class="fas fa-print me-2"></i>Print
        </button>
    </div>


    <!-- Preview Table (Hidden by default) -->
    <div id="previewTable" class="card shadow-sm border-0" style="display:none;">
        <div class="card-header bg-white py-2 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold text-dark fs-6">
                <i class="fas fa-table me-2"></i>Preview Data
            </h6>
            <span id="previewInfo" class="badge bg-secondary">Menampilkan 0 data</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle small" style="min-width: 1200px;">
                    <thead class="table-light">
                        <tr>
                            <th class="px-2 py-2 text-center fw-semibold" style="width: 35px;">No</th>
                            <th class="py-2 fw-semibold" style="width: 100px;">PID</th>
                            <th class="py-2 fw-semibold" style="min-width: 200px;">Nama Pasien</th>
                            <th class="py-2 text-center fw-semibold" style="width: 100px;">Cabang</th>
                            <th class="py-2 fw-semibold" style="width: 100px;">No Telp</th>
                            <th class="py-2 text-center fw-semibold" style="width: 85px;">DOB</th>
                            <th class="py-2 fw-semibold" style="min-width: 120px;">Alamat</th>
                            <th class="py-2 text-center fw-semibold" style="width: 65px;">Kunjungan</th>
                            <th class="py-2 text-center fw-semibold" style="width: 100px;">Kunjungan Terakhir</th>
                            <th class="py-2 text-end fw-semibold" style="width: 100px;">Total Biaya</th>
                            <th class="py-2 text-center fw-semibold" style="width: 75px;">Kelas</th>
                        </tr>
                    </thead>
                    <tbody id="previewTableBody">
                        <!-- Data will be loaded here -->
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div id="paginationContainer" class="d-flex justify-content-between align-items-center p-3 border-top bg-light small">
                <!-- Pagination will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div id="loadingIndicator" class="text-center py-5" style="display:none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Memuat data...</p>
    </div>

    <!-- Empty State -->
    <div id="emptyState" class="text-center py-5 text-muted" style="display:none;">
        <i class="fas fa-inbox fa-3x mb-3 text-secondary opacity-50"></i>
        <p class="mb-0">Tidak ada data yang sesuai dengan filter.</p>
    </div>

    <!-- Debug Info (Hidden by default) -->
    <div id="debugInfo" class="alert alert-danger" style="display:none;">
        <h6>Error Details:</h6>
        <pre id="debugContent" class="mb-0 small"></pre>
    </div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('typeSelect');
    const bulanContainer = document.getElementById('bulanContainer');
    const tahunContainer = document.getElementById('tahunContainer');
    const rangeContainer = document.getElementById('rangeContainer');
    const rangeContainer2 = document.getElementById('rangeContainer2');
    
    // Handle periode type change
    typeSelect.addEventListener('change', function() {
        const value = this.value;
        
        // Hide all first
        bulanContainer.style.display = 'none';
        tahunContainer.style.display = 'none';
        rangeContainer.style.display = 'none';
        rangeContainer2.style.display = 'none';
        
        // Show relevant fields
        if (value === 'perbulan') {
            bulanContainer.style.display = 'block';
            tahunContainer.style.display = 'block';
        } else if (value === 'pertahun') {
            tahunContainer.style.display = 'block';
        } else if (value === 'range') {
            rangeContainer.style.display = 'block';
            rangeContainer2.style.display = 'block';
        }
    });
    
    // Preview button
    document.getElementById('btnPreview').addEventListener('click', function() {
        loadPreview();
    });
    
    // Reset button
    document.getElementById('btnReset').addEventListener('click', function() {
        document.getElementById('filterForm').reset();
        typeSelect.value = 'semua';
        typeSelect.dispatchEvent(new Event('change'));
        
        // Hide all sections
        document.getElementById('summaryCard').style.display = 'none';
        document.getElementById('exportButtons').style.display = 'none';
        document.getElementById('previewTable').style.display = 'none';
        document.getElementById('emptyState').style.display = 'none';
        document.getElementById('debugInfo').style.display = 'none';
    });
    
    // Export buttons
    document.getElementById('btnExportExcel').addEventListener('click', function() {
        exportData('excel');
    });
    
    document.getElementById('btnPrint').addEventListener('click', function() {

        exportData('print');
    });
    
    function loadPreview(page = 1) {
        const form = document.getElementById('filterForm');
        const formData = new FormData(form);
        formData.append('page', page);
        
        // Show loading
        document.getElementById('loadingIndicator').style.display = 'block';
        document.getElementById('previewTable').style.display = 'none';
        document.getElementById('emptyState').style.display = 'none';
        document.getElementById('debugInfo').style.display = 'none';
        
        // Build query string
        const params = new URLSearchParams();
        formData.forEach((value, key) => {
            if (value && key !== '_token') params.append(key, value);
        });
        
        const url = '{{ route("laporan.preview") }}?' + params.toString();
        console.log('Fetching URL:', url);
        
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('Error response:', text);
                    throw new Error('HTTP ' + response.status + ': ' + text.substring(0, 500));
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            document.getElementById('loadingIndicator').style.display = 'none';
            
            // Check for server-side error
            if (data.error) {
                document.getElementById('debugInfo').style.display = 'block';
                document.getElementById('debugContent').textContent = 
                    'Error: ' + data.message + '\nFile: ' + data.file + '\nLine: ' + data.line;
                return;
            }
            
            if (!data.data || !data.data.data || data.data.data.length === 0) {
                document.getElementById('emptyState').style.display = 'block';
                document.getElementById('summaryCard').style.display = 'none';
                document.getElementById('exportButtons').style.display = 'none';
                return;
            }
            
            // Show summary
            document.getElementById('summaryCard').style.display = 'block';
            document.getElementById('summaryTotalPelanggan').textContent = (data.summary.total_pelanggan || 0).toLocaleString('id-ID');
            document.getElementById('summaryTotalKunjungan').textContent = (data.summary.total_kunjungan || 0).toLocaleString('id-ID');

            
            // Show export buttons
            document.getElementById('exportButtons').style.display = 'flex';
            
            // Show table
            document.getElementById('previewTable').style.display = 'block';
            
            // Populate table
            const tbody = document.getElementById('previewTableBody');
            tbody.innerHTML = '';
            
            data.data.data.forEach((item, index) => {
                const row = document.createElement('tr');
                const kelasBadge = getKelasBadge(item.class);
                const startNumber = (data.data.current_page - 1) * data.data.per_page + 1;
                
                row.innerHTML = `
                    <td class="px-2 py-2 text-center">${startNumber + index}</td>
                    <td class="py-2"><code class="bg-light px-1 py-1 rounded small text-nowrap">${item.pid || '-'}</code></td>
                    <td class="py-2 fw-medium">${item.nama || '-'}</td>
                    <td class="py-2 text-center">
                        <span class="badge bg-info bg-opacity-10 text-info border border-info small text-nowrap">${item.cabang?.nama || '-'}</span>
                    </td>
                    <td class="py-2 text-nowrap small">${item.no_telp || '-'}</td>
                    <td class="py-2 text-center text-nowrap small">${item.dob ? formatDate(item.dob) : '-'}</td>
                    <td class="py-2 small">${item.alamat ? item.alamat.substring(0, 25) : '-'}</td>
                    <td class="py-2 text-center"><span class="badge bg-secondary bg-opacity-10 text-secondary small">${item.total_kedatangan || 0}</span></td>
                    <td class="py-2 text-center text-nowrap small">${item.tgl_kunjungan_terakhir ? formatDate(item.tgl_kunjungan_terakhir) : '-'}</td>

                    <td class="py-2 text-end fw-semibold text-nowrap small">Rp ${(item.total_biaya || 0).toLocaleString('id-ID')}</td>
                    <td class="py-2 text-center">${kelasBadge}</td>
                `;
                tbody.appendChild(row);
            });
            
            // Update info
            document.getElementById('previewInfo').textContent = `Menampilkan ${data.data.from || 1}-${data.data.to || data.data.data.length} dari ${data.data.total} data`;
            
            // Render pagination
            renderPagination(data.data);
        })
        .catch(error => {
            document.getElementById('loadingIndicator').style.display = 'none';
            console.error('Error:', error);
            document.getElementById('debugInfo').style.display = 'block';
            document.getElementById('debugContent').textContent = error.message;
            alert('Terjadi kesalahan saat memuat data. Detail error ditampilkan di bawah.');
        });
    }
    
    function getKelasBadge(kelas) {
        const badgeClass = {
            'Prioritas': 'bg-danger bg-opacity-10 text-danger border border-danger',
            'Loyal': 'bg-success bg-opacity-10 text-success border border-success',
            'Potensial': 'bg-warning bg-opacity-10 text-warning border border-warning'
        }[kelas] || 'bg-secondary bg-opacity-10 text-secondary border border-secondary';
        
        return `<span class="badge ${badgeClass} small">${kelas || 'Potensial'}</span>`;
    }
    
    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '-';
        return date.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }
    
    function renderPagination(data) {
        const container = document.getElementById('paginationContainer');
        
        if (!data || data.last_page <= 1) {
            container.innerHTML = '';
            return;
        }
        
        let html = '<div class="text-muted">';
        html += `Menampilkan <strong>${data.from || 1} - ${data.to || data.data.length}</strong> dari <strong>${data.total}</strong> data`;
        html += '</div>';
        
        html += '<div class="btn-group btn-group-sm">';
        
        // Previous
        if (data.prev_page_url) {
            html += `<button type="button" class="btn btn-outline-primary" onclick="window.changePage(${data.current_page - 1})"><i class="fas fa-chevron-left"></i></button>`;
        } else {
            html += `<button type="button" class="btn btn-outline-secondary" disabled><i class="fas fa-chevron-left"></i></button>`;
        }
        
        // Page numbers
        const startPage = Math.max(1, data.current_page - 2);
        const endPage = Math.min(data.last_page, data.current_page + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            if (i === data.current_page) {
                html += `<button type="button" class="btn btn-primary">${i}</button>`;
            } else {
                html += `<button type="button" class="btn btn-outline-primary" onclick="window.changePage(${i})">${i}</button>`;
            }
        }
        
        // Next
        if (data.next_page_url) {
            html += `<button type="button" class="btn btn-outline-primary" onclick="window.changePage(${data.current_page + 1})"><i class="fas fa-chevron-right"></i></button>`;
        } else {
            html += `<button type="button" class="btn btn-outline-secondary" disabled><i class="fas fa-chevron-right"></i></button>`;
        }
        
        html += '</div>';
        
        container.innerHTML = html;
    }
    
    // Global function for pagination
    window.changePage = function(page) {
        loadPreview(page);
    };
    
    function exportData(format) {
        const form = document.getElementById('filterForm');
        const formData = new FormData(form);
        
        // Build query string
        const params = new URLSearchParams();
        formData.forEach((value, key) => {
            if (value && key !== '_token') params.append(key, value);
        });
        params.append('format', format);
        
        const url = '{{ route("laporan.export") }}?' + params.toString();
        
        if (format === 'print') {
            // Open in new window for print
            const printWindow = window.open(url, '_blank');
            if (printWindow) {
                printWindow.focus();
            }
        } else {
            // Download file
            window.location.href = url;
        }
    }
});
</script>
@endsection
