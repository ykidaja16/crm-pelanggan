@extends('layouts.main')

@section('title', $title . ' - Dashboard')

@section('content')
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
                        <th>PID</th>
                        <th>Nama</th>
                        <th>NIK</th>
                        <th>Cabang</th>
                        <th>No. Telepon</th>
                        <th>DOB</th>
                        <th>Alamat</th>
                        <th class="text-center">Jml Kunjungan</th>
                        <th>Tgl Kunjungan Terakhir</th>
                        <th class="text-end">Total Biaya</th>
                        <th class="text-center">Kelas</th>
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
                        <td class="text-center">{{ number_format($p->total_kedatangan) }}</td>
                        <td>{{ $p->tgl_kunjungan_terakhir ? \Carbon\Carbon::parse($p->tgl_kunjungan_terakhir)->format('d-m-Y') : '-' }}</td>
                        <td class="text-end">{{ number_format($p->total_biaya, 0, ',', '.') }}</td>
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
