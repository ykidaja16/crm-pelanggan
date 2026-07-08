@extends('layouts.main')

@section('title', 'Pertumbuhan Kelas Pelanggan')

@section('content')
@php
    $kelasColors = [
        'Prioritas' => (object) ['bg' => '#8B5CF6', 'light' => '#F5F3FF', 'text' => '#6D28D9'],
        'Loyal'     => (object) ['bg' => '#3B82F6', 'light' => '#EFF6FF', 'text' => '#1D4ED8'],
        'Potensial' => (object) ['bg' => '#F59E0B', 'light' => '#FFFBEB', 'text' => '#B45309'],
        'Umum'      => (object) ['bg' => '#6B7280', 'light' => '#F9FAFB', 'text' => '#374151'],
    ];
    $grandTotal    = collect($summaryData)->sum('total');
    $grandAktif    = collect($summaryData)->sum('aktif');
    $premiumTotal  = collect($summaryData)->whereIn('kelas', ['Prioritas','Loyal'])->sum('total');
    $premiumPct    = $grandTotal > 0 ? round($premiumTotal / $grandTotal * 100, 1) : 0;
    $premiumLabel  = $premiumPct >= 20 ? 'Bagus' : ($premiumPct >= 10 ? 'Cukup' : 'Perlu Ditingkatkan');
    $premiumColor  = $premiumPct >= 20 ? '#16a34a' : ($premiumPct >= 10 ? '#d97706' : '#dc2626');
    // CSS class helpers (no inline dynamic styles)
    $premiumCls    = $premiumPct >= 20 ? 'premium-good' : ($premiumPct >= 10 ? 'premium-medium' : 'premium-low');
    $premiumBadge  = $premiumPct >= 20 ? 'premium-badge-good' : ($premiumPct >= 10 ? 'premium-badge-medium' : 'premium-badge-low');
    $premiumBar    = $premiumPct >= 20 ? 'premium-bar-good' : ($premiumPct >= 10 ? 'premium-bar-medium' : 'premium-bar-low');
    $premiumBarW   = 'width:' . $premiumPct . '%;border-radius:6px;';
@endphp

<style>
.pk-kpi-main { border-radius: 14px; border: 1px solid #e8edf2; transition: box-shadow .2s, transform .2s; background: #fff; }
.pk-kpi-main:hover { box-shadow: 0 8px 24px rgba(0,0,0,.09); transform: translateY(-2px); }
.pk-icon { width: 44px; height: 44px; border-radius: 11px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
.pk-klass-card { border-radius: 14px; border: 1px solid #e8edf2; border-top: 4px solid; transition: box-shadow .2s, transform .2s; background: #fff; }
.pk-klass-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.09); transform: translateY(-2px); }
.pk-diff-line { font-size: 0.80rem; font-weight: 600; margin-top: 6px; }
.pk-diff-line .arrow-up { color: #16a34a; }
.pk-diff-line .arrow-dn { color: #dc2626; }
.pk-diff-line .neutral  { color: #64748b; }
.text-success-d { color: #16a34a !important; }
.text-danger-d  { color: #dc2626 !important; }
.insight-card { background: #f0fdf4; border: 1px solid #86efac; border-radius: 14px; }
.insight-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; flex-shrink: 0; margin-top: 5px; }
.chart-toggle .btn { border-radius: 6px; font-size: .78rem; padding: 3px 12px; }
.chart-toggle .btn.active { color: #fff !important; }
/* ── Per-kelas color classes ── */
.kelas-border-prioritas { border-top-color: #8B5CF6 !important; }
.kelas-border-loyal     { border-top-color: #3B82F6 !important; }
.kelas-border-potensial { border-top-color: #F59E0B !important; }
.kelas-border-umum      { border-top-color: #6B7280 !important; }
.kelas-bg-prioritas { background: #8B5CF6 !important; }
.kelas-bg-loyal     { background: #3B82F6 !important; }
.kelas-bg-potensial { background: #F59E0B !important; }
.kelas-bg-umum      { background: #6B7280 !important; }
.kelas-text-prioritas { color: #8B5CF6 !important; }
.kelas-text-loyal     { color: #3B82F6 !important; }
.kelas-text-potensial { color: #F59E0B !important; }
.kelas-text-umum      { color: #6B7280 !important; }
.pk-klass-icon { color: #fff; width: 30px; height: 30px; font-size: 13px; }
/* ── Premium color classes ── */
.premium-good   { color: #16a34a !important; }
.premium-medium { color: #d97706 !important; }
.premium-low    { color: #dc2626 !important; }
.premium-badge-good   { background: #16a34a !important; }
.premium-badge-medium { background: #d97706 !important; }
.premium-badge-low    { background: #dc2626 !important; }
.premium-bar-good   { background: #16a34a !important; }
.premium-bar-medium { background: #d97706 !important; }
.premium-bar-low    { background: #dc2626 !important; }
</style>

{{-- HEADER --}}
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="mb-0 fw-bold text-primary"><i class="fas fa-chart-line me-2"></i>Dashboard Pertumbuhan Kelas Pelanggan</h4>
    <small class="text-muted"><i class="fas fa-clock me-1"></i>Data terakhir diperbarui: {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i') }}</small>
</div>

{{-- 1. FILTER --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
    <div class="card-body py-2 px-3">
        <form method="GET" action="{{ route('pertumbuhan-kelas.index') }}" id="filterForm"
              class="d-flex align-items-center flex-wrap gap-2">

            <span class="small fw-semibold text-muted">Periode</span>
            <select name="filter_type" id="filterType" class="form-select form-select-sm shadow-none" style="width:auto;" onchange="toggleFilterFields()">
                <option value="monthly" {{ $filterType==='monthly'?'selected':'' }}>Per Tahun</option>
                <option value="yearly"  {{ $filterType==='yearly' ?'selected':'' }}>Per 5 Tahun</option>
                <option value="range"   {{ $filterType==='range'  ?'selected':'' }}>Range Bulan</option>
            </select>

            {{-- Per Tahun fields --}}
            <div id="monthlyFields" class="{{ $filterType!=='monthly'?'d-none':'' }} d-flex align-items-center gap-2">
                <span class="small fw-semibold text-muted">Tahun</span>
                <select name="year" class="form-select form-select-sm shadow-none" style="width:auto;">
                    @for($y=date('Y');$y>=2018;$y--)
                        <option value="{{ $y }}" {{ $year==$y?'selected':'' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            {{-- Range Bulan fields --}}
            <div id="rangeFields" class="{{ $filterType!=='range'?'d-none':'' }} d-flex align-items-center gap-2">
                <span class="small fw-semibold text-muted">Dari</span>
                <input type="month" name="date_from" class="form-control form-control-sm shadow-none" style="width:auto;"
                       value="{{ $dateFrom ?? \Carbon\Carbon::now()->subYear()->format('Y-m') }}">
                <span class="small text-muted">–</span>
                <span class="small fw-semibold text-muted">Sampai</span>
                <input type="month" name="date_to" class="form-control form-control-sm shadow-none" style="width:auto;"
                       value="{{ $dateTo ?? \Carbon\Carbon::now()->format('Y-m') }}">
            </div>

            {{-- Cabang --}}
            @if($cabangs->count()>1)
            <span class="small fw-semibold text-muted">Cabang</span>
            <select name="cabang_id" class="form-select form-select-sm shadow-none" style="width:auto;">
                <option value="">Semua Cabang</option>
                @foreach($cabangs as $c)
                    <option value="{{ $c->id }}" {{ $cabangId==$c->id?'selected':'' }}>{{ $c->nama }}</option>
                @endforeach
            </select>
            @endif

            <div class="ms-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-3 fw-medium" style="border-radius:8px;">
                    <i class="fas fa-search me-1"></i>Tampilkan
                </button>
                <a href="{{ route('pertumbuhan-kelas.export-ringkasan', request()->query()) }}"
                   class="btn btn-outline-success btn-sm px-3 fw-medium" style="border-radius:8px;">
                    <i class="fas fa-file-excel me-1"></i>Export Ringkasan
                </a>
            </div>
        </form>
    </div>
</div>

{{-- 2. KPI RINGKASAN ATAS --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card pk-kpi-main h-100 border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="pk-icon" style="background:#e0e7ff;color:#4338ca;"><i class="fas fa-users"></i></div>
                    <span class="small fw-semibold text-muted">Total Pelanggan</span>
                </div>
                <div class="fs-3 fw-bold" style="color:#1e293b;">{{ number_format($grandTotal,0,',','.') }}</div>
                <div class="pk-diff-line" id="kpi-total-diff"><span class="neutral">— Menghitung...</span></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card pk-kpi-main h-100 border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="pk-icon" style="background:#dcfce7;color:#15803d;"><i class="fas fa-chart-line"></i></div>
                    <span class="small fw-semibold text-muted">Pertumbuhan (MoM)</span>
                </div>
                <div class="fs-3 fw-bold" id="kpi-pct" style="color:#1e293b;">—</div>
                <div class="pk-diff-line" id="kpi-pct-sub"><span class="neutral">— vs periode lalu</span></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card pk-kpi-main h-100 border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="pk-icon" style="background:#cffafe;color:#0369a1;"><i class="fas fa-user-check"></i></div>
                    <span class="small fw-semibold text-muted">Aktif Bulan Ini</span>
                </div>
                <div class="fs-3 fw-bold" style="color:#1e293b;">{{ number_format($grandAktif,0,',','.') }}</div>
                <div class="pk-diff-line" id="kpi-aktif-diff"><span class="neutral">— vs periode lalu</span></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card pk-kpi-main h-100 border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="pk-icon" style="background:#fef3c7;color:#b45309;"><i class="fas fa-trophy"></i></div>
                    <span class="small fw-semibold text-muted">Rasio Premium</span>
                </div>
                <div class="fs-3 fw-bold {{ $premiumCls }}">{{ $premiumPct }}%</div>
                <div class="pk-diff-line">
                    <span class="fw-bold {{ $premiumCls }}">{{ $premiumLabel }}</span>
                    <span class="text-muted fw-normal"> — Prioritas+Loyal</span>
                </div>
                <div class="small text-muted mt-1" style="font-size:0.73rem;">{{ number_format($premiumTotal,0,',','.') }} dari {{ number_format($grandTotal,0,',','.') }} pelanggan</div>
            </div>
        </div>
    </div>
</div>

{{-- 3. KPI KELAS --}}
<div class="row g-3 mb-4">
    @php
        $kelasIcons = ['Prioritas'=>'fa-star','Loyal'=>'fa-heart','Potensial'=>'fa-user-plus','Umum'=>'fa-users'];
    @endphp
    @foreach($summaryData as $item)
    @php $kelasSlug = strtolower($item['kelas']); @endphp
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card pk-klass-card h-100 shadow-sm kelas-border-{{ $kelasSlug }}">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="pk-icon pk-klass-icon kelas-bg-{{ $kelasSlug }}">
                        <i class="fas {{ $kelasIcons[$item['kelas']]??'fa-user' }}"></i>
                    </div>
                    <span class="fw-bold kelas-text-{{ $kelasSlug }}">{{ $item['kelas'] }}</span>
                </div>
                <div class="fs-2 fw-bold mb-0" style="color:#1e293b;">{{ number_format($item['total'],0,',','.') }}</div>
                <div class="small text-muted mb-2">{{ $item['pct'] }}% dari total</div>
                <div class="small text-muted mb-1">
                    <i class="fas fa-check-circle me-1 kelas-text-{{ $kelasSlug }}"></i>
                    <strong>{{ number_format($item['aktif'],0,',','.') }}</strong> aktif periode ini
                </div>
                {{-- JS will fill this --}}
                <div class="pk-diff-line mt-2" id="klass-diff-{{ Str::slug($item['kelas']) }}">
                    <span class="neutral">— vs bulan lalu</span>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- 4+5. GRAFIK UTAMA + DONUT --}}
<div class="row g-3 mb-4">
    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center" style="border-radius:14px 14px 0 0;">
                <h6 class="mb-0 fw-bold text-dark">Penambahan Baru per Kelas — {{ $periodLabel }}</h6>
                <div class="btn-group btn-group-sm chart-toggle" role="group">
                    <button type="button" class="btn btn-outline-secondary" id="btn-line" onclick="switchChart('line')">Line</button>
                    <button type="button" class="btn btn-outline-secondary active" id="btn-bar"  onclick="switchChart('bar')" style="background:#4338ca;color:#fff;border-color:#4338ca;">Bar</button>
                </div>
            </div>
            <div class="card-body pt-1" style="height:320px;">
                <canvas id="pertumbuhanChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
            <div class="card-header bg-white border-0 py-3" style="border-radius:14px 14px 0 0;">
                <h6 class="mb-0 fw-bold text-dark">Komposisi Kelas</h6>
                <small class="text-muted" style="font-size:.75rem;">% dari total seluruh pelanggan terdaftar</small>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center pb-1">
                <canvas id="donutChart" style="max-height:300px;"></canvas>
            </div>
            {{-- Customer Health Score --}}
            <div class="card-footer bg-transparent border-0 pt-0 pb-3 px-3">
                <div class="rounded-3 p-2" style="background:#f8fafc;border:1px solid #e2e8f0;">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-semibold text-muted">Customer Health Score</span>
                        <span class="badge {{ $premiumBadge }}" style="font-size:.72rem;">{{ $premiumLabel }}</span>
                    </div>
                    <div class="progress" style="height:8px;border-radius:6px;">
                        <div class="progress-bar {{ $premiumBar }}" role="progressbar"
                             data-width="{{ $premiumPct }}"
                             aria-valuenow="{{ $premiumPct }}" aria-valuemin="0" aria-valuemax="100"
                             id="pk-premium-bar"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted" style="font-size:.7rem;">Prioritas+Loyal: <strong>{{ $premiumPct }}%</strong></small>
                        @php $umumPct = collect($summaryData)->firstWhere('kelas','Umum')['pct'] ?? 0; @endphp
                        <small class="text-muted" style="font-size:.7rem;">Umum: <strong>{{ $umumPct }}%</strong> ← potensi upgrade</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 6. INSIGHT RINGKAS --}}
<div class="insight-card p-3 mb-4">
    <h6 class="fw-bold mb-3" style="color:#15803d;"><i class="fas fa-lightbulb me-2"></i>Insight Bulan Ini</h6>
    <ul class="mb-0 ps-0 list-unstyled" id="insight-list">
        <li class="text-muted small"><i class="fas fa-spinner fa-spin me-2"></i>Menganalisa data...</li>
    </ul>
</div>

{{-- 7. TABEL RINGKASAN --}}
<div class="card border-0 shadow-sm" style="border-radius:14px;">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center" style="border-radius:14px 14px 0 0;">
        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-table me-2"></i>Tabel Ringkasan Ditingkatkan</h6>
        <a href="{{ route('pertumbuhan-kelas.export-detail', request()->query()) }}" class="btn btn-success btn-sm px-3" style="border-radius:8px;">
            <i class="fas fa-file-excel me-1"></i>Export Data
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Kelas</th>
                        <th class="text-center">Total Pelanggan<br><small class="fw-normal text-muted">(Periode Ini)</small></th>
                        <th class="text-center">Bulan Lalu</th>
                        <th class="text-center">Perubahan</th>
                        <th class="text-center">% Perubahan</th>
                        <th class="text-center">Aktif Periode Ini</th>
                        <th class="text-center">% dari Total</th>
                        <th class="text-center" style="min-width:110px;">Trend 12 Bulan</th>
                        <th class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($summaryData as $item)
                    @php $kelasSlug = strtolower($item['kelas']); @endphp
                    <tr>
                        <td class="ps-3">
                            <span class="badge rounded-pill px-3 py-2 kelas-bg-{{ $kelasSlug }}" style="font-size:.8rem;">{{ $item['kelas'] }}</span>
                        </td>
                        <td class="text-center fw-bold">{{ number_format($item['total'],0,',','.') }}</td>
                        <td class="text-center text-muted" id="tbl-prev-{{ Str::slug($item['kelas']) }}">—</td>
                        <td class="text-center fw-semibold"  id="tbl-diff-{{ Str::slug($item['kelas']) }}">—</td>
                        <td class="text-center fw-semibold"  id="tbl-pct-{{ Str::slug($item['kelas']) }}">—</td>
                        <td class="text-center">{{ number_format($item['aktif'],0,',','.') }}</td>
                        <td class="text-center">{{ $item['pct'] }}%</td>
                        <td class="text-center p-1">
                            <canvas id="spark-{{ Str::slug($item['kelas']) }}" width="100" height="32"></canvas>
                        </td>
                        <td class="text-center pe-3">
                            <a href="{{ route('pertumbuhan-kelas.detail', array_merge(request()->query(),['kelas'=>$item['kelas']])) }}"
                               class="btn btn-sm btn-light border text-primary" style="border-radius:8px;font-size:.78rem;">
                                <i class="fas fa-eye me-1"></i>Detail
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script id="chart-data"   type="application/json">{!! json_encode($chartData) !!}</script>
<script id="summary-data" type="application/json">{!! json_encode($summaryData) !!}</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Filter toggle ────────────────────────────────────────────────
function toggleFilterFields() {
    const t = document.getElementById('filterType').value;
    document.getElementById('monthlyFields').classList.toggle('d-none', t !== 'monthly');
    document.getElementById('rangeFields').classList.toggle('d-none',   t !== 'range');
}

// ── Raw data ─────────────────────────────────────────────────────
const chartRaw   = JSON.parse(document.getElementById('chart-data').textContent);
const summaryRaw = JSON.parse(document.getElementById('summary-data').textContent);

const COLOR = { Prioritas:'#8B5CF6', Loyal:'#3B82F6', Potensial:'#F59E0B', Umum:'#6B7280' };

// ── Determine current period index ───────────────────────────────
const filterType   = "{{ $filterType }}";
const selMonth     = parseInt("{{ $month }}", 10); // 1-12

// For monthly: chart has 12 points (Jan=0 … Dec=11)
// For yearly/range: use last index
let nowIdx  = filterType === 'monthly' ? selMonth - 1 : chartRaw.labels.length - 1;
let prevIdx = nowIdx > 0 ? nowIdx - 1 : null;

// ── Build per-class stats ─────────────────────────────────────────
let classStats = {};
let totalNow = 0, totalPrev = 0;

chartRaw.datasets.forEach(ds => {
    const now  = ds.data[nowIdx]  ?? 0;
    const prev = prevIdx !== null ? (ds.data[prevIdx] ?? 0) : 0;
    const diff = now - prev;
    const pct  = prev > 0 ? (diff / prev * 100) : (now > 0 ? 100 : 0);
    totalNow  += now;
    totalPrev += prev;
    classStats[ds.label] = { now, prev, diff, pct: pct.toFixed(1), trend: ds.data };
});

const grandDiff = totalNow - totalPrev;
const grandPct  = totalPrev > 0 ? (grandDiff / totalPrev * 100) : (totalNow > 0 ? 100 : 0);

// ── Helpers ───────────────────────────────────────────────────────
const fmt = n => n.toLocaleString('id-ID');

function diffHtml(diff, pct, label='vs periode lalu') {
    if (diff > 0) return `<span class="arrow-up">↑ +${fmt(diff)} (+${pct}%)</span> <span class="text-muted fw-normal">${label}</span>`;
    if (diff < 0) return `<span class="arrow-dn">↓ ${fmt(diff)} (${pct}%)</span> <span class="text-muted fw-normal">${label}</span>`;
    return `<span class="neutral">→ Tidak ada perubahan</span>`;
}

// ── DOM update on ready ───────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

    // Top KPI cards
    document.getElementById('kpi-total-diff').innerHTML  = diffHtml(grandDiff, grandPct.toFixed(1));
    document.getElementById('kpi-pct').innerHTML         = `<span class="${grandDiff>=0?'text-success-d':'text-danger-d'}">${grandDiff>=0?'+':''}${grandPct.toFixed(1)}%</span>`;
    document.getElementById('kpi-pct-sub').innerHTML     = diffHtml(grandDiff, grandPct.toFixed(1));
    document.getElementById('kpi-aktif-diff').innerHTML  = diffHtml(grandDiff, grandPct.toFixed(1));

    // Progress bar width (set via JS to avoid Blade expression in style="")
    const pkBar = document.getElementById('pk-premium-bar');
    if (pkBar) pkBar.style.width = pkBar.dataset.width + '%';

    // Per-class cards & table
    const insights = [];

    summaryRaw.forEach(item => {
        const slug = item.kelas.toLowerCase();
        const st   = classStats[item.kelas];
        if (!st) return;

        const sign = st.diff > 0 ? '+' : '';
        const col  = COLOR[item.kelas] || '#6B7280';

        // ── Kelas card diff line
        const cardEl = document.getElementById('klass-diff-' + slug);
        if (cardEl) cardEl.innerHTML = diffHtml(st.diff, st.pct, 'vs bulan lalu');

        // ── Table cells
        const elPrev = document.getElementById('tbl-prev-' + slug);
        const elDiff = document.getElementById('tbl-diff-' + slug);
        const elPct  = document.getElementById('tbl-pct-'  + slug);

        if (elPrev) elPrev.textContent = fmt(st.prev);
        if (elDiff) {
            const arrow = st.diff > 0 ? '↑' : (st.diff < 0 ? '↓' : '→');
            const cls   = st.diff > 0 ? 'text-success-d' : (st.diff < 0 ? 'text-danger-d' : 'text-muted');
            elDiff.innerHTML = `<span class="${cls}">${arrow} ${fmt(Math.abs(st.diff))}</span>`;
        }
        if (elPct) {
            const cls = st.diff > 0 ? 'text-success-d' : (st.diff < 0 ? 'text-danger-d' : 'text-muted');
            elPct.innerHTML = `<span class="${cls}">${sign}${st.pct}%</span>`;
        }

        // ── Sparkline (render after layout paint)
        requestAnimationFrame(() => {
            const canvas = document.getElementById('spark-' + slug);
            if (!canvas) return;
            new Chart(canvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: chartRaw.labels,
                    datasets: [{ data: st.trend, borderColor: col, borderWidth: 2, tension: 0.35, pointRadius: 0, fill: false }]
                },
                options: {
                    responsive: false, maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { enabled: false } },
                    scales:  { x: { display: false }, y: { display: false, min: 0 } },
                    layout:  { padding: 2 }
                }
            });
        });

        // ── Insight bullet
        const actWord = st.diff > 0 ? 'naik' : (st.diff < 0 ? 'turun' : 'stabil');
        const change  = st.diff !== 0
            ? `${Math.abs(st.diff)} (${sign}${st.pct}%)`
            : 'tidak berubah';
        insights.push({
            color: col,
            html: `<strong>${item.kelas}</strong> ${actWord} <strong>${change}</strong> pelanggan aktif dibanding bulan lalu.`
        });
    });

    // Dominant class overall insight
    const dom = summaryRaw.length > 0 ? summaryRaw[0] : null;
    if (dom) {
        insights.push({ color:'#3B82F6', html:`<strong>${dom.kelas}</strong> mendominasi dengan <strong>${dom.pct}%</strong> (${fmt(dom.total)}) dari total pelanggan.` });
    }
    // Total aktif insight
    const totalWord = grandDiff > 0 ? 'bertambah' : (grandDiff < 0 ? 'berkurang' : 'tidak berubah');
    insights.push({ color:'#15803d', html:`Total pelanggan aktif <strong>${totalWord} ${fmt(Math.abs(grandDiff))} (${grandDiff>=0?'+':''}${grandPct.toFixed(1)}%)</strong> dibanding periode lalu.` });

    document.getElementById('insight-list').innerHTML = insights.map(i =>
        `<li class="d-flex align-items-start gap-2 mb-2 small text-dark">
            <span class="insight-dot mt-1" style="background:${i.color};"></span>
            <span>${i.html}</span>
        </li>`
    ).join('');
});

// ── Main Line/Bar Chart ───────────────────────────────────────────
let mainChart;

const buildDatasets = type => chartRaw.datasets.map(ds => {
    const c = COLOR[ds.label] || '#999';
    return {
        label: ds.label, data: ds.data,
        backgroundColor: type === 'line' ? c + '28' : c + 'CC',
        borderColor: c, borderWidth: 2,
        fill: type === 'line', tension: 0.4,
        pointBackgroundColor: c, pointRadius: type === 'line' ? 3 : 0,
        borderRadius: type === 'bar' ? 5 : 0,
    };
});

function switchChart(type) {
    if (mainChart) mainChart.destroy();
    document.getElementById('btn-line').style.cssText = type==='line' ? 'background:#4338ca;color:#fff;border-color:#4338ca;' : '';
    document.getElementById('btn-bar').style.cssText  = type==='bar'  ? 'background:#4338ca;color:#fff;border-color:#4338ca;' : '';

    mainChart = new Chart(document.getElementById('pertumbuhanChart').getContext('2d'), {
        type,
        data: { labels: chartRaw.labels, datasets: buildDatasets(type) },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8, padding: 16 } },
                tooltip: { callbacks: { label: c => ' ' + c.dataset.label + ': +' + c.parsed.y.toLocaleString('id-ID') + ' baru' } }
            },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, border: { dash: [4,4] }, ticks: { callback: v => v.toLocaleString('id-ID') } }
            }
        }
    });
}

switchChart('bar');

// ── Donut Chart ───────────────────────────────────────────────────
new Chart(document.getElementById('donutChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: summaryRaw.map(d => d.kelas),
        datasets: [{
            data: summaryRaw.map(d => d.pct),
            backgroundColor: summaryRaw.map(d => COLOR[d.kelas] || '#999'),
            borderWidth: 3, borderColor: '#fff', hoverOffset: 6
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: true, cutout: '68%',
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    usePointStyle: true, boxWidth: 10, padding: 14, font: { size: 12 },
                    generateLabels(chart) {
                        return summaryRaw.map((d,i) => ({
                            text: `${d.kelas}  ${d.pct}%  (${fmt(d.total)})`,
                            fillStyle: COLOR[d.kelas] || '#999',
                            strokeStyle: COLOR[d.kelas] || '#999',
                            pointStyle: 'circle', index: i
                        }));
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${ctx.label}: ${ctx.parsed}% (${fmt(summaryRaw[ctx.dataIndex].total)})`
                }
            }
        }
    },
    plugins: [{
        id: 'donutCenter',
        beforeDraw(chart) {
            const { ctx, chartArea } = chart;
            if (!chartArea) return;
            const cx = (chartArea.left + chartArea.right) / 2;
            const cy = (chartArea.top  + chartArea.bottom) / 2;
            const dominant = summaryRaw.reduce((a, b) => parseFloat(a.pct) >= parseFloat(b.pct) ? a : b, summaryRaw[0]);
            if (!dominant) return;
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.font = 'bold 18px sans-serif';
            ctx.fillStyle = COLOR[dominant.kelas] || '#1e293b';
            ctx.fillText(dominant.pct + '%', cx, cy - 10);
            ctx.font = '500 12px sans-serif';
            ctx.fillStyle = '#64748b';
            ctx.fillText(dominant.kelas, cx, cy + 10);
            ctx.restore();
        }
    }]
});
</script>
@endsection
