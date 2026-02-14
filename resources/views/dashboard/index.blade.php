@extends('layouts.main')

@section('title', 'Dashboard - Medical Lab CRM')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-primary mb-0">Dashboard</h3>
    </div>

    <!-- Statistik Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Total Pelanggan</h6>
                            <h3 class="mb-0">{{ number_format($totalPelanggan) }}</h3>
                        </div>
                        <i class="fas fa-users fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Kunjungan Bulan Ini</h6>
                            <h3 class="mb-0">{{ number_format($totalKunjunganBulanIni) }}</h3>
                        </div>
                        <i class="fas fa-calendar-check fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Kunjungan Tahun Ini</h6>
                            <h3 class="mb-0">{{ number_format($totalKunjunganTahunIni) }}</h3>
                        </div>
                        <i class="fas fa-chart-line fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Pelanggan Baru Bulan Ini</h6>
                            <h3 class="mb-0">{{ number_format($pelangganBaruBulanIni) }}</h3>
                        </div>
                        <i class="fas fa-user-plus fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Grafik -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <i class="fas fa-filter text-primary me-2"></i> Filter Grafik
        </div>
        <div class="card-body">
            <form method="GET" class="row align-items-end">
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
            <canvas id="growthChart" height="100"></canvas>
        </div>
    </div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            'rgba(33, 37, 41, 0.8)',   // Platinum - dark
            'rgba(255, 193, 7, 0.8)',   // Gold - yellow
            'rgba(108, 117, 125, 0.8)', // Silver - gray
            'rgba(248, 249, 250, 0.8)'  // Basic - light
        ];
        borderColors = [
            'rgba(33, 37, 41, 1)',
            'rgba(255, 193, 7, 1)',
            'rgba(108, 117, 125, 1)',
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
