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
            Total &gt;60 hari: <strong>{{ number_format($statusCounts->at_risk_total ?? 0) }}</strong> &nbsp;|&nbsp;
            Total &gt;90 hari: <strong>{{ number_format($statusCounts->dormant_total ?? 0) }}</strong> &nbsp;|&nbsp;
            Total &gt;180 hari: <strong>{{ number_format($statusCounts->lost_total ?? 0) }}</strong>
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

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
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
