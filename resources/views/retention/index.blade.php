@extends('layouts.main')

@section('title', 'Retention Customer - Medical Lab CRM')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="text-primary mb-0 fw-semibold"><i class="fas fa-recycle me-2"></i>Dashboard Retention Customer</h3>
</div>

{{-- ============ FILTER ============ --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-semibold text-secondary"><i class="fas fa-filter me-2"></i>Filter</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('retention.index') }}" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Periode</label>
                <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Per Bulan</option>
                    <option value="yearly"  {{ $period === 'yearly'  ? 'selected' : '' }}>Per Tahun</option>
                </select>
            </div>
            @if($period === 'monthly')
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Bulan</label>
                <select name="month" class="form-select form-select-sm">
                    @foreach(range(1,12) as $m)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Tahun</label>
                <select name="year" class="form-select form-select-sm">
                    @for($y = date('Y') - 5; $y <= date('Y'); $y++)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            @if($cabangs->count() > 1)
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Cabang</label>
                <select name="cabang_id" class="form-select form-select-sm">
                    <option value="">Semua Cabang</option>
                    @foreach($cabangs as $c)
                    <option value="{{ $c->id }}" {{ $cabangId == $c->id ? 'selected' : '' }}>{{ $c->nama }}</option>
                    @endforeach
                </select>
            </div>
            @else
                <input type="hidden" name="cabang_id" value="{{ $cabangId }}">
            @endif
            @if($statusFilter)
            <input type="hidden" name="status" value="{{ $statusFilter }}">
            @endif
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="fas fa-sync-alt me-1"></i>Tampilkan
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ============ RETENTION RATE ============ --}}
<div class="row g-3 mb-4">
    {{-- Retention Rate Card --}}
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body text-center py-4">
                <div class="mb-2">
                    <i class="fas fa-percentage fa-2x text-primary opacity-75"></i>
                </div>
                <h6 class="text-muted fw-semibold text-uppercase mb-1" style="font-size:.75rem; letter-spacing:.05em">Retention Rate</h6>
                <div class="display-5 fw-bold {{ is_null($retentionRate) ? 'text-muted' : ($retentionRate >= 70 ? 'text-success' : ($retentionRate >= 40 ? 'text-warning' : 'text-danger')) }}">
                    @if(is_null($retentionRate))
                        <span class="text-muted" style="font-size:1.5rem">Belum ada data</span>
                    @else
                        {{ $retentionRate }}%
                    @endif
                </div>
                <p class="text-muted small mt-2 mb-0">
                    Periode: <strong>{{ $period === 'monthly' ? \Carbon\Carbon::create($year, $month, 1)->format('F Y') : "Tahun $year" }}</strong>
                </p>
            </div>
        </div>
    </div>

    {{-- Breakdown Cards --}}
    <div class="col-md-8">
        <div class="row g-3 h-100">
            <div class="col-6 col-md-4">
                <div class="card shadow-sm border-0 h-100 text-center py-3">
                    <div class="card-body p-2">
                        <p class="text-muted small mb-1 fw-semibold" style="font-size:.72rem">Pelanggan Awal</p>
                        <h4 class="fw-bold text-primary mb-0">{{ number_format($pelangganAwal) }}</h4>
                        <p class="text-muted mb-0" style="font-size:.7rem">pernah datang sebelum periode</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card shadow-sm border-0 h-100 text-center py-3">
                    <div class="card-body p-2">
                        <p class="text-muted small mb-1 fw-semibold" style="font-size:.72rem">Pelanggan Baru</p>
                        <h4 class="fw-bold text-info mb-0">{{ number_format($pelangganBaru) }}</h4>
                        <p class="text-muted mb-0" style="font-size:.7rem">pertama kali di periode ini</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card shadow-sm border-0 h-100 text-center py-3">
                    <div class="card-body p-2">
                        <p class="text-muted small mb-1 fw-semibold" style="font-size:.72rem">Retained Customer</p>
                        <h4 class="fw-bold text-success mb-0">{{ number_format($pelangganRetained) }}</h4>
                        <p class="text-muted mb-0" style="font-size:.7rem">pelanggan lama yang kembali</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============ FORMULA TOOLTIP ============ --}}
<div class="alert alert-light border small mb-4 py-2">
    <i class="fas fa-info-circle text-primary me-1"></i>
    <strong>Formula:</strong>
    Retained Customer &divide; Pelanggan Awal &times; 100%
    &nbsp;=&nbsp; {{ number_format($pelangganRetained) }} &divide; {{ $pelangganAwal > 0 ? number_format($pelangganAwal) : '?' }} &times; 100%
    @if($pelangganAwal > 0)
    &nbsp;=&nbsp; <strong>{{ $retentionRate }}%</strong>
    @endif
</div>

{{-- ============ B. SMART INSIGHTS ============ --}}
@if($isAdminOrAbove && count($smartInsights) > 0)
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-semibold"><i class="fas fa-lightbulb me-2 text-warning"></i>Smart Insight Otomatis</h6>
    </div>
    <div class="card-body py-3">
        <div class="row g-2">
            @foreach($smartInsights as $ins)
            <div class="col-12 col-md-6">
                <div class="alert alert-{{ $ins['type'] }} py-2 px-3 mb-0 d-flex align-items-start gap-2 border">
                    <i class="fas fa-{{ $ins['icon'] }} mt-1 flex-shrink-0"></i>
                    <span class="small">{{ $ins['text'] }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ============ A. ANALISIS CABANG (Direktur only) ============ --}}
@if($isDirektur && $analisisCabang && count($analisisCabang) > 1)
@php
    $best_ret  = collect($analisisCabang)->sortByDesc('retRate')->first();
    $most_baru = collect($analisisCabang)->sortByDesc('baru')->first();
    $most_lost = collect($analisisCabang)->sortByDesc('lost')->first();
    $best_grow = collect($analisisCabang)->sortByDesc('growth')->first();
@endphp
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="fas fa-trophy me-2 text-warning"></i>Analisis Cabang <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem">Direktur</span></h6>
    </div>
    <div class="card-body">
        {{-- Leaderboard ranking --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-success border-opacity-50 h-100">
                    <div class="card-body text-center py-3">
                        <i class="fas fa-medal fa-lg text-success mb-1"></i>
                        <p class="text-muted mb-0" style="font-size:.7rem">Retention Terbaik</p>
                        <p class="fw-bold text-success mb-0 small">{{ $best_ret['nama'] }}</p>
                        <p class="fw-bold mb-0">{{ $best_ret['retRate'] ?? 'N/A' }}%</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-info border-opacity-50 h-100">
                    <div class="card-body text-center py-3">
                        <i class="fas fa-user-plus fa-lg text-info mb-1"></i>
                        <p class="text-muted mb-0" style="font-size:.7rem">Pelanggan Baru Terbanyak</p>
                        <p class="fw-bold text-info mb-0 small">{{ $most_baru['nama'] }}</p>
                        <p class="fw-bold mb-0">{{ number_format($most_baru['baru']) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-danger border-opacity-50 h-100">
                    <div class="card-body text-center py-3">
                        <i class="fas fa-user-slash fa-lg text-danger mb-1"></i>
                        <p class="text-muted mb-0" style="font-size:.7rem">Pelanggan Hilang Terbanyak</p>
                        <p class="fw-bold text-danger mb-0 small">{{ $most_lost['nama'] }}</p>
                        <p class="fw-bold mb-0">{{ number_format($most_lost['lost']) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-primary border-opacity-50 h-100">
                    <div class="card-body text-center py-3">
                        <i class="fas fa-chart-line fa-lg text-primary mb-1"></i>
                        <p class="text-muted mb-0" style="font-size:.7rem">Growth Tertinggi</p>
                        <p class="fw-bold text-primary mb-0 small">{{ $best_grow['nama'] }}</p>
                        <p class="fw-bold mb-0">{{ $best_grow['growth'] !== null ? $best_grow['growth'].'%' : 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabel perbandingan cabang --}}
        <div class="table-responsive mb-4">
            <table class="table table-hover table-bordered align-middle mb-0" style="font-size:.85rem">
                <thead class="table-light">
                    <tr>
                        <th class="px-3">Cabang</th>
                        <th class="text-center">Pelanggan Awal</th>
                        <th class="text-center">Pelanggan Baru</th>
                        <th class="text-center">Retained</th>
                        <th class="text-center">Lost (real-time)</th>
                        <th class="text-center">Retention Rate</th>
                        <th class="text-center">Growth</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($analisisCabang as $cab)
                    <tr>
                        <td class="px-3 fw-semibold">{{ $cab['nama'] }}</td>
                        <td class="text-center">{{ number_format($cab['awal']) }}</td>
                        <td class="text-center text-info fw-semibold">{{ number_format($cab['baru']) }}</td>
                        <td class="text-center text-success fw-semibold">{{ number_format($cab['retained']) }}</td>
                        <td class="text-center text-danger fw-semibold">{{ number_format($cab['lost']) }}</td>
                        <td class="text-center">
                            @if(!is_null($cab['retRate']))
                            <span class="badge {{ $cab['retRate'] >= 70 ? 'bg-success' : ($cab['retRate'] >= 40 ? 'bg-warning text-dark' : 'bg-danger') }}">
                                {{ $cab['retRate'] }}%
                            </span>
                            @else
                            <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if(!is_null($cab['growth']))
                            <span class="fw-semibold {{ $cab['growth'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $cab['growth'] >= 0 ? '+' : '' }}{{ $cab['growth'] }}%
                            </span>
                            @else
                            <span class="text-muted small">-</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Bar Chart perbandingan cabang — 3 chart terpisah agar skala tidak bentrok --}}
        <div class="row g-3 mt-1">
            <div class="col-12 col-md-4">
                <p class="small fw-semibold text-success mb-1"><i class="fas fa-percentage me-1"></i>Retention Rate (%)</p>
                <div style="position:relative;height:180px">
                    <canvas id="chartCabangRetRate"></canvas>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <p class="small fw-semibold text-info mb-1"><i class="fas fa-user-plus me-1"></i>Pelanggan Baru</p>
                <div style="position:relative;height:180px">
                    <canvas id="chartCabangBaru"></canvas>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <p class="small fw-semibold text-danger mb-1"><i class="fas fa-user-slash me-1"></i>Lost (real-time)</p>
                <div style="position:relative;height:180px">
                    <canvas id="chartCabangLost"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ============ STATUS RETENTION ============ --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-semibold"><i class="fas fa-exclamation-triangle me-2 text-warning"></i>Status Retention Pelanggan <span class="text-muted small fw-normal">(berdasarkan hari ini)</span></h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            {{-- At Risk --}}
            <div class="col-md-4">
                <a href="{{ route('retention.index', array_merge(request()->query(), ['status' => 'at_risk'])) }}"
                   class="text-decoration-none">
                    <div class="card border-warning h-100 {{ $statusFilter === 'at_risk' ? 'bg-warning bg-opacity-10' : '' }}">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-exclamation-circle fa-2x text-warning mb-2"></i>
                            <h5 class="fw-bold text-warning mb-0">{{ number_format($statusCounts->at_risk_only ?? 0) }}</h5>
                            <p class="text-muted small mb-1">Pelanggan <strong>At Risk</strong></p>
                            <p class="text-muted mb-0" style="font-size:.72rem">Tidak datang 61–90 hari</p>
                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning mt-2">Klik untuk detail</span>
                        </div>
                    </div>
                </a>
            </div>
            {{-- Dormant --}}
            <div class="col-md-4">
                <a href="{{ route('retention.index', array_merge(request()->query(), ['status' => 'dormant'])) }}"
                   class="text-decoration-none">
                    <div class="card border-danger h-100 {{ $statusFilter === 'dormant' ? 'bg-danger bg-opacity-10' : '' }}">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-moon fa-2x text-danger mb-2"></i>
                            <h5 class="fw-bold text-danger mb-0">{{ number_format($statusCounts->dormant_only ?? 0) }}</h5>
                            <p class="text-muted small mb-1">Pelanggan <strong>Dormant</strong></p>
                            <p class="text-muted mb-0" style="font-size:.72rem">Tidak datang 91–180 hari</p>
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger mt-2">Klik untuk detail</span>
                        </div>
                    </div>
                </a>
            </div>
            {{-- Lost --}}
            <div class="col-md-4">
                <a href="{{ route('retention.index', array_merge(request()->query(), ['status' => 'lost'])) }}"
                   class="text-decoration-none">
                    <div class="card border-secondary h-100 {{ $statusFilter === 'lost' ? 'bg-secondary bg-opacity-10' : '' }}">
                        <div class="card-body text-center py-4">
                            <i class="fas fa-user-slash fa-2x text-secondary mb-2"></i>
                            <h5 class="fw-bold text-secondary mb-0">{{ number_format($statusCounts->lost_total ?? 0) }}</h5>
                            <p class="text-muted small mb-1">Pelanggan <strong>Lost</strong></p>
                            <p class="text-muted mb-0" style="font-size:.72rem">Tidak datang &gt; 180 hari</p>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary mt-2">Klik untuk detail</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        {{-- Cumulative totals --}}
        <div class="mt-3 small text-muted">
            <i class="fas fa-info-circle me-1"></i>
            At Risk + Dormant + Lost (&gt;60 hari): <strong>{{ number_format($statusCounts->at_risk_total ?? 0) }}</strong>
            &nbsp;|&nbsp;
            Dormant + Lost (&gt;90 hari): <strong>{{ number_format($statusCounts->dormant_total ?? 0) }}</strong>
            &nbsp;|&nbsp;
            Lost saja (&gt;180 hari): <strong>{{ number_format($statusCounts->lost_total ?? 0) }}</strong>
        </div>
    </div>
</div>

{{-- ============ LIST PELANGGAN BY STATUS ============ --}}
@if($statusFilter && $statusPelanggan)
@php
    $statusLabel = match($statusFilter) {
        'at_risk' => ['label' => 'At Risk', 'color' => 'warning', 'icon' => 'exclamation-circle', 'desc' => '61–90 hari'],
        'dormant' => ['label' => 'Dormant', 'color' => 'danger',  'icon' => 'moon',              'desc' => '91–180 hari'],
        'lost'    => ['label' => 'Lost',    'color' => 'secondary','icon' => 'user-slash',        'desc' => '> 180 hari'],
        default   => ['label' => 'Unknown', 'color' => 'secondary','icon' => 'question',          'desc' => ''],
    };
@endphp
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold">
            <i class="fas fa-{{ $statusLabel['icon'] }} me-2 text-{{ $statusLabel['color'] }}"></i>
            Daftar Pelanggan Status <span class="text-{{ $statusLabel['color'] }}">{{ $statusLabel['label'] }}</span>
            <span class="text-muted small fw-normal">(tidak datang {{ $statusLabel['desc'] }})</span>
        </h6>
        <a href="{{ route('retention.index', array_diff_key(request()->query(), ['status' => ''])) }}"
           class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-times me-1"></i>Tutup
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">No</th>
                        <th>PID</th>
                        <th>Nama</th>
                        <th>Cabang</th>
                        <th>Klasifikasi</th>
                        <th>Kunjungan Terakhir</th>
                        <th class="text-center">Hari Sejak Terakhir</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statusPelanggan as $idx => $p)
                    <tr>
                        <td class="px-4">{{ ($statusPelanggan->currentPage() - 1) * $statusPelanggan->perPage() + $idx + 1 }}</td>
                        <td><code class="bg-light px-1 rounded small">{{ $p->pid }}</code></td>
                        <td class="fw-semibold">{{ $p->nama }}</td>
                        <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary small">
                                {{ $p->cabang?->nama ?? '-' }}
                            </span>
                        </td>
                        <td>
                            @php
                                $classBadge = match($p->class) {
                                    'Prioritas' => 'bg-danger bg-opacity-10 text-danger border-danger',
                                    'Loyal'     => 'bg-success bg-opacity-10 text-success border-success',
                                    'Potensial' => 'bg-warning bg-opacity-10 text-warning border-warning',
                                    default     => 'bg-secondary bg-opacity-10 text-secondary border-secondary',
                                };
                            @endphp
                            <span class="badge {{ $classBadge }} border small">{{ strtoupper($p->class) }}</span>
                        </td>
                        <td>{{ $p->last_visit ? \Carbon\Carbon::parse($p->last_visit)->format('d-m-Y') : '-' }}</td>
                        <td class="text-center">
                            <span class="badge bg-{{ $statusLabel['color'] }} bg-opacity-10 text-{{ $statusLabel['color'] }} border border-{{ $statusLabel['color'] }}">
                                {{ $p->days_since }} hari
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('pelanggan.show', $p->id) }}" class="btn btn-sm btn-outline-primary" title="Lihat Detail">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-check-circle fa-2x mb-3 text-success opacity-50"></i>
                            <p class="mb-0">Tidak ada pelanggan dengan status ini.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center p-3 border-top bg-light small">
            <div class="text-muted">
                Menampilkan <strong>{{ $statusPelanggan->firstItem() ?? 0 }} - {{ $statusPelanggan->lastItem() ?? 0 }}</strong>
                dari <strong>{{ $statusPelanggan->total() }}</strong> pelanggan
            </div>
            <div>{{ $statusPelanggan->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>
</div>
@endif

{{-- ============ TREND CHART 12 BULAN ============ --}}
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-semibold"><i class="fas fa-chart-line me-2 text-primary"></i>Tren Aktivitas Pelanggan (12 Bulan Terakhir)</h6>
    </div>
    <div class="card-body">
        <canvas id="retentionTrendChart" height="90"></canvas>
    </div>
</div>

{{-- ============ REVENUE RETENTION ============ --}}
@if($isAdminOrAbove && $revenueData)
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="fas fa-coins me-2 text-warning"></i>Revenue Retention</h6>
        <span class="text-muted small">vs {{ $revenueData['prev_label'] }}</span>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            {{-- Revenue Rate --}}
            <div class="col-md-3">
                <div class="card border-0 bg-light h-100 text-center py-3">
                    <div class="card-body p-2">
                        <p class="text-muted small mb-1 fw-semibold" style="font-size:.72rem">Revenue Retention Rate</p>
                        @php $rr = $revenueData['ret_rate']; @endphp
                        <h3 class="fw-bold mb-0 {{ is_null($rr) ? 'text-muted' : ($rr >= 70 ? 'text-success' : ($rr >= 40 ? 'text-warning' : 'text-danger')) }}">
                            {{ is_null($rr) ? '-' : $rr.'%' }}
                        </h3>
                        <p class="text-muted mb-0" style="font-size:.7rem">Rev retained / Rev periode lalu</p>
                    </div>
                </div>
            </div>
            {{-- Total Revenue --}}
            <div class="col-md-3">
                <div class="card border-0 bg-light h-100 text-center py-3">
                    <div class="card-body p-2">
                        <p class="text-muted small mb-1 fw-semibold" style="font-size:.72rem">Total Revenue Periode Ini</p>
                        <h5 class="fw-bold text-primary mb-0">Rp {{ number_format($revenueData['total'], 0, ',', '.') }}</h5>
                        @if($revenueData['prev'] > 0)
                        <p class="mb-0" style="font-size:.7rem">
                            <span class="{{ $revenueData['growth'] >= 0 ? 'text-success' : 'text-danger' }} fw-semibold">
                                {{ $revenueData['growth'] >= 0 ? '▲' : '▼' }} {{ abs($revenueData['growth']) }}%
                            </span>
                            <span class="text-muted"> vs periode lalu</span>
                        </p>
                        @endif
                    </div>
                </div>
            </div>
            {{-- Revenue dari Retained --}}
            <div class="col-md-3">
                <div class="card border-0 bg-light h-100 text-center py-3">
                    <div class="card-body p-2">
                        <p class="text-muted small mb-1 fw-semibold" style="font-size:.72rem">Dari Pelanggan Retained</p>
                        <h5 class="fw-bold text-success mb-0">Rp {{ number_format($revenueData['retained'], 0, ',', '.') }}</h5>
                        @if($revenueData['total'] > 0)
                        <p class="text-muted mb-0" style="font-size:.7rem">{{ round($revenueData['retained']/$revenueData['total']*100) }}% dari total</p>
                        @endif
                    </div>
                </div>
            </div>
            {{-- Revenue dari Baru --}}
            <div class="col-md-3">
                <div class="card border-0 bg-light h-100 text-center py-3">
                    <div class="card-body p-2">
                        <p class="text-muted small mb-1 fw-semibold" style="font-size:.72rem">Dari Pelanggan Baru</p>
                        <h5 class="fw-bold text-info mb-0">Rp {{ number_format($revenueData['baru'], 0, ',', '.') }}</h5>
                        @if($revenueData['total'] > 0)
                        <p class="text-muted mb-0" style="font-size:.7rem">{{ round($revenueData['baru']/$revenueData['total']*100) }}% dari total</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        {{-- Progress bar komposisi revenue --}}
        @if($revenueData['total'] > 0)
        @php
            $pctRetained = round($revenueData['retained'] / $revenueData['total'] * 100);
            $pctBaru     = 100 - $pctRetained;
        @endphp
        <p class="small text-muted mb-1 fw-semibold">Komposisi Revenue</p>
        <div class="progress" style="height:22px;border-radius:6px">
            <div class="progress-bar bg-success" style="width:{{ $pctRetained }}%" title="Retained: {{ $pctRetained }}%">
                <span class="small fw-semibold">Retained {{ $pctRetained }}%</span>
            </div>
            <div class="progress-bar bg-info" style="width:{{ $pctBaru }}%" title="Baru: {{ $pctBaru }}%">
                <span class="small fw-semibold">Baru {{ $pctBaru }}%</span>
            </div>
        </div>
        @endif
    </div>
</div>
@endif

{{-- ============ RETENTION BY KLASIFIKASI ============ --}}
@if($isAdminOrAbove && $retByKlasifikasi && count($retByKlasifikasi) > 0)
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-semibold"><i class="fas fa-layer-group me-2 text-primary"></i>Retention by Klasifikasi</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            {{-- Tabel --}}
            <div class="col-md-7">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:.85rem">
                        <thead class="table-light">
                            <tr>
                                <th class="px-3">Klasifikasi</th>
                                <th class="text-center">Awal</th>
                                <th class="text-center">Retained</th>
                                <th class="text-center">Baru</th>
                                <th class="text-center">Retention Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($retByKlasifikasi as $rk)
                            @php
                                $badge = match($rk['kelas']) {
                                    'Prioritas' => 'bg-danger bg-opacity-10 text-danger border-danger',
                                    'Loyal'     => 'bg-success bg-opacity-10 text-success border-success',
                                    'Potensial' => 'bg-warning bg-opacity-10 text-warning border-warning',
                                    default     => 'bg-secondary bg-opacity-10 text-secondary border-secondary',
                                };
                            @endphp
                            <tr>
                                <td class="px-3">
                                    <span class="badge {{ $badge }} border">{{ strtoupper($rk['kelas']) }}</span>
                                </td>
                                <td class="text-center">{{ number_format($rk['awal']) }}</td>
                                <td class="text-center text-success fw-semibold">{{ number_format($rk['retained']) }}</td>
                                <td class="text-center text-info fw-semibold">{{ number_format($rk['baru']) }}</td>
                                <td class="text-center">
                                    @if(!is_null($rk['rate']))
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height:8px">
                                            <div class="progress-bar {{ $rk['rate'] >= 70 ? 'bg-success' : ($rk['rate'] >= 40 ? 'bg-warning' : 'bg-danger') }}"
                                                 style="width:{{ $rk['rate'] }}%"></div>
                                        </div>
                                        <span class="fw-semibold {{ $rk['rate'] >= 70 ? 'text-success' : ($rk['rate'] >= 40 ? 'text-warning' : 'text-danger') }}" style="min-width:42px">
                                            {{ $rk['rate'] }}%
                                        </span>
                                    </div>
                                    @else
                                    <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            {{-- Bar Chart --}}
            <div class="col-md-5">
                <div style="position:relative;height:200px">
                    <canvas id="chartKlasifikasiRetRate"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ============ C. REPEAT VISIT RATE ============ --}}
@if($isAdminOrAbove && $repeatVisitData)
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-semibold"><i class="fas fa-redo me-2 text-info"></i>Repeat Visit Rate</h6>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 bg-light h-100 text-center py-3">
                    <div class="card-body p-2">
                        <p class="text-muted small mb-1 fw-semibold" style="font-size:.72rem">Total Pelanggan Aktif</p>
                        <h4 class="fw-bold text-primary mb-0">{{ number_format($repeatVisitData['total_pelanggan']) }}</h4>
                        <p class="text-muted mb-0" style="font-size:.7rem">dalam periode ini</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 bg-light h-100 text-center py-3">
                    <div class="card-body p-2">
                        <p class="text-muted small mb-1 fw-semibold" style="font-size:.72rem">Rata-rata Kunjungan</p>
                        <h4 class="fw-bold text-info mb-0">{{ $repeatVisitData['avg_visit'] }}x</h4>
                        <p class="text-muted mb-0" style="font-size:.7rem">per pelanggan</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 bg-light h-100 text-center py-3">
                    <div class="card-body p-2">
                        <p class="text-muted small mb-1 fw-semibold" style="font-size:.72rem">Datang &gt; 2x</p>
                        <h4 class="fw-bold text-success mb-0">{{ number_format($repeatVisitData['more_than_2']) }}</h4>
                        @if($repeatVisitData['total_pelanggan'] > 0)
                        <p class="text-muted mb-0" style="font-size:.7rem">{{ round($repeatVisitData['more_than_2']/$repeatVisitData['total_pelanggan']*100) }}% pelanggan</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 bg-light h-100 text-center py-3">
                    <div class="card-body p-2">
                        <p class="text-muted small mb-1 fw-semibold" style="font-size:.72rem">Datang &gt; 5x</p>
                        <h4 class="fw-bold text-warning mb-0">{{ number_format($repeatVisitData['more_than_5']) }}</h4>
                        @if($repeatVisitData['total_pelanggan'] > 0)
                        <p class="text-muted mb-0" style="font-size:.7rem">{{ round($repeatVisitData['more_than_5']/$repeatVisitData['total_pelanggan']*100) }}% pelanggan</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Top 5 pelanggan paling aktif --}}
        @if($repeatVisitData['top_active']->count() > 0)
        <h6 class="fw-semibold text-muted mb-3" style="font-size:.82rem"><i class="fas fa-star me-1 text-warning"></i>Pelanggan Paling Aktif Periode Ini</h6>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.85rem">
                <thead class="table-light">
                    <tr>
                        <th class="px-3" style="width:40px">#</th>
                        <th>PID</th>
                        <th>Nama</th>
                        <th>Cabang</th>
                        <th class="text-center">Kunjungan</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($repeatVisitData['top_active'] as $idx => $ta)
                    <tr>
                        <td class="px-3 fw-bold text-warning">{{ $idx + 1 }}</td>
                        <td><code class="bg-light px-1 rounded small">{{ $ta->pid }}</code></td>
                        <td class="fw-semibold">{{ $ta->nama }}</td>
                        <td>
                            @php $cabNama = $cabangs->firstWhere('id', $ta->cabang_id)?->nama ?? '-'; @endphp
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary small">{{ $cabNama }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-success">{{ $ta->visit_count }}x</span>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('pelanggan.show', $ta->id) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endif

{{-- ============ C. COHORT ANALYSIS ============ --}}
@if($isAdminOrAbove && $cohortData && count($cohortData['matrix']) > 0)
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-semibold"><i class="fas fa-th me-2 text-primary"></i>Cohort Analysis
            <span class="text-muted small fw-normal ms-1">(berdasarkan bulan pertama datang, 6 bulan terakhir)</span>
        </h6>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">Setiap baris = kelompok pelanggan berdasarkan bulan pertama datang. Kolom menunjukkan berapa % yang kembali di bulan berikutnya.</p>
        <div class="table-responsive">
            <table class="table table-bordered align-middle text-center mb-0" style="font-size:.82rem">
                <thead class="table-light">
                    <tr>
                        <th class="text-start px-3">Cohort</th>
                        <th>Ukuran</th>
                        @foreach($cohortData['months'] as $idx => $cm)
                        <th>Bulan +{{ $idx }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($cohortData['matrix'] as $row)
                    <tr>
                        <td class="text-start px-3 fw-semibold">{{ \Carbon\Carbon::createFromFormat('Y-m', $row['month'])->format('M Y') }}</td>
                        <td class="fw-semibold">{{ number_format($row['size']) }}</td>
                        @foreach($row['months'] as $mIdx => $mData)
                        @if(is_null($mData))
                        <td class="text-muted" style="background:#f8f9fa">-</td>
                        @else
                        @php
                            $pct = $mData['pct'];
                            $bg  = $mIdx === 0 ? '#0d6efd' : ($pct >= 60 ? '#198754' : ($pct >= 30 ? '#fd7e14' : ($pct >= 10 ? '#ffc107' : '#dee2e6')));
                            $fg  = ($pct >= 10 || $mIdx === 0) ? '#fff' : '#6c757d';
                        @endphp
                        <td style="background:{{ $bg }};color:{{ $fg }};font-weight:600" title="{{ $mData['count'] }} pelanggan">
                            {{ $mIdx === 0 ? '100%' : $pct.'%' }}
                        </td>
                        @endif
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-2 small text-muted">
            <i class="fas fa-info-circle me-1"></i>
            Warna: <span class="badge bg-success">≥60%</span> <span class="badge bg-warning text-dark">≥30%</span> <span class="badge" style="background:#ffc107;color:#fff">≥10%</span> <span class="badge bg-secondary">&lt;10%</span>
        </div>
    </div>
</div>
@endif

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
{{-- Chart Retention by Klasifikasi --}}
@if($isAdminOrAbove && $retByKlasifikasi && count($retByKlasifikasi) > 0)
(function() {
    const klsLabels = @json(collect($retByKlasifikasi)->pluck('kelas'));
    const klsRates  = @json(collect($retByKlasifikasi)->pluck('rate'));
    const klsColors = klsLabels.map(k => {
        if (k === 'Prioritas') return 'rgba(220,53,69,0.75)';
        if (k === 'Loyal')     return 'rgba(25,135,84,0.75)';
        if (k === 'Potensial') return 'rgba(255,193,7,0.75)';
        return 'rgba(108,117,125,0.75)';
    });
    new Chart(document.getElementById('chartKlasifikasiRetRate').getContext('2d'), {
        type: 'bar',
        data: {
            labels: klsLabels,
            datasets: [{
                label: 'Retention Rate (%)',
                data: klsRates.map(v => v ?? 0),
                backgroundColor: klsColors,
                borderRadius: 5, borderWidth: 0,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => ctx.parsed.y + '%' } }
            },
            scales: {
                x: { ticks: { font: { size: 11 } } },
                y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%', font: { size: 11 } } }
            }
        }
    });
})();
@endif

{{-- Bar chart Analisis Cabang (Direktur only) --}}
@if($isDirektur && $analisisCabang && count($analisisCabang) > 1)
(function() {
    const cabangLabels  = @json(collect($analisisCabang)->pluck('nama'));
    const retentionData = @json(collect($analisisCabang)->pluck('retRate'));
    const baruData      = @json(collect($analisisCabang)->pluck('baru'));
    const lostData      = @json(collect($analisisCabang)->pluck('lost'));

    function makeCabangChart(id, data, color, suffix) {
        return new Chart(document.getElementById(id).getContext('2d'), {
            type: 'bar',
            data: {
                labels: cabangLabels,
                datasets: [{
                    data: data,
                    backgroundColor: color + '0.7)',
                    borderColor: color + '1)',
                    borderWidth: 1,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { font: { size: 10 }, maxRotation: 30 } },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: { size: 10 },
                            callback: v => suffix === '%' ? v + '%' : v.toLocaleString('id-ID')
                        }
                    }
                }
            }
        });
    }
    makeCabangChart('chartCabangRetRate', retentionData.map(v => v ?? 0), 'rgba(25,135,84,', '%');
    makeCabangChart('chartCabangBaru',    baruData,                        'rgba(13,202,240,', '');
    makeCabangChart('chartCabangLost',    lostData,                        'rgba(220,53,69,',  '');
})();
@endif

(function() {
    const labels    = @json($trendLabels);
    const pelanggan = @json($trendPelanggan);
    const kunjungan = @json($trendKunjungan);

    new Chart(document.getElementById('retentionTrendChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Pelanggan Aktif',
                    data: pelanggan,
                    backgroundColor: 'rgba(0, 86, 179, 0.65)',
                    borderColor: 'rgba(0, 86, 179, 1)',
                    borderWidth: 1,
                    yAxisID: 'y',
                },
                {
                    label: 'Total Kunjungan',
                    data: kunjungan,
                    type: 'line',
                    backgroundColor: 'rgba(0, 168, 204, 0.15)',
                    borderColor: 'rgba(0, 168, 204, 1)',
                    borderWidth: 2,
                    pointRadius: 3,
                    fill: false,
                    yAxisID: 'y2',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        afterBody: function(items) {
                            const idx = items[0].dataIndex;
                            return `Rata-rata kunjungan/pelanggan: ${pelanggan[idx] > 0 ? (kunjungan[idx]/pelanggan[idx]).toFixed(1) : 0}x`;
                        }
                    }
                }
            },
            scales: {
                y:  { beginAtZero: true, title: { display: true, text: 'Pelanggan Aktif' } },
                y2: { beginAtZero: true, position: 'right', title: { display: true, text: 'Total Kunjungan' }, grid: { drawOnChartArea: false } }
            }
        }
    });
})();
</script>
@endsection
