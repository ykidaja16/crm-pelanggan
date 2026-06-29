@extends('layouts.main')

@section('title', 'Sinkronisasi Data Pelanggan')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-semibold text-primary">
                        <i class="fas fa-sync-alt me-2"></i>Sinkronisasi Data Pelanggan
                    </h5>
                </div>
                <div class="card-body p-4">

                    <div class="alert alert-info border-0 rounded-3 mb-4">
                        <div class="d-flex align-items-start gap-3">
                            <i class="fas fa-info-circle fa-lg mt-1 text-info"></i>
                            <div>
                                <p class="mb-2 fw-semibold">Tentang Sinkronisasi Kelas Pelanggan</p>
                                <p class="mb-1 small">Menu ini digunakan untuk menyesuaikan kelas pelanggan yang <strong>sudah tidak berkunjung kembali dalam jangka waktu 2 (dua) tahun</strong> terhitung dari tanggal kunjungan terakhir mereka.</p>
                                <p class="mb-0 small">Aturan penurunan kelas yang diterapkan:</p>
                                <ul class="mb-0 small mt-1">
                                    <li><span class="badge bg-danger bg-opacity-10 text-danger border border-danger">Prioritas</span> &rarr; diturunkan menjadi <span class="badge bg-success bg-opacity-10 text-success border border-success">Loyal</span></li>
                                    <li><span class="badge bg-success bg-opacity-10 text-success border border-success">Loyal</span> &rarr; diturunkan menjadi <span class="badge bg-warning bg-opacity-10 text-warning border border-warning">Potensial</span></li>
                                    <li><span class="badge bg-warning bg-opacity-10 text-warning border border-warning">Potensial</span> &rarr; <strong>tidak berubah</strong></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning border-0 rounded-3 mb-4">
                        <div class="d-flex align-items-start gap-3">
                            <i class="fas fa-exclamation-triangle fa-lg mt-1 text-warning"></i>
                            <div class="small">
                                <p class="mb-1 fw-semibold">Catatan Penting</p>
                                <ul class="mb-0">
                                    <li>Setiap perubahan kelas akan tercatat di <strong>Riwayat Perubahan Kelas</strong> masing-masing pelanggan dengan flag <em>Sinkronisasi</em>.</li>
                                    <li>Jika pelanggan yang terdampak Sinkronisasi kemudian tercatat kunjungan baru (melalui import atau input data), kelas mereka akan <strong>otomatis dipulihkan</strong> ke kelas sebelum Sinkronisasi.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center">
                        <button type="button" class="btn btn-primary px-5 py-2"
                                onclick="konfirmasiSinkronisasi()">
                            <i class="fas fa-sync-alt me-2"></i>Synchronize
                        </button>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

{{-- Modal Konfirmasi --}}
<div class="modal fade" id="modalKonfirmasi" tabindex="-1" aria-labelledby="modalKonfirmasiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold" id="modalKonfirmasiLabel">
                    <i class="fas fa-sync-alt me-2 text-primary"></i>Konfirmasi Sinkronisasi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Apakah Anda yakin ingin menjalankan <strong>Sinkronisasi Kelas Pelanggan</strong>?</p>
                <p class="text-muted small mb-0">Proses ini akan menurunkan kelas semua pelanggan yang tidak berkunjung selama 2 tahun atau lebih (Prioritas → Loyal, Loyal → Potensial). Tindakan ini tidak dapat dibatalkan secara langsung.</p>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Tidak
                </button>
                <form action="{{ route('pelanggan.sinkronisasi.run') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sync-alt me-1"></i>Ya, Jalankan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function konfirmasiSinkronisasi() {
    var modal = new bootstrap.Modal(document.getElementById('modalKonfirmasi'));
    modal.show();
}
</script>
@endsection
