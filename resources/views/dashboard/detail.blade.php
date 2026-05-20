@extends('layouts.main')

@section('title', $title . ' - Dashboard')

@section('content')
@php
    // Helper: buat URL sort — toggle asc/desc jika kolom sama, default asc jika beda kolom
    $sortUrl = fn(string $col) => request()->fullUrlWithQuery([
        'sort'      => $col,
        'direction' => ($sort === $col && $direction === 'asc') ? 'desc' : 'asc',
        'page'      => 1,
    ]);

    // Icon sort untuk header
    $sortIcon = function(string $col) use ($sort, $direction): string {
        if ($sort !== $col) return '<i class="fas fa-sort ms-1 text-dark opacity-50 small"></i>';
        return $direction === 'asc'
            ? '<i class="fas fa-sort-up ms-1 small"></i>'
            : '<i class="fas fa-sort-down ms-1 small"></i>';
    };
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="{{ route('dashboard') }}" class="text-decoration-none text-muted small">
            <i class="fas fa-arrow-left me-1"></i>Kembali ke Dashboard
        </a>
        <h3 class="text-primary mb-0 mt-1">{{ $title }}</h3>
        <small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i>{{ $cabangNama }}</small>
    </div>
    <a href="{{ route('dashboard.detail.export', ['type' => $type, 'cabang_id' => $cabangId]) }}"
       class="btn btn-success">
        <i class="fas fa-file-excel me-1"></i> Export Excel
    </a>
</div>

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span><i class="fas fa-table text-primary me-2"></i>Total: <strong>{{ number_format($pelanggan->total()) }}</strong> pelanggan</span>
        <span class="text-muted small">Hal {{ $pelanggan->currentPage() }} / {{ $pelanggan->lastPage() }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 small">
                <thead class="table-primary">
                    <tr>
                        <th class="text-center" style="width:45px">No</th>
                        <th><a href="{{ $sortUrl('pid') }}" class="text-dark text-decoration-none d-flex align-items-center">PID {!! $sortIcon('pid') !!}</a></th>
                        <th><a href="{{ $sortUrl('nama') }}" class="text-dark text-decoration-none d-flex align-items-center">Nama {!! $sortIcon('nama') !!}</a></th>
                        <th><a href="{{ $sortUrl('nik') }}" class="text-dark text-decoration-none d-flex align-items-center">NIK {!! $sortIcon('nik') !!}</a></th>
                        <th><a href="{{ $sortUrl('cabang') }}" class="text-dark text-decoration-none d-flex align-items-center">Cabang {!! $sortIcon('cabang') !!}</a></th>
                        <th><a href="{{ $sortUrl('no_telp') }}" class="text-dark text-decoration-none d-flex align-items-center">No. Telepon {!! $sortIcon('no_telp') !!}</a></th>
                        <th><a href="{{ $sortUrl('dob') }}" class="text-dark text-decoration-none d-flex align-items-center">DOB {!! $sortIcon('dob') !!}</a></th>
                        <th><a href="{{ $sortUrl('alamat') }}" class="text-dark text-decoration-none d-flex align-items-center">Alamat {!! $sortIcon('alamat') !!}</a></th>
                        <th class="text-center"><a href="{{ $sortUrl('kunjungan') }}" class="text-dark text-decoration-none d-flex align-items-center justify-content-center">
                            @if($type === 'kunjungan_bulan_kemarin') Jml Kunjungan Bulan Kemarin
                            @elseif($type === 'kunjungan_tahun_ini') Jml Kunjungan Tahun Ini
                            @else Jml Kunjungan @endif
                            {!! $sortIcon('kunjungan') !!}</a></th>
                        <th><a href="{{ $sortUrl('tgl_terakhir') }}" class="text-dark text-decoration-none d-flex align-items-center">Tgl Kunjungan Terakhir {!! $sortIcon('tgl_terakhir') !!}</a></th>
                        <th class="text-end"><a href="{{ $sortUrl('total_biaya') }}" class="text-dark text-decoration-none d-flex align-items-center justify-content-end">
                            @if($type === 'kunjungan_bulan_kemarin') Total Biaya Bulan Kemarin
                            @elseif($type === 'kunjungan_tahun_ini') Total Biaya Tahun Ini
                            @else Total Biaya @endif
                            {!! $sortIcon('total_biaya') !!}</a></th>
                        <th class="text-center"><a href="{{ $sortUrl('class') }}" class="text-dark text-decoration-none d-flex align-items-center justify-content-center">Kelas {!! $sortIcon('class') !!}</a></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pelanggan as $p)
                    <tr>
                        <td class="text-center text-muted">{{ ($pelanggan->currentPage()-1)*$pelanggan->perPage()+$loop->iteration }}</td>
                        <td><a href="{{ route('pelanggan.show', $p->id) }}" class="text-decoration-none fw-semibold">{{ $p->pid }}</a></td>
                        <td>{{ $p->nama }}</td>
                        <td>{{ $p->nik ?? '-' }}</td>
                        <td>{{ $p->cabang?->nama ?? '-' }}</td>
                        <td>{{ $p->no_telp ?? '-' }}</td>
                        <td>{{ $p->dob ? \Carbon\Carbon::parse($p->dob)->format('d-m-Y') : '-' }}</td>
                        <td>{{ $p->alamat ?? '-' }}</td>
                        <td class="text-center">{{ number_format(in_array($type, ['kunjungan_bulan_kemarin','kunjungan_tahun_ini']) ? ($p->kunjungan_periode ?? 0) : $p->total_kedatangan) }}</td>
                        <td>{{ $p->tgl_kunjungan_terakhir ? \Carbon\Carbon::parse($p->tgl_kunjungan_terakhir)->format('d-m-Y') : '-' }}</td>
                        <td class="text-end">{{ number_format(in_array($type, ['kunjungan_bulan_kemarin','kunjungan_tahun_ini']) ? ($p->biaya_periode ?? 0) : $p->total_biaya, 0, ',', '.') }}</td>
                        <td class="text-center">
                            @php $badge = match($p->class) {'Prioritas'=>'bg-danger','Loyal'=>'bg-success','Potensial'=>'bg-warning',default=>'bg-secondary'}; @endphp
                            <span class="badge {{ $badge }}">{{ $p->class ?? 'Umum' }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="12" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x d-block mb-2"></i>Tidak ada data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($pelanggan->hasPages())
    <div class="card-footer bg-white">{{ $pelanggan->links('pagination::bootstrap-5') }}</div>
    @endif
</div>
@endsection
