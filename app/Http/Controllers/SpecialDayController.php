<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Models\Cabang;
use App\Models\User;
use App\Exports\SpecialDayExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class SpecialDayController extends Controller
{
    /**
     * Tampilkan halaman Special Day Member dengan filter & pagination.
     */
    public function index(Request $request)
    {
        $filter   = $request->get('filter', 'birthday');
        $cabangId = $request->get('cabang_id');
        $kelas    = $request->get('kelas');

        // Filter dropdown cabang berdasarkan hak akses user
        /** @var User $user */
        $user = Auth::user();
        $accessibleCabangIds = $user->getAccessibleCabangIds();
        $cabangs = empty($accessibleCabangIds)
            ? Cabang::orderBy('nama')->get()
            : Cabang::whereIn('id', $accessibleCabangIds)->orderBy('nama')->get();

        $query = $this->buildQuery($filter, $cabangId, $kelas, $accessibleCabangIds);

        $pelanggans = $query->orderBy('nama')->paginate(20)->withQueryString();

        return view('special-day.index', compact('pelanggans', 'cabangs', 'filter'));
    }

    /**
     * Export hasil filter Special Day Member ke Excel.
     */
    public function export(Request $request)
    {
        $filter   = $request->get('filter', 'birthday');
        $cabangId = $request->get('cabang_id');
        $kelas    = $request->get('kelas');

        /** @var User $user */
        $user = Auth::user();
        $accessibleCabangIds = $user->getAccessibleCabangIds();

        $query      = $this->buildQuery($filter, $cabangId, $kelas, $accessibleCabangIds);
        $pelanggans = $query->orderBy('nama')->get();

        $filterLabel = match ($filter) {
            'birthday'       => 'Ulang-Tahun-Hari-Ini',
            'birthday_month' => 'Ulang-Tahun-Bulan-Ini',
            'anniversary'    => '1-Tahun-Kunjungan-Terakhir',
            default          => $filter,
        };

        $filename = 'special-day-' . $filterLabel . '-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new SpecialDayExport($pelanggans, $filter), $filename);
    }

    /**
     * Helper: bangun query berdasarkan filter tipe, cabang, kelas, dan hak akses.
     */
    private function buildQuery(
        string  $filter,
        ?string $cabangId,
        ?string $kelas,
        array   $accessibleCabangIds
    ) {
        $query = Pelanggan::with(['cabang', 'latestKunjungan.kelompokPelanggan'])
            ->withMax('kunjungans', 'tanggal_kunjungan');

        // ── Filter tipe ──────────────────────────────────────────────────────
        if ($filter === 'birthday') {
            // Pelanggan yang ulang tahun hari ini
            $query->whereNotNull('dob')
                  ->whereMonth('dob', now()->month)
                  ->whereDay('dob', now()->day);
        } elseif ($filter === 'birthday_month') {
            // Pelanggan yang ulang tahun bulan ini
            $query->whereNotNull('dob')
                  ->whereMonth('dob', now()->month);
        } elseif ($filter === 'anniversary') {
            // Pelanggan yang kunjungan terakhirnya tepat 1 tahun yang lalu
            $anniversaryDate = now()->subYear()->format('Y-m-d');
            $query->whereRaw(
                '(SELECT DATE(MAX(tanggal_kunjungan)) FROM kunjungans WHERE kunjungans.pelanggan_id = pelanggans.id) = ?',
                [$anniversaryDate]
            );
        }

        // ── Filter hak akses cabang ──────────────────────────────────────────
        if (!empty($accessibleCabangIds)) {
            if ($cabangId && in_array((int) $cabangId, $accessibleCabangIds)) {
                $query->where('cabang_id', $cabangId);
            } else {
                $query->whereIn('cabang_id', $accessibleCabangIds);
            }
        } elseif ($cabangId) {
            $query->where('cabang_id', $cabangId);
        }

        // ── Filter Kelas ─────────────────────────────────────────────────────
        if ($kelas) {
            $query->where('class', $kelas);
        }

        return $query;
    }
}
