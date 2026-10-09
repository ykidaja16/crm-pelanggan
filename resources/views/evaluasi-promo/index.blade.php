@extends('layouts.main')

@section('title', 'Evaluasi Event / Promo')

@section('content')
<style>
.eval-card-kpi {
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    transition: all 0.2s ease-in-out;
}
.eval-card-kpi:hover {
    box-shadow: 0 6px 18px rgba(0,0,0,0.06);
    transform: translateY(-2px);
}
.eval-icon-circle {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}
.badge-status-baru {
    background-color: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
}
.badge-status-lama {
    background-color: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
}
</style>

<div class="container-fluid px-0">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h4 class="mb-1 fw-bold text-primary">
                <i class="fas fa-bullhorn me-2"></i>Evaluasi Event / Promo (MOU)
            </h4>
            <p class="text-muted small mb-0">
                Ukur efektivitas promo/event dalam menarik kunjungan kembali pasien di kemudian hari serta identifikasi pelanggan baru vs lama.
            </p>
        </div>
        @if($isFiltered && $results->count() > 0)
        <div>
            <a href="{{ route('evaluasi-promo.export', request()->all()) }}" class="btn btn-success btn-sm px-3 shadow-sm">
                <i class="fas fa-file-excel me-1"></i> Export Excel
            </a>
        </div>
        @endif
    </div>

    {{-- Filter Form --}}
    <div class="card shadow-sm border-0 mb-4 rounded-3">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="card-title mb-0 fw-bold text-dark">
                <i class="fas fa-filter text-primary me-2"></i>Filter Evaluasi Event / Promo
            </h6>
        </div>
        <div class="card-body p-3">
            <form method="GET" action="{{ route('evaluasi-promo.index') }}" id="evaluasiForm">
                <div class="row g-3">
                    {{-- Dropdown / Autocomplete MOU/Agreement --}}
                    <div class="col-md-4">
                        <label for="mouInput" class="form-label fw-bold text-dark small mb-1">
                            Pilih / Masukkan Nama MOU / Agreement: <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-info border-end-0">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text"
                                   name="mou"
                                   id="mouInput"
                                   class="form-control border-start-0 ps-0"
                                   list="mouDatalist"
                                   placeholder="Ketik MOU / promo (contoh: Promo Kemerdekaan)..."
                                   value="{{ $mou }}"
                                   autocomplete="off"
                                   required>
                            <button class="btn btn-outline-info dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Pilih dari daftar"></button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="max-height: 250px; overflow-y: auto;" id="mouDropdownMenu">
                                <li><h6 class="dropdown-header">Daftar MOU / Agreement</h6></li>
                                @if(isset($mouOptions) && count($mouOptions) > 0)
                                    @foreach($mouOptions as $opt)
                                        <li>
                                            <a class="dropdown-item small" href="javascript:void(0)" onclick="selectMou('{{ addslashes($opt) }}')">
                                                {{ $opt }}
                                            </a>
                                        </li>
                                    @endforeach
                                @else
                                    <li><span class="dropdown-item text-muted small">Belum ada data MOU / Promo</span></li>
                                @endif
                            </ul>
                            <datalist id="mouDatalist">
                                @if(isset($mouOptions))
                                    @foreach($mouOptions as $opt)
                                        <option value="{{ $opt }}">
                                    @endforeach
                                @endif
                            </datalist>
                        </div>
                        <div class="form-text small text-muted">Contoh: Promo Kemerdekaan, MOU Bank BCA</div>
                    </div>

                    {{-- Periode Promo / Event Asal --}}
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">
                            Periode Promo / Event <span class="text-muted fw-normal">(Opsional)</span>
                        </label>
                        <div class="input-group">
                            <input type="date"
                                   name="tgl_promo_mulai"
                                   class="form-control"
                                   value="{{ $tglPromoMulai }}"
                                   placeholder="Mulai">
                            <span class="input-group-text bg-light">s/d</span>
                            <input type="date"
                                   name="tgl_promo_selesai"
                                   class="form-control"
                                   value="{{ $tglPromoSelesai }}"
                                   placeholder="Selesai">
                        </div>
                        <div class="form-text small text-muted">Rentang tanggal saat promo diberikan</div>
                    </div>

                    {{-- Periode Evaluasi Kunjungan Ulang --}}
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">
                            Periode Evaluasi Kedatangan <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="date"
                                   name="tgl_evaluasi_mulai"
                                   class="form-control"
                                   value="{{ $tglEvaluasiMulai }}"
                                   required>
                            <span class="input-group-text bg-light">s/d</span>
                            <input type="date"
                                   name="tgl_evaluasi_selesai"
                                   class="form-control"
                                   value="{{ $tglEvaluasiSelesai }}"
                                   required>
                        </div>
                        <div class="form-text small text-muted">Mencari pasien yang datang lagi di rentang ini</div>
                    </div>

                    {{-- Filter Jenis Pelanggan --}}
                    <div class="col-md-3">
                        <label for="jenis_pelanggan" class="form-label fw-semibold small">Jenis Pelanggan</label>
                        <select name="jenis_pelanggan" id="jenis_pelanggan" class="form-select">
                            <option value="semua" {{ $jenisPelanggan === 'semua' ? 'selected' : '' }}>Semua Jenis Pelanggan</option>
                            <option value="baru" {{ $jenisPelanggan === 'baru' ? 'selected' : '' }}>Pelanggan Baru Saja</option>
                            <option value="lama" {{ $jenisPelanggan === 'lama' ? 'selected' : '' }}>Pelanggan Lama Saja</option>
                        </select>
                        <div class="form-text small text-muted">Baru = belum ada data di CRM sebelum promo</div>
                    </div>

                    {{-- Filter Cabang --}}
                    <div class="col-md-3">
                        <label for="cabang_id" class="form-label fw-semibold small">Cabang</label>
                        <select name="cabang_id" id="cabang_id" class="form-select">
                            @if($isMultiCabang ?? true)
                                <option value="">Semua Cabang</option>
                            @endif
                            @foreach($cabangs as $c)
                                <option value="{{ $c->id }}" {{ ((string)$cabangId === (string)$c->id) || (!($isMultiCabang ?? true)) ? 'selected' : '' }}>
                                    {{ $c->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tombol Aksi --}}
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small d-none d-md-block invisible" style="user-select: none;">Aksi</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-search me-1"></i> Evaluasi Data
                            </button>
                            <a href="{{ route('evaluasi-promo.index') }}" class="btn btn-outline-secondary px-3">
                                <i class="fas fa-undo me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($isFiltered)
    {{-- KPI Cards Ringkasan Efektivitas Promo --}}
    <div class="row g-3 mb-4">
        {{-- Total Pasien Promo --}}
        <div class="col-md-3 col-sm-6">
            <div class="card eval-card-kpi p-3 h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="eval-icon-circle bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Peserta Promo</div>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($summary['total_peserta_promo']) }}</h4>
                        <small class="text-muted" style="font-size: 0.75rem;">
                            {{ $summary['total_peserta_baru'] }} Baru | {{ $summary['total_peserta_lama'] }} Lama
                        </small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pasien Datang Lagi di Masa Evaluasi --}}
        <div class="col-md-3 col-sm-6">
            <div class="card eval-card-kpi p-3 h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="eval-icon-circle bg-success bg-opacity-10 text-success">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Datang Lagi (Repeat)</div>
                        <h4 class="mb-0 fw-bold text-success">{{ number_format($summary['total_repeat_visitor']) }}</h4>
                        <small class="text-muted" style="font-size: 0.75rem;">
                            Di periode evaluasi
                        </small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tingkat Konversi / Efektivitas Promo --}}
        <div class="col-md-3 col-sm-6">
            <div class="card eval-card-kpi p-3 h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="eval-icon-circle bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Tingkat Efektivitas</div>
                        <h4 class="mb-0 fw-bold text-warning">{{ $summary['retention_rate'] }}%</h4>
                        <small class="text-muted" style="font-size: 0.75rem;">
                            Dari total peserta promo
                        </small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pelanggan Baru vs Lama yang Datang Lagi --}}
        <div class="col-md-3 col-sm-6">
            <div class="card eval-card-kpi p-3 h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="eval-icon-circle bg-info bg-opacity-10 text-info">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Pelanggan Baru Kembali</div>
                        <h4 class="mb-0 fw-bold text-info">{{ number_format($summary['repeat_visitor_baru']) }}</h4>
                        <small class="text-muted" style="font-size: 0.75rem;">
                            {{ $summary['repeat_visitor_lama'] }} Pelanggan Lama Kembali
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel Hasil Evaluasi --}}
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <h6 class="card-title mb-0 fw-bold text-dark">
                    <i class="fas fa-list-alt text-primary me-2"></i>Daftar Pasien Promo yang Datang Kembali
                </h6>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2 py-1">
                    {{ $results->count() }} Pasien
                </span>
                @if($jenisPelanggan !== 'semua')
                    <span class="badge bg-secondary px-2 py-1">
                        Filter: Pelanggan {{ ucfirst($jenisPelanggan) }}
                    </span>
                @endif
            </div>

            @if($results->count() > 0)
            <a href="{{ route('evaluasi-promo.export', request()->all()) }}" class="btn btn-outline-success btn-sm">
                <i class="fas fa-file-excel me-1"></i> Download Excel
            </a>
            @endif
        </div>

        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 650px;">
                <table class="table table-hover table-striped mb-0 align-middle small text-nowrap">
                    <thead class="table-light sticky-top" style="z-index: 5;">
                        <tr>
                            <th class="text-center px-3 py-3" style="width: 50px;">No</th>
                            <th class="py-3">PID</th>
                            <th class="py-3">Nama Pasien</th>
                            <th class="py-3">NIK</th>
                            <th class="py-3">Cabang</th>
                            <th class="py-3">No Telp</th>
                            <th class="py-3">DOB</th>
                            <th class="py-3">Alamat</th>
                            <th class="text-center py-3">Total Kunjungan</th>
                            <th class="py-3">Kunjungan Terakhir</th>
                            <th class="py-3">MOU/Agreement Terakhir</th>
                            <th class="py-3">Pemeriksaan Terakhir</th>
                            <th class="py-3">Tgl Pemeriksaan Terakhir</th>
                            <th class="text-center py-3">Kelas</th>
                            <th class="text-center py-3">Status Pelanggan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($results as $index => $row)
                            @php
                                $kelasBadge = match($row['class']) {
                                    'Prioritas' => 'bg-danger bg-opacity-10 text-danger border border-danger',
                                    'Loyal'     => 'bg-success bg-opacity-10 text-success border border-success',
                                    'Potensial' => 'bg-warning bg-opacity-10 text-warning border border-warning',
                                    default     => 'bg-secondary bg-opacity-10 text-secondary border border-secondary',
                                };
                                $statusBadge = $row['status_pelanggan'] === 'Pelanggan Baru'
                                    ? 'badge-status-baru'
                                    : 'badge-status-lama';
                            @endphp
                            <tr>
                                <td class="text-center px-3">{{ $index + 1 }}</td>
                                <td class="fw-semibold">
                                    <a href="{{ route('pelanggan.show', $row['id']) }}" class="text-decoration-none text-primary" target="_blank">
                                        {{ $row['pid'] }} <i class="fas fa-external-link-alt ms-1 text-muted" style="font-size: 10px;"></i>
                                    </a>
                                </td>
                                <td class="fw-semibold text-dark">{{ $row['nama'] }}</td>
                                <td>{{ $row['nik'] }}</td>
                                <td>{{ $row['cabang'] }}</td>
                                <td>{{ $row['no_telp'] }}</td>
                                <td>{{ $row['dob'] }}</td>
                                <td class="text-truncate" style="max-width: 200px;" title="{{ $row['alamat'] }}">{{ $row['alamat'] }}</td>
                                <td class="text-center fw-bold text-primary">{{ $row['total_kunjungan'] }}</td>
                                <td>{{ $row['kunjungan_terakhir'] }}</td>
                                <td>
                                    @if($row['mou_terakhir'] !== '-')
                                        <span class="badge bg-light text-dark border">{{ $row['mou_terakhir'] }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($row['pemeriksaan_terakhir'] !== '-')
                                        <span class="text-dark">{{ $row['pemeriksaan_terakhir'] }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $row['tgl_pemeriksaan_terakhir'] }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $kelasBadge }}">{{ $row['class'] }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $statusBadge }} px-2 py-1">
                                        <i class="fas {{ $row['status_pelanggan'] === 'Pelanggan Baru' ? 'fa-user-plus' : 'fa-history' }} me-1"></i>
                                        {{ $row['status_pelanggan'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="15" class="text-center py-5 text-muted">
                                    <i class="fas fa-info-circle fa-2x mb-3 text-secondary d-block"></i>
                                    Tidak ada pasien dari promo "<strong>{{ $mou }}</strong>" yang memiliki kunjungan lagi pada periode evaluasi yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @else
    {{-- Empty State Sebelum Filter Dijalankan --}}
    <div class="card shadow-sm border-0 rounded-3 p-5 text-center my-4">
        <div class="py-4">
            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                <i class="fas fa-bullhorn fa-2x"></i>
            </div>
            <h5 class="fw-bold text-dark">Mulai Evaluasi Event / Promo</h5>
            <p class="text-muted col-md-6 mx-auto mb-3">
                Silakan pilih <strong>MOU / Agreement</strong> promo yang ingin dievaluasi, tentukan <strong>Periode Evaluasi Kedatangan</strong>, lalu klik tombol <strong>Evaluasi Data</strong> untuk melihat pasien yang berkunjung kembali.
            </p>
        </div>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script id="mouOptionsData" type="application/json">{!! json_encode(isset($mouOptions) ? $mouOptions->values()->toArray() : []) !!}</script>
<script>
const allMouOptions = JSON.parse(document.getElementById('mouOptionsData')?.textContent || '[]');

function selectMou(val) {
    const input = document.getElementById('mouInput');
    if (!input) return;
    input.value = val;
    input.focus();
}

function updateMouDatalist() {
    const mouInput = document.getElementById('mouInput');
    const mouDatalist = document.getElementById('mouDatalist');
    const mouDropdownMenu = document.getElementById('mouDropdownMenu');
    if (!mouInput) return;

    const val = mouInput.value.trim().toLowerCase();
    const matched = val 
        ? allMouOptions.filter(function(opt) { return opt.toLowerCase().indexOf(val) !== -1; })
        : allMouOptions;

    if (mouDatalist) {
        let html = '';
        matched.slice(0, 50).forEach(function(opt) {
            html += '<option value="' + opt.replace(/"/g, '&quot;') + '">';
        });
        mouDatalist.innerHTML = html;
    }

    if (mouDropdownMenu) {
        let menuHtml = '<li><h6 class="dropdown-header">Daftar MOU / Agreement</h6></li>';
        if (matched.length > 0) {
            matched.slice(0, 50).forEach(function(opt) {
                const safeOpt = opt.replace(/'/g, "\\'");
                menuHtml += '<li><a class="dropdown-item small" href="javascript:void(0)" onclick="selectMou(\'' + safeOpt + '\')">' + opt + '</a></li>';
            });
        } else {
            menuHtml += '<li><span class="dropdown-item text-muted small">Tidak ada MOU yang cocok</span></li>';
        }
        mouDropdownMenu.innerHTML = menuHtml;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const mouInput = document.getElementById('mouInput');
    if (mouInput) {
        mouInput.addEventListener('input', updateMouDatalist);
        mouInput.addEventListener('focus', updateMouDatalist);
    }
});
</script>
@endsection
