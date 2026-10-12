@extends('layouts.main')

@section('title', 'Dashboard - Medical Lab CRM')

@section('content')
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="text-primary mb-0 fw-bold">
                @if($isIT)
                    <i class="fas fa-server me-2"></i>Dashboard IT & System Operations
                @else
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard Utama
                @endif
            </h3>
            @if($isIT)
                <small class="text-muted">Pusat monitoring kesehatan sistem, telemetri server, audit log, dan integritas data batch</small>
            @elseif(!$isMultiCabang && $cabangNama)
                <small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i>Cabang: <strong>{{ $cabangNama }}</strong></small>
            @endif
        </div>

        @if($isIT)
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('import-batch.index') }}" class="btn btn-outline-primary btn-sm px-3 shadow-sm">
                <i class="fas fa-undo-alt me-1"></i> Riwayat Import & Rollback
            </a>
            <a href="{{ route('activity-log.index') }}" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                <i class="fas fa-history me-1"></i> Log Aktivitas
            </a>
            <a href="{{ route('users.index') }}" class="btn btn-outline-dark btn-sm px-3 shadow-sm">
                <i class="fas fa-users-cog me-1"></i> User Management
            </a>
        </div>
        @endif
    </div>

    {{-- ========================================================================= --}}
    {{-- ROLE NON-IT: BIRTHDAY REMINDER BANNER (Revisi Poin 7)                     --}}
    {{-- ========================================================================= --}}
    @if(!$isIT && ($todayBirthdayCount ?? 0) > 0)
    <div class="alert alert-warning border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3 py-3 px-4" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-left: 5px solid #f59e0b !important;">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-warning bg-opacity-25 p-3 text-warning d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; font-size: 1.5rem;">
                🎂
            </div>
            <div>
                <h5 class="mb-1 fw-bold text-dark">
                    Pengingat Ulang Tahun Pasien Hari Ini!
                </h5>
                <p class="mb-0 text-secondary small">
                    Terdapat <strong class="text-danger fw-bold fs-6">{{ number_format($todayBirthdayCount) }} pasien</strong> yang berulang tahun pada hari ini ({{ \Carbon\Carbon::now()->isoFormat('D MMMM Y') }}). Berikan apresiasi atau ucapan selamat kepada mereka.
                </p>
            </div>
        </div>
        <div>
            <a href="{{ route('special-day.birthday', ['tgl_mulai' => date('Y-m-d'), 'tgl_akhir' => date('Y-m-d')]) }}" class="btn btn-warning fw-bold px-4 py-2 shadow-sm text-dark d-flex align-items-center gap-2">
                <i class="fas fa-gift"></i>
                <span>Lihat Detail</span>
                <i class="fas fa-arrow-right small"></i>
            </a>
        </div>
    </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- ROLE IT: DEDICATED IT DASHBOARD (Revisi Poin 6)                           --}}
    {{-- ========================================================================= --}}
    @if($isIT && isset($itData))
    {{-- Telemetri Server & Lingkungan Sistem --}}
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0 fw-bold text-dark">
                <i class="fas fa-microchip text-primary me-2"></i>Telemetri Server & Lingkungan Sistem
            </h6>
            <span class="badge bg-success bg-opacity-10 text-success border border-success">
                <i class="fas fa-check-circle me-1"></i>Sistem Berjalan Normal
            </span>
        </div>
        <div class="card-body p-3">
            <div class="row g-3 text-center">
                <div class="col-6 col-md-2">
                    <div class="p-2 border rounded-2 bg-light">
                        <small class="text-muted d-block" style="font-size: 0.75rem;">PHP Version</small>
                        <strong class="text-dark">{{ $itData['system']['php_version'] }}</strong>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-2 border rounded-2 bg-light">
                        <small class="text-muted d-block" style="font-size: 0.75rem;">Laravel</small>
                        <strong class="text-dark">v{{ $itData['system']['laravel_version'] }}</strong>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-2 border rounded-2 bg-light">
                        <small class="text-muted d-block" style="font-size: 0.75rem;">Database</small>
                        <strong class="text-dark">{{ $itData['system']['database_driver'] }}</strong>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-2 border rounded-2 bg-light">
                        <small class="text-muted d-block" style="font-size: 0.75rem;">Memory Usage</small>
                        <strong class="text-dark">{{ $itData['system']['memory_usage'] }}</strong>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-2 border rounded-2 bg-light">
                        <small class="text-muted d-block" style="font-size: 0.75rem;">Memory Limit</small>
                        <strong class="text-dark">{{ $itData['system']['memory_limit'] }}</strong>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-2 border rounded-2 bg-light">
                        <small class="text-muted d-block" style="font-size: 0.75rem;">Max Upload</small>
                        <strong class="text-dark">{{ $itData['system']['upload_max_filesize'] }}</strong>
                    </div>
                </div>
            </div>
            <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center text-muted small flex-wrap gap-2">
                <span><i class="fas fa-desktop me-1"></i>OS: <strong>{{ $itData['system']['server_os'] }}</strong> (Env: <code>{{ $itData['system']['environment'] }}</code>)</span>
                <span><i class="fas fa-clock me-1"></i>Server Time: <strong>{{ $itData['system']['server_time'] }}</strong></span>
            </div>
        </div>
    </div>

    {{-- KPI Cards Database & Core Records --}}
    <div class="row g-3 mb-4">
        {{-- Total Pelanggan --}}
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card bg-primary text-white h-100 shadow-sm border-0 rounded-3">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="opacity-75 d-block">Data Pelanggan</small>
                            <h3 class="mb-0 fw-bold">{{ number_format($itData['counts']['total_pelanggan']) }}</h3>
                        </div>
                        <i class="fas fa-user-injured fa-2x opacity-50"></i>
                    </div>
                    <div class="mt-2 pt-2 border-top border-white border-opacity-25">
                        <a href="{{ route('pelanggan.index') }}" class="text-white text-decoration-none small opacity-75">
                            <i class="fas fa-database me-1"></i>Lihat Tabel Master &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Kunjungan --}}
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card bg-info text-white h-100 shadow-sm border-0 rounded-3">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="opacity-75 d-block">Total Kunjungan</small>
                            <h3 class="mb-0 fw-bold">{{ number_format($itData['counts']['total_kunjungan']) }}</h3>
                        </div>
                        <i class="fas fa-hospital-user fa-2x opacity-50"></i>
                    </div>
                    <div class="mt-2 pt-2 border-top border-white border-opacity-25 small opacity-75">
                        <i class="fas fa-chart-line me-1"></i>Semua riwayat transaksi
                    </div>
                </div>
            </div>
        </div>

        {{-- Log Aktivitas 24h & Total --}}
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card bg-warning text-dark h-100 shadow-sm border-0 rounded-3">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block">Audit Log (24 Jam)</small>
                            <h3 class="mb-0 fw-bold">{{ number_format($itData['counts']['logs_24h']) }}</h3>
                        </div>
                        <i class="fas fa-shield-alt fa-2x text-warning opacity-75"></i>
                    </div>
                    <div class="mt-2 pt-2 border-top border-dark border-opacity-10">
                        <a href="{{ route('activity-log.index') }}" class="text-dark text-decoration-none small">
                            <i class="fas fa-history me-1"></i>Total {{ number_format($itData['counts']['total_activity_logs']) }} log audit &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sesi Batch Import --}}
        <div class="col-12 col-sm-6 col-md-3">
            <div class="card bg-dark text-white h-100 shadow-sm border-0 rounded-3">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="opacity-75 d-block">Sesi Import Data</small>
                            <h3 class="mb-0 fw-bold">{{ number_format($itData['counts']['total_import_batches']) }}</h3>
                        </div>
                        <i class="fas fa-file-import fa-2x opacity-50"></i>
                    </div>
                    <div class="mt-2 pt-2 border-top border-white border-opacity-25">
                        <span class="small opacity-75">
                            {{ $itData['counts']['completed_batches'] }} OK | {{ $itData['counts']['rolled_back_batches'] }} Rolled Back
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Live Monitoring: Import Batches & Activity Log Trail --}}
    <div class="row g-4 mb-4">
        {{-- Sesi Import Terakhir --}}
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0 fw-bold text-dark">
                        <i class="fas fa-file-csv text-primary me-2"></i>Monitoring Import Batch Terkini
                    </h6>
                    <a href="{{ route('import-batch.index') }}" class="btn btn-sm btn-outline-primary py-0 px-2 small">
                        Semua Sesi
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Filename</th>
                                    <th>Cabang</th>
                                    <th class="text-center">Baris</th>
                                    <th class="text-center">Status</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($itData['recent_imports'] as $batch)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-truncate" style="max-width: 140px;" title="{{ $batch->filename }}">
                                            {{ $batch->filename }}
                                        </div>
                                        <small class="text-muted" style="font-size: 0.7rem;">{{ $batch->user?->name ?? 'System' }}</small>
                                    </td>
                                    <td>{{ $batch->cabang?->nama ?? '-' }}</td>
                                    <td class="text-center fw-bold text-primary">{{ number_format($batch->total_rows) }}</td>
                                    <td class="text-center">
                                        @if($batch->status === 'completed')
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success">Completed</span>
                                        @else
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">Rolled Back</span>
                                        @endif
                                    </td>
                                    <td class="text-muted" style="font-size: 0.75rem;">{{ $batch->imported_at?->diffForHumans() }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat import</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Log Aktivitas Terkini (Audit Trail) --}}
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-3 h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0 fw-bold text-dark">
                        <i class="fas fa-shield-alt text-primary me-2"></i>Audit Trail Aktivitas Sistem
                    </h6>
                    <a href="{{ route('activity-log.index') }}" class="btn btn-sm btn-outline-secondary py-0 px-2 small">
                        Semua Log
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>User</th>
                                    <th class="text-center">Aksi</th>
                                    <th>Modul & Deskripsi</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($itData['recent_logs'] as $log)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $log->username }}</div>
                                        <span class="badge bg-light text-muted border" style="font-size: 0.65rem;">{{ $log->role }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ \App\Models\ActivityLog::actionBadgeClass($log->action) }}">
                                            {{ \App\Models\ActivityLog::actionLabel($log->action) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;" title="{{ $log->description }}">
                                            <strong class="text-secondary">[{{ ucfirst($log->module) }}]</strong> {{ $log->description }}
                                        </div>
                                    </td>
                                    <td class="text-muted" style="font-size: 0.75rem;">{{ $log->created_at?->diffForHumans() }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Belum ada catatan aktivitas</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- STATISTIK KUNJUNGAN & GRAFIK BISNIS (Hanya Untuk Role Non-IT)             --}}
    {{-- ========================================================================= --}}
    @if(!$isIT)
        @if($isMultiCabang)
            {{-- ===== MULTI-CABANG: Tab per cabang ===== --}}
            <div class="card mb-4 shadow-sm border-0 rounded-3">
                <div class="card-header bg-white p-0">
                    <ul class="nav nav-tabs border-0 flex-wrap" id="cabangTabs" role="tablist">
                        @foreach($perCabangStats as $cabId => $cab)
                        @php $isActiveTab = ($activeCabang === $cabId); @endphp
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $isActiveTab ? 'active fw-bold' : '' }} rounded-0"
                                    id="tab-{{ $cabId }}-tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#tab-{{ $cabId }}"
                                    type="button" role="tab">
                                <i class="fas fa-building me-1 opacity-75"></i>{{ $cab['nama'] }}
                            </button>
                        </li>
                        @endforeach
                    </ul>
                </div>
                <div class="card-body p-3">
                    <div class="tab-content" id="cabangTabsContent">
                        @foreach($perCabangStats as $cabId => $cab)
                        <div class="tab-pane fade {{ ($activeCabang === $cabId) ? 'show active' : '' }}"
                             id="tab-{{ $cabId }}" role="tabpanel">

                            {{-- Statistik Cards --}}
                            @php $p = ['cabang_id' => $cabId]; @endphp
                            <div class="row mb-3 g-3">
                                @foreach([
                                    ['bg'=>'bg-primary','icon'=>'fa-users','label'=>'Total Pelanggan','value'=>$cab['totalPelanggan'],'type'=>'total'],
                                    ['bg'=>'bg-secondary','icon'=>'fa-calendar-minus','label'=>'Kunjungan Bulan Kemarin','value'=>$cab['totalKunjunganBulanKemarin'],'type'=>'kunjungan_bulan_kemarin'],
                                    ['bg'=>'bg-info','icon'=>'fa-chart-line','label'=>'Kunjungan Tahun Ini','value'=>$cab['totalKunjunganTahunIni'],'type'=>'kunjungan_tahun_ini'],
                                    ['bg'=>'bg-warning','icon'=>'fa-user-plus','label'=>'Pelanggan Baru Bulan Kemarin','value'=>$cab['pelangganBaruBulanKemarin'],'type'=>'pelanggan_baru_bulan_kemarin'],
                                ] as $card)
                                <div class="col-12 col-sm-6 col-md-3">
                                    <div class="card {{ $card['bg'] }} text-white h-100 shadow-sm border-0 rounded-3">
                                        <div class="card-body d-flex flex-column">
                                            <div class="d-flex justify-content-between align-items-center flex-grow-1">
                                                <div>
                                                    <h6 class="mb-0 opacity-75">{{ $card['label'] }}</h6>
                                                    <h3 class="mb-0 fw-bold">{{ number_format($card['value']) }}</h3>
                                                </div>
                                                <i class="fas {{ $card['icon'] }} fa-2x opacity-50"></i>
                                            </div>
                                            <div class="mt-2 pt-1 border-top border-white border-opacity-25">
                                                <a href="{{ route('dashboard.detail', array_merge($p, ['type'=>$card['type']])) }}" class="text-white text-decoration-none small opacity-75"><i class="fas fa-eye me-1"></i>Lihat Detail</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            {{-- Statistik Per Klasifikasi --}}
                            <div class="row g-3">
                                @foreach([
                                    ['bg'=>'bg-danger','icon'=>'fa-crown','label'=>'Total Pelanggan Prioritas','value'=>$cab['totalPelangganPrioritas'],'type'=>'prioritas'],
                                    ['bg'=>'bg-success','icon'=>'fa-heart','label'=>'Total Pelanggan Loyal','value'=>$cab['totalPelangganLoyal'],'type'=>'loyal'],
                                    ['bg'=>'bg-warning','icon'=>'fa-star','label'=>'Total Pelanggan Potensial','value'=>$cab['totalPelangganPotensial'],'type'=>'potensial'],
                                    ['bg'=>'bg-secondary','icon'=>'fa-user','label'=>'Total Pelanggan Umum','value'=>$cab['totalPelangganUmum'],'type'=>'umum'],
                                ] as $card)
                                <div class="col-12 col-sm-6 col-md-3">
                                    <div class="card {{ $card['bg'] }} text-white h-100 shadow-sm border-0 rounded-3">
                                        <div class="card-body d-flex flex-column">
                                            <div class="d-flex justify-content-between align-items-center flex-grow-1">
                                                <div>
                                                    <h6 class="mb-0 opacity-75">{{ $card['label'] }}</h6>
                                                    <h3 class="mb-0 fw-bold">{{ number_format($card['value']) }}</h3>
                                                </div>
                                                <i class="fas {{ $card['icon'] }} fa-2x opacity-50"></i>
                                            </div>
                                            <div class="mt-2 pt-1 border-top border-white border-opacity-25">
                                                <a href="{{ route('dashboard.detail', array_merge($p, ['type'=>$card['type']])) }}" class="text-white text-decoration-none small opacity-75"><i class="fas fa-eye me-1"></i>Lihat Detail</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                        </div>{{-- end tab-pane --}}
                        @endforeach
                    </div>
                </div>
            </div>

        @else
            {{-- ===== SINGLE CABANG: Layout langsung tanpa tab ===== --}}
            @php $sp = ['cabang_id' => $singleCabangId]; @endphp
            <div class="row mb-4 g-3">
                @foreach([
                    ['bg'=>'bg-primary','icon'=>'fa-users','label'=>'Total Pelanggan','value'=>$totalPelanggan,'type'=>'total'],
                    ['bg'=>'bg-secondary','icon'=>'fa-calendar-minus','label'=>'Kunjungan Bulan Kemarin','value'=>$totalKunjunganBulanKemarin,'type'=>'kunjungan_bulan_kemarin'],
                    ['bg'=>'bg-info','icon'=>'fa-chart-line','label'=>'Kunjungan Tahun Ini','value'=>$totalKunjunganTahunIni,'type'=>'kunjungan_tahun_ini'],
                    ['bg'=>'bg-warning','icon'=>'fa-user-plus','label'=>'Pelanggan Baru Bulan Kemarin','value'=>$pelangganBaruBulanKemarin,'type'=>'pelanggan_baru_bulan_kemarin'],
                ] as $card)
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="card {{ $card['bg'] }} text-white h-100 shadow-sm border-0 rounded-3">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center flex-grow-1">
                                <div>
                                    <h6 class="mb-0 opacity-75">{{ $card['label'] }}</h6>
                                    <h3 class="mb-0 fw-bold">{{ number_format($card['value']) }}</h3>
                                </div>
                                <i class="fas {{ $card['icon'] }} fa-2x opacity-50"></i>
                            </div>
                            <div class="mt-2 pt-1 border-top border-white border-opacity-25">
                                <a href="{{ route('dashboard.detail', array_merge($sp, ['type'=>$card['type']])) }}" class="text-white text-decoration-none small opacity-75"><i class="fas fa-eye me-1"></i>Lihat Detail</a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="row mb-4 g-3">
                @foreach([
                    ['bg'=>'bg-danger','icon'=>'fa-crown','label'=>'Total Pelanggan Prioritas','value'=>$totalPelangganPrioritas,'type'=>'prioritas'],
                    ['bg'=>'bg-success','icon'=>'fa-heart','label'=>'Total Pelanggan Loyal','value'=>$totalPelangganLoyal,'type'=>'loyal'],
                    ['bg'=>'bg-warning','icon'=>'fa-star','label'=>'Total Pelanggan Potensial','value'=>$totalPelangganPotensial,'type'=>'potensial'],
                    ['bg'=>'bg-secondary','icon'=>'fa-user','label'=>'Total Pelanggan Umum','value'=>$totalPelangganUmum,'type'=>'umum'],
                ] as $card)
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="card {{ $card['bg'] }} text-white h-100 shadow-sm border-0 rounded-3">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center flex-grow-1">
                                <div>
                                    <h6 class="mb-0 opacity-75">{{ $card['label'] }}</h6>
                                    <h3 class="mb-0 fw-bold">{{ number_format($card['value']) }}</h3>
                                </div>
                                <i class="fas {{ $card['icon'] }} fa-2x opacity-50"></i>
                            </div>
                            <div class="mt-2 pt-1 border-top border-white border-opacity-25">
                                <a href="{{ route('dashboard.detail', array_merge($sp, ['type'=>$card['type']])) }}" class="text-white text-decoration-none small opacity-75"><i class="fas fa-eye me-1"></i>Lihat Detail</a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif

        <!-- Filter Grafik -->
        <div class="card mb-4 shadow-sm border-0 rounded-3">
            <div class="card-body py-3">
                <form method="GET" action="{{ route('dashboard') }}" class="row g-3 align-items-end">
                    @if($isMultiCabang)
                        <input type="hidden" name="active_cab" value="{{ $activeCabang }}">
                    @endif
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">Tipe Filter Grafik</label>
                        <select name="filter_type" class="form-select form-select-sm" id="filterTypeSelect">
                            <option value="monthly" {{ $filterType == 'monthly' ? 'selected' : '' }}>Pertumbuhan per Bulan</option>
                            <option value="yearly" {{ $filterType == 'yearly' ? 'selected' : '' }}>Pertumbuhan per Tahun</option>
                            <option value="class" {{ $filterType == 'class' ? 'selected' : '' }}>Per Klasifikasi Pelanggan</option>
                        </select>
                    </div>

                    <div class="col-md-3" id="yearFilter">
                        <label class="form-label fw-semibold small">Tahun</label>
                        <select name="year" class="form-select form-select-sm">
                            @for($y = date('Y'); $y >= date('Y') - 4; $y--)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="fas fa-sync-alt me-1"></i> Refresh Grafik
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Grafik -->
        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-chart-bar text-primary me-2"></i> {{ $chartTitle }}</h5>
            </div>
            <div class="card-body">
                <canvas id="growthChart" height="220"></canvas>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
@if(!$isIT)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Klik tab → reload halaman dengan active_cab baru agar chart ikut terupdate
    document.querySelectorAll('#cabangTabs button[data-bs-toggle="tab"]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var cabId = e.currentTarget.getAttribute('data-bs-target').replace('#tab-', '');
            var url   = new URL(window.location.href);
            url.searchParams.set('active_cab', cabId);
            window.location.href = url.toString();
        });
    });
</script>
<script id="growthChartLabels" type="application/json">{!! json_encode($chartLabels) !!}</script>
<script id="growthChartData" type="application/json">{!! json_encode($chartData) !!}</script>
<script id="growthFilterType" type="application/json">{!! json_encode($filterType) !!}</script>
<script>
    const chartElem = document.getElementById('growthChart');
    if (chartElem) {
        const ctx = chartElem.getContext('2d');

        // Data dari PHP
        const labels = JSON.parse(document.getElementById('growthChartLabels').textContent || '[]');
        const data = JSON.parse(document.getElementById('growthChartData').textContent || '[]');
        const filterType = JSON.parse(document.getElementById('growthFilterType').textContent || '""');

        // Warna berdasarkan tipe filter
        let backgroundColors, borderColors;

        if (filterType === 'class') {
            backgroundColors = [
                'rgba(220, 53, 69, 0.85)',   // Prioritas - merah
                'rgba(40, 167, 69, 0.85)',   // Loyal - hijau
                'rgba(255, 193, 7, 0.85)',   // Potensial - kuning
                'rgba(108, 117, 125, 0.85)'  // Umum - abu-abu
            ];
            borderColors = [
                'rgba(220, 53, 69, 1)',
                'rgba(40, 167, 69, 1)',
                'rgba(255, 193, 7, 1)',
                'rgba(108, 117, 125, 1)'
            ];
        } else {
            backgroundColors = 'rgba(13, 110, 253, 0.65)';
            borderColors = 'rgba(13, 110, 253, 1)';
        }

        new Chart(ctx, {
            type: filterType === 'class' ? 'pie' : 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Pasien',
                    data: data,
                    backgroundColor: backgroundColors,
                    borderColor: borderColors,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: filterType === 'class',
                        position: 'bottom'
                    }
                },
                scales: filterType === 'class' ? {} : {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
</script>
@endif
@endsection
