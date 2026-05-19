@extends('layouts.main')

@section('title', 'Dashboard - Medical Lab CRM')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-primary mb-0">Dashboard Utama</h3>
            @if(!$isMultiCabang && $cabangNama)
                <small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i>Cabang: <strong>{{ $cabangNama }}</strong></small>
            @endif
        </div>
    </div>

    @if($isMultiCabang)
        {{-- ===== MULTI-CABANG: Tab per cabang ===== --}}
        <div class="card mb-4">
            <div class="card-header bg-white p-0">
                <ul class="nav nav-tabs border-0 flex-wrap" id="cabangTabs" role="tablist">
                    @foreach($perCabangStats as $cabId => $cab)
                    @php $isActiveTab = ($activeCabang === $cabId); @endphp
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $isActiveTab ? 'active' : '' }} rounded-0"
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
                                <div class="card {{ $card['bg'] }} text-white h-100">
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-center flex-grow-1">
                                            <div>
                                                <h6 class="mb-0">{{ $card['label'] }}</h6>
                                                <h3 class="mb-0">{{ number_format($card['value']) }}</h3>
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
                                <div class="card {{ $card['bg'] }} text-white h-100">
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-center flex-grow-1">
                                            <div>
                                                <h6 class="mb-0">{{ $card['label'] }}</h6>
                                                <h3 class="mb-0">{{ number_format($card['value']) }}</h3>
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
        {{-- ===== SINGLE CABANG: layout asli ===== --}}
        @php $sc = $singleCabangId; @endphp

        <!-- Statistik Cards -->
        <div class="row mb-4 g-3">
            @foreach([
                ['bg'=>'bg-primary','icon'=>'fa-users','label'=>'Total Pelanggan','value'=>$totalPelanggan,'type'=>'total'],
                ['bg'=>'bg-secondary','icon'=>'fa-calendar-minus','label'=>'Kunjungan Bulan Kemarin','value'=>$totalKunjunganBulanKemarin,'type'=>'kunjungan_bulan_kemarin'],
                ['bg'=>'bg-info','icon'=>'fa-chart-line','label'=>'Kunjungan Tahun Ini','value'=>$totalKunjunganTahunIni,'type'=>'kunjungan_tahun_ini'],
                ['bg'=>'bg-warning','icon'=>'fa-user-plus','label'=>'Pelanggan Baru Bulan Kemarin','value'=>$pelangganBaruBulanKemarin,'type'=>'pelanggan_baru_bulan_kemarin'],
            ] as $card)
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card {{ $card['bg'] }} text-white h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center flex-grow-1">
                            <div>
                                <h6 class="mb-0">{{ $card['label'] }}</h6>
                                <h3 class="mb-0">{{ number_format($card['value']) }}</h3>
                            </div>
                            <i class="fas {{ $card['icon'] }} fa-2x opacity-50"></i>
                        </div>
                        <div class="mt-2 pt-1 border-top border-white border-opacity-25">
                            <a href="{{ route('dashboard.detail', ['type'=>$card['type'],'cabang_id'=>$sc]) }}" class="text-white text-decoration-none small opacity-75"><i class="fas fa-eye me-1"></i>Lihat Detail</a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Statistik Per Klasifikasi -->
        <div class="row mb-4 g-3">
            @foreach([
                ['bg'=>'bg-danger','icon'=>'fa-crown','label'=>'Total Pelanggan Prioritas','value'=>$totalPelangganPrioritas,'type'=>'prioritas'],
                ['bg'=>'bg-success','icon'=>'fa-heart','label'=>'Total Pelanggan Loyal','value'=>$totalPelangganLoyal,'type'=>'loyal'],
                ['bg'=>'bg-warning','icon'=>'fa-star','label'=>'Total Pelanggan Potensial','value'=>$totalPelangganPotensial,'type'=>'potensial'],
                ['bg'=>'bg-secondary','icon'=>'fa-user','label'=>'Total Pelanggan Umum','value'=>$totalPelangganUmum,'type'=>'umum'],
            ] as $card)
            <div class="col-12 col-sm-6 col-md-3">
                <div class="card {{ $card['bg'] }} text-white h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center flex-grow-1">
                            <div>
                                <h6 class="mb-0">{{ $card['label'] }}</h6>
                                <h3 class="mb-0">{{ number_format($card['value']) }}</h3>
                            </div>
                            <i class="fas {{ $card['icon'] }} fa-2x opacity-50"></i>
                        </div>
                        <div class="mt-2 pt-1 border-top border-white border-opacity-25">
                            <a href="{{ route('dashboard.detail', ['type'=>$card['type'],'cabang_id'=>$sc]) }}" class="text-white text-decoration-none small opacity-75"><i class="fas fa-eye me-1"></i>Lihat Detail</a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

    @endif

    <!-- Filter Grafik -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <i class="fas fa-filter text-primary me-2"></i> Filter Grafik
        </div>
        <div class="card-body">
            <form method="GET" class="row align-items-end" id="filterForm">
                @if($isMultiCabang)
                <input type="hidden" name="active_cab" id="activeTabInput" value="{{ $activeCabang }}">
                @endif
                <div class="col-md-3">
                    <label class="form-label">Tipe Filter</label>
                    <select name="filter_type" class="form-select" onchange="this.form.submit()">
                        <option value="monthly" {{ $filterType == 'monthly' ? 'selected' : '' }}>Per Bulan</option>
                        <option value="yearly" {{ $filterType == 'yearly' ? 'selected' : '' }}>Per Tahun</option>
                        <option value="class" {{ $filterType == 'class' ? 'selected' : '' }}>Per Klasifikasi</option>
                    </select>
                </div>

                @if($filterType == 'monthly')
                <div class="col-md-3">
                    <label class="form-label">Tahun</label>
                    <select name="year" class="form-select" onchange="this.form.submit()">
                        @for($i = date('Y') - 5; $i <= date('Y') + 1; $i++)
                            <option value="{{ $i }}" {{ $year == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                @endif

                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Grafik -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-chart-bar text-primary me-2"></i> {{ $chartTitle }}</h5>
        </div>
        <div class="card-body">
            <canvas id="growthChart" height="200"></canvas>
        </div>
    </div>
@endsection

@section('scripts')
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
<script>
    const ctx = document.getElementById('growthChart').getContext('2d');

    // Data dari PHP
    const labels = @json($chartLabels);
    const data = @json($chartData);
    const filterType = @json($filterType);

    // Warna berdasarkan tipe filter
    let backgroundColors, borderColors;

    if (filterType === 'class') {
        backgroundColors = [
            'rgba(255, 0, 0, 0.8)',      // Prioritas - merah
            'rgba(40, 167, 69, 0.8)',    // Loyal - hijau
            'rgba(255, 193, 7, 0.8)',    // Potensial - kuning
            'rgba(108, 117, 125, 0.8)'   // Umum - abu-abu
        ];
        borderColors = [
            'rgba(255, 0, 0, 1)',
            'rgba(40, 167, 69, 1)',
            'rgba(255, 193, 7, 1)',
            'rgba(108, 117, 125, 1)'
        ];
    } else {
        backgroundColors = 'rgba(0, 168, 204, 0.6)';
        borderColors = 'rgba(0, 168, 204, 1)';
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
</script>
@endsection
