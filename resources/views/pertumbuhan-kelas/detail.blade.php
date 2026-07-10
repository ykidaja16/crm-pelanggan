@extends('layouts.main')

@section('title', 'Detail Kelas ' . ($kelas ?? 'Semua') . ' — Pertumbuhan Kelas Pelanggan')

@section('content')
@php
    $kelasColors = [
        'Prioritas' => (object) ['bg' => '#7C3AED', 'light' => '#EDE9FE', 'text' => '#5B21B6'],
        'Loyal'     => (object) ['bg' => '#2563EB', 'light' => '#DBEAFE', 'text' => '#1D4ED8'],
        'Potensial' => (object) ['bg' => '#D97706', 'light' => '#FEF3C7', 'text' => '#B45309'],
        'Umum'      => (object) ['bg' => '#6B7280', 'light' => '#F3F4F6', 'text' => '#4B5563'],
    ];
    $c = $kelasColors[$kelas] ?? (object) ['bg' => '#2563EB', 'light' => '#DBEAFE', 'text' => '#1D4ED8'];
    $kelasLabel = $kelas ?? 'Semua Kelas';
@endphp

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <a href="{{ route('pertumbuhan-kelas.index', request()->except('kelas')) }}"
       class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Kembali
    </a>
    <h4 class="text-primary mb-0 fw-semibold">
        <i class="fas fa-table me-2"></i>Detail Pelanggan —
        <span class="badge ms-1" @style(['background: ' . $c->bg, 'font-size: 1rem'])>{{ $kelasLabel }}</span>
    </h4>
    <a href="{{ route('pertumbuhan-kelas.export-detail', request()->query()) }}"
       class="btn btn-outline-success btn-sm ms-auto">
        <i class="fas fa-file-excel me-1"></i>Export Excel
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-bottom py-2 d-flex align-items-center justify-content-between">
        <span class="fw-semibold small text-muted">
            Total: <strong>{{ number_format($pelangganList->total()) }}</strong> pelanggan
        </span>
        @if($dateFrom || $dateTo)
        <span class="badge bg-light text-dark border" style="font-size:0.75rem;">
            {{ $dateFrom ?? '—' }} s.d. {{ $dateTo ?? '—' }}
        </span>
        @endif
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:0.85rem;">
                <thead class="table-light">
                    <tr>
                        <th class="text-center">#</th>
                        <th>PID</th>
                        <th>Nama</th>
                        <th>Cabang</th>
                        <th>No. Telp</th>
                        <th class="text-center">Total Kunjungan</th>
                        <th class="text-center">Tgl Kunjungan Terakhir</th>
                        <th class="text-center">Total Biaya</th>
                        <th class="text-center">Kelas</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pelangganList as $idx => $p)
                    @php 
                        $pClass = $p->getAttribute('class');
                        $cp = $kelasColors[$pClass] ?? $c; 
                    @endphp
                    <tr>
                        <td class="text-center text-muted">{{ $pelangganList->firstItem() + $idx }}</td>
                        <td>
                            <a href="{{ route('pelanggan.show', $p->id) }}" class="text-decoration-none fw-semibold small">
                                {{ $p->pid }}
                            </a>
                        </td>
                        <td>{{ $p->nama }}</td>
                        <td class="text-muted small">{{ $p->cabang?->nama ?? '-' }}</td>
                        <td class="text-muted small">
                            @if($p->no_telp)
                                @php
                                    $waNum = $p->no_telp;
                                    if (str_starts_with($waNum, '0')) { $waNum = '62' . substr($waNum, 1); }
                                @endphp
                                <a href="https://api.whatsapp.com/send/?phone={{ $waNum }}&text&type=phone_number&app_absent=0" target="_blank" class="text-decoration-none" title="Chat WhatsApp">
                                    <i class="fab fa-whatsapp text-success me-1"></i>{{ $p->no_telp }}
                                </a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-center fw-semibold">{{ number_format($p->total_kedatangan) }}</td>
                        <td class="text-center text-muted small">
                            {{ $p->latestKunjungan?->tanggal_kunjungan
                                ? \Carbon\Carbon::parse($p->latestKunjungan->tanggal_kunjungan)->format('d-m-Y')
                                : '-' }}
                        </td>
                        <td class="text-end text-muted small">
                            {{ number_format($p->total_biaya, 0, ',', '.') }}
                        </td>
                        <td class="text-center">
                            <span class="badge" @style(['background: ' . $cp->bg, 'font-size: 0.72rem'])>
                                {{ $pClass ?? 'Umum' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="fas fa-inbox me-2"></i>Tidak ada data pelanggan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($pelangganList->hasPages())
    <div class="card-footer bg-white py-2">
        {{ $pelangganList->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
    @endif
</div>
@endsection
