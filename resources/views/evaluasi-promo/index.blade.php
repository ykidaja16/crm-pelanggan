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
.nav-pills .nav-link {
    border-radius: 8px;
    color: #4b5563;
    font-weight: 600;
}
.nav-pills .nav-link.active {
    background-color: #0d6efd;
    color: #ffffff;
    box-shadow: 0 2px 6px rgba(13,110,253,0.3);
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
                Ukur efektivitas promo/event dalam menarik kunjungan kembali pasien serta komparasi kinerja antar promo (peningkatan / penurunan).
            </p>
        </div>
        @if(($activeTab ?? 'single') === 'single' && $isFiltered && $results->count() > 0)
        <div>
            <a href="{{ route('evaluasi-promo.export', request()->all()) }}" class="btn btn-success btn-sm px-3 shadow-sm">
                <i class="fas fa-file-excel me-1"></i> Export Excel
            </a>
        </div>
        @endif
    </div>

    {{-- Tab Navigation --}}
    <ul class="nav nav-pills mb-4 gap-2 bg-light p-2 rounded-3 border" id="promoTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ ($activeTab ?? 'single') !== 'compare' ? 'active' : '' }}" 
                    id="single-tab" 
                    data-bs-toggle="pill" 
                    data-bs-target="#tab-single" 
                    type="button" 
                    role="tab">
                <i class="fas fa-chart-line me-2"></i>Evaluasi Single Promo
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ ($activeTab ?? 'single') === 'compare' ? 'active' : '' }}" 
                    id="compare-tab" 
                    data-bs-toggle="pill" 
                    data-bs-target="#tab-compare" 
                    type="button" 
                    role="tab">
                <i class="fas fa-balance-scale me-2"></i>Komparasi Antar Promo
            </button>
        </li>
    </ul>

    <div class="tab-content" id="promoTabsContent">
        {{-- ============================================================== --}}
        {{-- TAB 1: EVALUASI SINGLE PROMO                                   --}}
        {{-- ============================================================== --}}
        <div class="tab-pane fade {{ ($activeTab ?? 'single') !== 'compare' ? 'show active' : '' }}" id="tab-single" role="tabpanel">
            {{-- Filter Form --}}
            <div class="card shadow-sm border-0 mb-4 rounded-3">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="card-title mb-0 fw-bold text-dark">
                        <i class="fas fa-filter text-primary me-2"></i>Filter Evaluasi Event / Promo
                    </h6>
                </div>
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('evaluasi-promo.index') }}" id="evaluasiForm">
                        <input type="hidden" name="tab" value="single">
                        <div class="row g-3">
                            {{-- Dropdown / Autocomplete MOU/Agreement --}}
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label for="mouInput" class="form-label fw-bold text-dark small mb-0">
                                        MOU / Agreement: <span class="text-danger">*</span>
                                    </label>
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info small" style="font-size: 0.7rem;">
                                        Koma (,) = ATAU | Plus (+) = DAN
                                    </span>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-info border-end-0">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text"
                                           name="mou"
                                           id="mouInput"
                                           class="form-control border-start-0 ps-0"
                                           list="mouDatalist"
                                           placeholder="Ketik MOU (contoh: Promo A, Promo B atau Promo A + Promo B)..."
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
                                <div class="form-text small text-muted">Bisa gunakan koma (,) untuk logika ATAU, dan tanda plus (+) untuk logika DAN.</div>
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
                                <div class="text-muted small fw-semibold">Repeat: Baru vs Lama</div>
                                <h5 class="mb-0 fw-bold text-info">
                                    {{ $summary['repeat_visitor_baru'] }} <span class="text-muted fs-6 fw-normal">baru /</span> {{ $summary['repeat_visitor_lama'] }} <span class="text-muted fs-6 fw-normal">lama</span>
                                </h5>
                                <small class="text-muted" style="font-size: 0.75rem;">
                                    Rasio pengunjung ulang
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabel Hasil Evaluasi Pasien --}}
            <div class="card shadow-sm border-0 rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="card-title mb-0 fw-bold text-dark">
                            <i class="fas fa-list-alt text-primary me-2"></i>Daftar Pasien Promo yang Datang Lagi
                        </h6>
                        <small class="text-muted">
                            Total {{ number_format($results->count()) }} pasien dari promo <strong>"{{ $mou }}"</strong> kembali melakukan kunjungan pada rentang evaluasi.
                        </small>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
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

        {{-- ============================================================== --}}
        {{-- TAB 2: KOMPARASI ANTAR PROMO (Revisi Poin 2)                    --}}
        {{-- ============================================================== --}}
        <div class="tab-pane fade {{ ($activeTab ?? 'single') === 'compare' ? 'show active' : '' }}" id="tab-compare" role="tabpanel">
            <div class="card shadow-sm border-0 mb-4 rounded-3">
                <div class="card-header bg-white py-3 border-bottom">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h6 class="card-title mb-0 fw-bold text-dark">
                            <i class="fas fa-balance-scale text-primary me-2"></i>Form Komparasi Kinerja Antar Promo
                        </h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary small">
                            Membandingkan Promo A vs Promo B
                        </span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form method="GET" action="{{ route('evaluasi-promo.index') }}" id="compareForm">
                        <input type="hidden" name="tab" value="compare">

                        <div class="row g-4">
                            {{-- PROMO A --}}
                            <div class="col-lg-6">
                                <div class="p-3 border rounded-3 bg-light bg-opacity-50 h-100">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <span class="badge bg-primary px-3 py-2 fw-bold fs-6">PROMO A</span>
                                        <span class="text-muted small">(Promo Pembanding Awal / Baseline)</span>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-dark small">
                                            Nama MOU / Promo A: <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white text-primary border-end-0"><i class="fas fa-tag"></i></span>
                                            <input type="text"
                                                   name="mou_a"
                                                   id="mouInputA"
                                                   class="form-control border-start-0"
                                                   list="mouDatalistA"
                                                   placeholder="Contoh: Promo Kemerdekaan 2024..."
                                                   value="{{ $mouA }}"
                                                   autocomplete="off"
                                                   required>
                                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"></button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="max-height: 250px; overflow-y: auto;">
                                                @if(isset($mouOptions))
                                                    @foreach($mouOptions as $opt)
                                                        <li><a class="dropdown-item small" href="javascript:void(0)" onclick="selectMouField('mouInputA', '{{ addslashes($opt) }}')">{{ $opt }}</a></li>
                                                    @endforeach
                                                @endif
                                            </ul>
                                            <datalist id="mouDatalistA">
                                                @if(isset($mouOptions))
                                                    @foreach($mouOptions as $opt)
                                                        <option value="{{ $opt }}">
                                                    @endforeach
                                                @endif
                                            </datalist>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">Periode Promo A (Opsional):</label>
                                        <div class="input-group">
                                            <input type="date" name="tgl_promo_mulai_a" class="form-control" value="{{ $tglPromoMulaiA }}" placeholder="Mulai">
                                            <span class="input-group-text bg-white">s/d</span>
                                            <input type="date" name="tgl_promo_selesai_a" class="form-control" value="{{ $tglPromoSelesaiA }}" placeholder="Selesai">
                                        </div>
                                    </div>

                                    <div class="mb-0">
                                        <label class="form-label fw-semibold small">Periode Evaluasi Datang Kembali A (Opsional):</label>
                                        <div class="input-group">
                                            <input type="date" name="tgl_evaluasi_mulai_a" class="form-control" value="{{ $tglEvaluasiMulaiA }}">
                                            <span class="input-group-text bg-white">s/d</span>
                                            <input type="date" name="tgl_evaluasi_selesai_a" class="form-control" value="{{ $tglEvaluasiSelesaiA }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- PROMO B --}}
                            <div class="col-lg-6">
                                <div class="p-3 border rounded-3 bg-light bg-opacity-50 h-100">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <span class="badge bg-success px-3 py-2 fw-bold fs-6">PROMO B</span>
                                        <span class="text-muted small">(Promo Pembanding Baru / Target)</span>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-dark small">
                                            Nama MOU / Promo B: <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white text-success border-end-0"><i class="fas fa-tag"></i></span>
                                            <input type="text"
                                                   name="mou_b"
                                                   id="mouInputB"
                                                   class="form-control border-start-0"
                                                   list="mouDatalistB"
                                                   placeholder="Contoh: Promo Kemerdekaan 2025..."
                                                   value="{{ $mouB }}"
                                                   autocomplete="off"
                                                   required>
                                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown"></button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="max-height: 250px; overflow-y: auto;">
                                                @if(isset($mouOptions))
                                                    @foreach($mouOptions as $opt)
                                                        <li><a class="dropdown-item small" href="javascript:void(0)" onclick="selectMouField('mouInputB', '{{ addslashes($opt) }}')">{{ $opt }}</a></li>
                                                    @endforeach
                                                @endif
                                            </ul>
                                            <datalist id="mouDatalistB">
                                                @if(isset($mouOptions))
                                                    @foreach($mouOptions as $opt)
                                                        <option value="{{ $opt }}">
                                                    @endforeach
                                                @endif
                                            </datalist>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">Periode Promo B (Opsional):</label>
                                        <div class="input-group">
                                            <input type="date" name="tgl_promo_mulai_b" class="form-control" value="{{ $tglPromoMulaiB }}" placeholder="Mulai">
                                            <span class="input-group-text bg-white">s/d</span>
                                            <input type="date" name="tgl_promo_selesai_b" class="form-control" value="{{ $tglPromoSelesaiB }}" placeholder="Selesai">
                                        </div>
                                    </div>

                                    <div class="mb-0">
                                        <label class="form-label fw-semibold small">Periode Evaluasi Datang Kembali B (Opsional):</label>
                                        <div class="input-group">
                                            <input type="date" name="tgl_evaluasi_mulai_b" class="form-control" value="{{ $tglEvaluasiMulaiB }}">
                                            <span class="input-group-text bg-white">s/d</span>
                                            <input type="date" name="tgl_evaluasi_selesai_b" class="form-control" value="{{ $tglEvaluasiSelesaiB }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Filter Tambahan Cabang & Jenis Pelanggan --}}
                        <div class="row g-3 mt-2 pt-3 border-top">
                            <div class="col-md-4">
                                <label for="compare_cabang_id" class="form-label fw-semibold small">Filter Cabang:</label>
                                <select name="cabang_id" id="compare_cabang_id" class="form-select">
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
                            <div class="col-md-4">
                                <label for="compare_jenis_pelanggan" class="form-label fw-semibold small">Jenis Pelanggan:</label>
                                <select name="jenis_pelanggan" id="compare_jenis_pelanggan" class="form-select">
                                    <option value="semua" {{ $jenisPelanggan === 'semua' ? 'selected' : '' }}>Semua Jenis Pelanggan</option>
                                    <option value="baru" {{ $jenisPelanggan === 'baru' ? 'selected' : '' }}>Pelanggan Baru Saja</option>
                                    <option value="lama" {{ $jenisPelanggan === 'lama' ? 'selected' : '' }}>Pelanggan Lama Saja</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="d-flex gap-2 w-100">
                                    <button type="submit" class="btn btn-primary px-4 flex-fill">
                                        <i class="fas fa-balance-scale me-1"></i> Bandingkan Promo
                                    </button>
                                    <a href="{{ route('evaluasi-promo.index', ['tab' => 'compare']) }}" class="btn btn-outline-secondary px-3">
                                        <i class="fas fa-undo"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- HASIL KOMPARASI --}}
            @if(isset($compareData))
            @php
                $a = $compareData['promo_a'];
                $b = $compareData['promo_b'];

                // Helper diff calculation
                $calcDiff = function($valA, $valB) {
                    $diff = $valB - $valA;
                    $pct = $valA > 0 ? round(($diff / $valA) * 100, 1) : ($valB > 0 ? 100 : 0);
                    return ['diff' => $diff, 'pct' => $pct];
                };

                $diffOmset = $calcDiff($a['omset'], $b['omset']);
                $diffPeserta = $calcDiff($a['total_peserta_promo'], $b['total_peserta_promo']);
                $diffBaru = $calcDiff($a['total_peserta_baru'], $b['total_peserta_baru']);
                $diffLama = $calcDiff($a['total_peserta_lama'], $b['total_peserta_lama']);
                $diffRepeat = $calcDiff($a['total_repeat_visitor'], $b['total_repeat_visitor']);
                $diffRetention = [
                    'diff' => round($b['retention_rate'] - $a['retention_rate'], 1),
                    'pct' => $a['retention_rate'] > 0 ? round((($b['retention_rate'] - $a['retention_rate']) / $a['retention_rate']) * 100, 1) : 0,
                ];
            @endphp

            {{-- KPI Summary Cards Komparasi --}}
            <div class="row g-3 mb-4">
                {{-- Omset --}}
                <div class="col-md-4 col-sm-6">
                    <div class="card eval-card-kpi p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="text-muted small fw-semibold">Total Nilai Transaksi Promo</span>
                            @if($diffOmset['diff'] >= 0)
                                <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                    <i class="fas fa-arrow-up me-1"></i>+{{ $diffOmset['pct'] }}%
                                </span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">
                                    <i class="fas fa-arrow-down me-1"></i>{{ $diffOmset['pct'] }}%
                                </span>
                            @endif
                        </div>
                        <div class="d-flex justify-content-between align-items-baseline">
                            <div>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Promo A</small>
                                <span class="fw-bold text-dark fs-6">Rp {{ number_format($a['omset'], 0, ',', '.') }}</span>
                            </div>
                            <i class="fas fa-arrow-right text-muted mx-2"></i>
                            <div>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Promo B</small>
                                <span class="fw-bold text-primary fs-6">Rp {{ number_format($b['omset'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                        <small class="text-muted mt-2 d-block pt-2 border-top" style="font-size: 0.75rem;">
                            Selisih: <strong>{{ $diffOmset['diff'] >= 0 ? '+' : '' }}Rp {{ number_format($diffOmset['diff'], 0, ',', '.') }}</strong>
                        </small>
                    </div>
                </div>

                {{-- Total Peserta --}}
                <div class="col-md-4 col-sm-6">
                    <div class="card eval-card-kpi p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="text-muted small fw-semibold">Total Peserta Promo</span>
                            @if($diffPeserta['diff'] >= 0)
                                <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                    <i class="fas fa-arrow-up me-1"></i>+{{ $diffPeserta['pct'] }}%
                                </span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">
                                    <i class="fas fa-arrow-down me-1"></i>{{ $diffPeserta['pct'] }}%
                                </span>
                            @endif
                        </div>
                        <div class="d-flex justify-content-between align-items-baseline">
                            <div>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Promo A</small>
                                <span class="fw-bold text-dark fs-5">{{ number_format($a['total_peserta_promo']) }}</span>
                            </div>
                            <i class="fas fa-arrow-right text-muted mx-2"></i>
                            <div>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Promo B</small>
                                <span class="fw-bold text-primary fs-5">{{ number_format($b['total_peserta_promo']) }}</span>
                            </div>
                        </div>
                        <small class="text-muted mt-2 d-block pt-2 border-top" style="font-size: 0.75rem;">
                            Selisih: <strong>{{ $diffPeserta['diff'] >= 0 ? '+' : '' }}{{ number_format($diffPeserta['diff']) }} Pasien</strong>
                        </small>
                    </div>
                </div>

                {{-- Retention / Efektivitas Rate --}}
                <div class="col-md-4 col-sm-6">
                    <div class="card eval-card-kpi p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="text-muted small fw-semibold">Tingkat Efektivitas (Repeat Rate)</span>
                            @if($diffRetention['diff'] >= 0)
                                <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                    <i class="fas fa-arrow-up me-1"></i>+{{ $diffRetention['diff'] }}% pts
                                </span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger">
                                    <i class="fas fa-arrow-down me-1"></i>{{ $diffRetention['diff'] }}% pts
                                </span>
                            @endif
                        </div>
                        <div class="d-flex justify-content-between align-items-baseline">
                            <div>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Promo A</small>
                                <span class="fw-bold text-dark fs-5">{{ $a['retention_rate'] }}%</span>
                            </div>
                            <i class="fas fa-arrow-right text-muted mx-2"></i>
                            <div>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Promo B</small>
                                <span class="fw-bold text-primary fs-5">{{ $b['retention_rate'] }}%</span>
                            </div>
                        </div>
                        <small class="text-muted mt-2 d-block pt-2 border-top" style="font-size: 0.75rem;">
                            Repeat: A ({{ $a['total_repeat_visitor'] }}) vs B ({{ $b['total_repeat_visitor'] }})
                        </small>
                    </div>
                </div>
            </div>

            {{-- Tabel Komparasi Lengkap --}}
            <div class="card shadow-sm border-0 rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0 fw-bold text-dark">
                        <i class="fas fa-table text-primary me-2"></i>Tabel Detail Komparasi Promo
                    </h6>
                    <span class="text-muted small">
                        <strong>{{ $a['mou'] }}</strong> (A) vs <strong>{{ $b['mou'] }}</strong> (B)
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-3 px-4">Indikator Metrik</th>
                                    <th class="py-3 text-center bg-primary bg-opacity-10 text-primary">Promo A ({{ $a['mou'] }})</th>
                                    <th class="py-3 text-center bg-success bg-opacity-10 text-success">Promo B ({{ $b['mou'] }})</th>
                                    <th class="py-3 text-center">Selisih (B - A)</th>
                                    <th class="py-3 text-center">Status Pertumbuhan</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Baris Nilai Transaksi --}}
                                <tr>
                                    <td class="px-4 fw-semibold text-dark">
                                        <i class="fas fa-money-bill-wave text-success me-2"></i>Total Nilai Transaksi (Rp)
                                    </td>
                                    <td class="text-center fw-bold">Rp {{ number_format($a['omset'], 0, ',', '.') }}</td>
                                    <td class="text-center fw-bold text-primary">Rp {{ number_format($b['omset'], 0, ',', '.') }}</td>
                                    <td class="text-center fw-bold {{ $diffOmset['diff'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $diffOmset['diff'] >= 0 ? '+' : '' }}Rp {{ number_format($diffOmset['diff'], 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        @if($diffOmset['diff'] > 0)
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2">
                                                <i class="fas fa-arrow-up me-1"></i>Peningkatan (+{{ $diffOmset['pct'] }}%)
                                            </span>
                                        @elseif($diffOmset['diff'] < 0)
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-3 py-2">
                                                <i class="fas fa-arrow-down me-1"></i>Penurunan ({{ $diffOmset['pct'] }}%)
                                            </span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border px-3 py-2">Sama (0%)</span>
                                        @endif
                                    </td>
                                </tr>

                                {{-- Baris Peserta Promo --}}
                                <tr>
                                    <td class="px-4 fw-semibold text-dark">
                                        <i class="fas fa-users text-primary me-2"></i>Total Pasien Peserta Promo
                                    </td>
                                    <td class="text-center fw-bold">{{ number_format($a['total_peserta_promo']) }}</td>
                                    <td class="text-center fw-bold text-primary">{{ number_format($b['total_peserta_promo']) }}</td>
                                    <td class="text-center fw-bold {{ $diffPeserta['diff'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $diffPeserta['diff'] >= 0 ? '+' : '' }}{{ number_format($diffPeserta['diff']) }}
                                    </td>
                                    <td class="text-center">
                                        @if($diffPeserta['diff'] > 0)
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2">
                                                <i class="fas fa-arrow-up me-1"></i>Peningkatan (+{{ $diffPeserta['pct'] }}%)
                                            </span>
                                        @elseif($diffPeserta['diff'] < 0)
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-3 py-2">
                                                <i class="fas fa-arrow-down me-1"></i>Penurunan ({{ $diffPeserta['pct'] }}%)
                                            </span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border px-3 py-2">Sama (0%)</span>
                                        @endif
                                    </td>
                                </tr>

                                {{-- Pasien Baru --}}
                                <tr>
                                    <td class="px-4 fw-semibold text-dark">
                                        <i class="fas fa-user-plus text-info me-2"></i>Peserta Pasien Baru
                                    </td>
                                    <td class="text-center">{{ number_format($a['total_peserta_baru']) }}</td>
                                    <td class="text-center fw-semibold text-primary">{{ number_format($b['total_peserta_baru']) }}</td>
                                    <td class="text-center fw-bold {{ $diffBaru['diff'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $diffBaru['diff'] >= 0 ? '+' : '' }}{{ number_format($diffBaru['diff']) }}
                                    </td>
                                    <td class="text-center">
                                        @if($diffBaru['diff'] > 0)
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1">
                                                <i class="fas fa-arrow-up me-1"></i>+{{ $diffBaru['pct'] }}%
                                            </span>
                                        @elseif($diffBaru['diff'] < 0)
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 py-1">
                                                <i class="fas fa-arrow-down me-1"></i>{{ $diffBaru['pct'] }}%
                                            </span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1">0%</span>
                                        @endif
                                    </td>
                                </tr>

                                {{-- Pasien Lama --}}
                                <tr>
                                    <td class="px-4 fw-semibold text-dark">
                                        <i class="fas fa-user-check text-secondary me-2"></i>Peserta Pasien Lama
                                    </td>
                                    <td class="text-center">{{ number_format($a['total_peserta_lama']) }}</td>
                                    <td class="text-center fw-semibold text-primary">{{ number_format($b['total_peserta_lama']) }}</td>
                                    <td class="text-center fw-bold {{ $diffLama['diff'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $diffLama['diff'] >= 0 ? '+' : '' }}{{ number_format($diffLama['diff']) }}
                                    </td>
                                    <td class="text-center">
                                        @if($diffLama['diff'] > 0)
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1">
                                                <i class="fas fa-arrow-up me-1"></i>+{{ $diffLama['pct'] }}%
                                            </span>
                                        @elseif($diffLama['diff'] < 0)
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 py-1">
                                                <i class="fas fa-arrow-down me-1"></i>{{ $diffLama['pct'] }}%
                                            </span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1">0%</span>
                                        @endif
                                    </td>
                                </tr>

                                {{-- Repeat Visitors --}}
                                <tr>
                                    <td class="px-4 fw-semibold text-dark">
                                        <i class="fas fa-redo text-warning me-2"></i>Pasien Datang Kembali (Repeat Visitor)
                                    </td>
                                    <td class="text-center">{{ number_format($a['total_repeat_visitor']) }}</td>
                                    <td class="text-center fw-semibold text-primary">{{ number_format($b['total_repeat_visitor']) }}</td>
                                    <td class="text-center fw-bold {{ $diffRepeat['diff'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $diffRepeat['diff'] >= 0 ? '+' : '' }}{{ number_format($diffRepeat['diff']) }}
                                    </td>
                                    <td class="text-center">
                                        @if($diffRepeat['diff'] > 0)
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1">
                                                <i class="fas fa-arrow-up me-1"></i>+{{ $diffRepeat['pct'] }}%
                                            </span>
                                        @elseif($diffRepeat['diff'] < 0)
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 py-1">
                                                <i class="fas fa-arrow-down me-1"></i>{{ $diffRepeat['pct'] }}%
                                            </span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1">0%</span>
                                        @endif
                                    </td>
                                </tr>

                                {{-- Tingkat Efektivitas / Retention --}}
                                <tr>
                                    <td class="px-4 fw-semibold text-dark">
                                        <i class="fas fa-percentage text-danger me-2"></i>Tingkat Efektivitas (Retention Rate)
                                    </td>
                                    <td class="text-center fw-bold">{{ $a['retention_rate'] }}%</td>
                                    <td class="text-center fw-bold text-primary">{{ $b['retention_rate'] }}%</td>
                                    <td class="text-center fw-bold {{ $diffRetention['diff'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $diffRetention['diff'] >= 0 ? '+' : '' }}{{ $diffRetention['diff'] }}% pts
                                    </td>
                                    <td class="text-center">
                                        @if($diffRetention['diff'] > 0)
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-2">
                                                <i class="fas fa-arrow-up me-1"></i>Peningkatan (+{{ $diffRetention['diff'] }}%)
                                            </span>
                                        @elseif($diffRetention['diff'] < 0)
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-3 py-2">
                                                <i class="fas fa-arrow-down me-1"></i>Penurunan ({{ $diffRetention['diff'] }}%)
                                            </span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border px-3 py-2">Sama (0%)</span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @elseif(($activeTab ?? 'single') === 'compare')
            <div class="card shadow-sm border-0 rounded-3 p-5 text-center my-4">
                <div class="py-4">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                        <i class="fas fa-balance-scale fa-2x"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Siap Membandingkan Promo</h5>
                    <p class="text-muted col-md-6 mx-auto mb-3">
                        Pilih <strong>MOU / Promo A</strong> dan <strong>MOU / Promo B</strong> di atas, lalu klik tombol <strong>Bandingkan Promo</strong> untuk melihat analisis peningkatan atau penurunan kinerja.
                    </p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script id="mouOptionsData" type="application/json">{!! json_encode(isset($mouOptions) ? $mouOptions->values()->toArray() : []) !!}</script>
<script>
const allMouOptions = JSON.parse(document.getElementById('mouOptionsData')?.textContent || '[]');

function selectMouField(fieldId, val) {
    const input = document.getElementById(fieldId);
    if (!input) return;
    input.value = val;
    input.focus();
}

function selectMou(val) {
    const input = document.getElementById('mouInput');
    if (!input) return;
    const current = input.value;
    const lastComma = current.lastIndexOf(',');
    const lastPlus  = current.lastIndexOf('+');
    const lastDelimIdx = Math.max(lastComma, lastPlus);

    if (lastDelimIdx === -1) {
        if (!current.trim()) {
            input.value = val;
        } else {
            // Jika sudah ada teks, tanya atau ganti langsung
            input.value = val;
        }
    } else {
        const delim = current[lastDelimIdx];
        const beforeDelim = current.substring(0, lastDelimIdx).trim();
        input.value = (beforeDelim ? beforeDelim + ' ' + delim + ' ' : delim + ' ') + val;
    }
    input.focus();
    updateMouDatalist();
}

function updateMouDatalist() {
    const mouInput = document.getElementById('mouInput');
    const mouDatalist = document.getElementById('mouDatalist');
    const mouDropdownMenu = document.getElementById('mouDropdownMenu');
    if (!mouInput) return;

    const val = mouInput.value;
    const lastComma = val.lastIndexOf(',');
    const lastPlus  = val.lastIndexOf('+');
    const lastDelimIdx = Math.max(lastComma, lastPlus);

    let prefix = '';
    let currentToken = '';

    if (lastDelimIdx !== -1) {
        const delim = val[lastDelimIdx];
        const beforeDelim = val.substring(0, lastDelimIdx).trim();
        prefix = beforeDelim ? (beforeDelim + ' ' + delim + ' ') : (delim + ' ');
        currentToken = val.substring(lastDelimIdx + 1).trim();
    } else {
        currentToken = val.trim();
    }

    const tokenLower = currentToken.toLowerCase();
    const matched = tokenLower 
        ? allMouOptions.filter(function(opt) { return opt.toLowerCase().indexOf(tokenLower) !== -1; })
        : allMouOptions;

    if (mouDatalist) {
        let html = '';
        matched.slice(0, 50).forEach(function(opt) {
            const fullVal = prefix ? (prefix + opt) : opt;
            html += '<option value="' + fullVal.replace(/"/g, '&quot;') + '">';
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
