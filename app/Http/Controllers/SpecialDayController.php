<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Models\Cabang;
use App\Models\User;
use App\Exports\SpecialDayExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class SpecialDayController extends Controller
{
    /**
     * Submenu 1: Birthday Reminder
     * Filter: cabang, kelas, range tanggal (cari pelanggan yang ulang tahun di range tsb)
     */
    public function birthday(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $accessibleCabangIds = $user->getAccessibleCabangIds();
        $cabangs = empty($accessibleCabangIds)
            ? Cabang::orderBy('nama')->get()
            : Cabang::whereIn('id', $accessibleCabangIds)->orderBy('nama')->get();

        $cabangId   = $request->get('cabang_id');
        $kelas      = $request->get('kelas');
        $tglMulai   = $request->get('tgl_mulai', now()->format('Y-m-d'));
        $tglAkhir   = $request->get('tgl_akhir', now()->format('Y-m-d'));

        $query = $this->buildBirthdayQuery($cabangId, $kelas, $tglMulai, $tglAkhir, $accessibleCabangIds);
        $pelanggans = $query->orderBy('nama')->paginate(20)->withQueryString();

        return view('special-day.birthday', compact('pelanggans', 'cabangs', 'tglMulai', 'tglAkhir'));
    }

    /**
     * Export Birthday Reminder ke Excel
     */
    public function birthdayExport(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $accessibleCabangIds = $user->getAccessibleCabangIds();

        $cabangId = $request->get('cabang_id');
        $kelas    = $request->get('kelas');
        $tglMulai = $request->get('tgl_mulai', now()->format('Y-m-d'));
        $tglAkhir = $request->get('tgl_akhir', now()->format('Y-m-d'));

        $query      = $this->buildBirthdayQuery($cabangId, $kelas, $tglMulai, $tglAkhir, $accessibleCabangIds);
        $pelanggans = $query->orderBy('nama')->get();

        $filename = 'birthday-reminder-' . $tglMulai . '-sd-' . $tglAkhir . '.xlsx';
        return Excel::download(new SpecialDayExport($pelanggans, 'birthday_range', $tglMulai, $tglAkhir), $filename);
    }

    /**
     * Submenu 2: Kunjungan Terakhir
     * Filter: cabang, kelas, range tanggal
     * Logika: tampilkan pelanggan yang kunjungan terakhirnya jatuh 1 tahun sebelum range yang dipilih
     * Contoh: pilih Maret 2025 → tampilkan yang kunjungan terakhir di Maret 2024
     */
    public function kunjunganTerakhir(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $accessibleCabangIds = $user->getAccessibleCabangIds();
        $cabangs = empty($accessibleCabangIds)
            ? Cabang::orderBy('nama')->get()
            : Cabang::whereIn('id', $accessibleCabangIds)->orderBy('nama')->get();

        $cabangId = $request->get('cabang_id');
        $kelas    = $request->get('kelas');
        $tglMulai = $request->get('tgl_mulai', now()->format('Y-m-d'));
        $tglAkhir = $request->get('tgl_akhir', now()->format('Y-m-d'));

        $query = $this->buildKunjunganTerakhirQuery($cabangId, $kelas, $tglMulai, $tglAkhir, $accessibleCabangIds);
        $pelanggans = $query->orderBy('nama')->paginate(20)->withQueryString();

        return view('special-day.kunjungan-terakhir', compact('pelanggans', 'cabangs', 'tglMulai', 'tglAkhir'));
    }

    /**
     * Export Kunjungan Terakhir ke Excel
     */
    public function kunjunganTerakhirExport(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $accessibleCabangIds = $user->getAccessibleCabangIds();

        $cabangId = $request->get('cabang_id');
        $kelas    = $request->get('kelas');
        $tglMulai = $request->get('tgl_mulai', now()->format('Y-m-d'));
        $tglAkhir = $request->get('tgl_akhir', now()->format('Y-m-d'));

        $query      = $this->buildKunjunganTerakhirQuery($cabangId, $kelas, $tglMulai, $tglAkhir, $accessibleCabangIds);
        $pelanggans = $query->orderBy('nama')->get();

        $filename = 'kunjungan-terakhir-' . $tglMulai . '-sd-' . $tglAkhir . '.xlsx';
        return Excel::download(new SpecialDayExport($pelanggans, 'kunjungan_terakhir', $tglMulai, $tglAkhir), $filename);
    }

    /**
     * Helper: query Birthday Reminder
     * Cari pelanggan yang tanggal lahir (bulan-hari) jatuh dalam range tanggal yang dipilih
     */
    private function buildBirthdayQuery(
        ?string $cabangId,
        ?string $kelas,
        string  $tglMulai,
        string  $tglAkhir,
        array   $accessibleCabangIds
    ) {
        $query = Pelanggan::with(['cabang', 'latestKunjungan.kelompokPelanggan'])
            ->withMax('kunjungans', 'tanggal_kunjungan')
            ->whereNotNull('dob');

        $start = Carbon::parse($tglMulai);
        $end   = Carbon::parse($tglAkhir);

        // Jika range dalam tahun yang sama (tidak melewati tahun baru)
        if ($start->month <= $end->month || ($start->month === $end->month && $start->day <= $end->day)) {
            // Cek apakah range melewati batas bulan
            if ($start->month === $end->month) {
                // Dalam bulan yang sama
                $query->whereMonth('dob', $start->month)
                      ->whereDay('dob', '>=', $start->day)
                      ->whereDay('dob', '<=', $end->day);
            } else {
                // Span beberapa bulan, gunakan DAY_OF_YEAR comparison
                $query->whereRaw(
                    'DATE_FORMAT(dob, "%m-%d") >= ? AND DATE_FORMAT(dob, "%m-%d") <= ?',
                    [$start->format('m-d'), $end->format('m-d')]
                );
            }
        } else {
            // Range melewati tahun baru (misal Nov - Feb)
            $query->where(function ($q) use ($start, $end) {
                $q->whereRaw('DATE_FORMAT(dob, "%m-%d") >= ?', [$start->format('m-d')])
                  ->orWhereRaw('DATE_FORMAT(dob, "%m-%d") <= ?', [$end->format('m-d')]);
            });
        }

        $this->applyAccessFilter($query, $cabangId, $kelas, $accessibleCabangIds);
        return $query;
    }

    /**
     * Helper: query Kunjungan Terakhir
     * Cari pelanggan yang kunjungan terakhirnya jatuh 1 tahun sebelum range yang dipilih
     */
    private function buildKunjunganTerakhirQuery(
        ?string $cabangId,
        ?string $kelas,
        string  $tglMulai,
        string  $tglAkhir,
        array   $accessibleCabangIds
    ) {
        // Geser range 1 tahun ke belakang
        $refStart = Carbon::parse($tglMulai)->subYear()->format('Y-m-d');
        $refEnd   = Carbon::parse($tglAkhir)->subYear()->format('Y-m-d');

        $query = Pelanggan::with(['cabang', 'latestKunjungan.kelompokPelanggan'])
            ->withMax('kunjungans', 'tanggal_kunjungan')
            ->whereRaw(
                '(SELECT DATE(MAX(tanggal_kunjungan)) FROM kunjungans WHERE kunjungans.pelanggan_id = pelanggans.id) BETWEEN ? AND ?',
                [$refStart, $refEnd]
            );

        $this->applyAccessFilter($query, $cabangId, $kelas, $accessibleCabangIds);
        return $query;
    }

    /**
     * Helper: terapkan filter cabang dan kelas
     */
    private function applyAccessFilter($query, ?string $cabangId, ?string $kelas, array $accessibleCabangIds): void
    {
        if (!empty($accessibleCabangIds)) {
            if ($cabangId && in_array((int) $cabangId, $accessibleCabangIds)) {
                $query->where('cabang_id', $cabangId);
            } else {
                $query->whereIn('cabang_id', $accessibleCabangIds);
            }
        } elseif ($cabangId) {
            $query->where('cabang_id', $cabangId);
        }

        if ($kelas) {
            $query->where('class', $kelas);
        }
    }

    // ── Legacy method (kept for backward compatibility) ──────────────────────

    /**
     * @deprecated Gunakan birthday() atau kunjunganTerakhir()
     */
    public function index(Request $request)
    {
        return redirect()->route('special-day.birthday');
    }

    /**
     * @deprecated Gunakan birthdayExport() atau kunjunganTerakhirExport()
     */
    public function export(Request $request)
    {
        return redirect()->route('special-day.birthday');
    }
}
