<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Cabang;
use App\Models\Kelas;
use App\Models\Pelanggan;
use App\Exports\DashboardDetailExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user        = $request->user();
        $cabangIds   = $user->getAccessibleCabangIds();
        $isAllAccess = empty($cabangIds); // IT/admin: no restriction

        // Cabangs that are accessible
        $cabangs = $isAllAccess
            ? Cabang::orderBy('nama')->get()
            : Cabang::whereIn('id', $cabangIds)->orderBy('nama')->get();

        $isMultiCabang   = $cabangs->count() > 1;
        $filterCabangIds = $isAllAccess ? [] : $cabangIds; // empty = no WHERE IN

        $filterType   = $request->filter_type ?? 'monthly';
        $year         = $request->year ?? date('Y');
        $month        = $request->month ?? null;
        $activeCabang = $request->integer('active_cab', 0);

        // Tentukan cabang aktif untuk grafik:
        // multi-cabang → gunakan tab yang sedang aktif; single/admin → pakai filterCabangIds
        if ($isMultiCabang) {
            $activeCabangId   = $activeCabang > 0 ? $activeCabang : ($cabangs->first()?->id ?? 0);
            $chartFilter      = $activeCabangId > 0 ? [$activeCabangId] : [];
            $activeCabangNama = $cabangs->firstWhere('id', $activeCabangId)?->nama ?? '';
        } else {
            $activeCabangId   = 0;
            $chartFilter      = $filterCabangIds;
            $activeCabangNama = '';
        }

        // Chart data — filtered by cabang aktif
        $chartData   = [];
        $chartLabels = [];
        $chartTitle  = '';

        if ($filterType === 'monthly') {
            $suffix     = $isMultiCabang ? " - {$activeCabangNama}" : '';
            $chartTitle = 'Pertumbuhan Pasien per Bulan - Tahun ' . $year . $suffix;

            $monthlyCounts = DB::table('kunjungans')
                ->select(DB::raw('MONTH(tanggal_kunjungan) as bulan'), DB::raw('COUNT(DISTINCT pelanggan_id) as total'))
                ->whereYear('tanggal_kunjungan', $year)
                ->when(!empty($chartFilter), fn($q) => $q->whereIn('cabang_id', $chartFilter))
                ->groupBy(DB::raw('MONTH(tanggal_kunjungan)'))
                ->pluck('total', 'bulan');

            for ($i = 1; $i <= 12; $i++) {
                $chartLabels[] = Carbon::create()->month($i)->format('F');
                $chartData[]   = (int) ($monthlyCounts[$i] ?? 0);
            }

        } elseif ($filterType === 'yearly') {
            $suffix      = $isMultiCabang ? " - {$activeCabangNama}" : '';
            $chartTitle  = 'Pertumbuhan Pasien per Tahun' . $suffix;
            $currentYear = (int) date('Y');
            $startYear   = $currentYear - 4;

            $yearlyCounts = DB::table('kunjungans')
                ->select(DB::raw('YEAR(tanggal_kunjungan) as tahun'), DB::raw('COUNT(DISTINCT pelanggan_id) as total'))
                ->whereBetween(DB::raw('YEAR(tanggal_kunjungan)'), [$startYear, $currentYear])
                ->when(!empty($chartFilter), fn($q) => $q->whereIn('cabang_id', $chartFilter))
                ->groupBy(DB::raw('YEAR(tanggal_kunjungan)'))
                ->pluck('total', 'tahun');

            for ($i = $startYear; $i <= $currentYear; $i++) {
                $chartLabels[] = $i;
                $chartData[]   = (int) ($yearlyCounts[$i] ?? 0);
            }

        } elseif ($filterType === 'class') {
            $suffix     = $isMultiCabang ? " - {$activeCabangNama}" : '';
            $chartTitle = 'Jumlah Pasien per Klasifikasi' . $suffix;

            $classCounts = Pelanggan::select('class', DB::raw('COUNT(*) as total'))
                ->when(!empty($chartFilter), fn($q) => $q->whereIn('cabang_id', $chartFilter))
                ->groupBy('class')
                ->pluck('total', 'class');

            $classes = Kelas::orderedNames()->toArray();
            foreach ($classes as $class) {
                $chartLabels[] = $class;
                $chartData[]   = (int) ($classCounts[$class] ?? 0);
            }
        }

        // Per-cabang stats — cached per access level (5 menit)
        $sortedIds = $cabangIds;
        sort($sortedIds);
        $cacheKey = 'dashboard_stats_' . ($isAllAccess ? 'all' : implode('_', $sortedIds));

        $perCabangStats = Cache::remember($cacheKey, 300, function () use ($cabangs, $filterCabangIds) {
            $hasFilter       = !empty($filterCabangIds);
            $now             = Carbon::now();
            $thisMonth       = $now->month;
            $thisYear        = $now->year;
            $firstDay        = $now->copy()->startOfMonth()->toDateString();
            $lastMonth       = $now->copy()->subMonth();
            $lastMonthNumber = $lastMonth->month;
            $lastMonthYear   = $lastMonth->year;

            // Total pelanggan per cabang — 1 query GROUP BY
            $totalPerCabang = Pelanggan::selectRaw('cabang_id, COUNT(*) as total')
                ->when($hasFilter, fn($q) => $q->whereIn('cabang_id', $filterCabangIds))
                ->groupBy('cabang_id')
                ->pluck('total', 'cabang_id');

            // Class breakdown per cabang — 1 query GROUP BY
            $classPerCabang = Pelanggan::selectRaw('cabang_id, class, COUNT(*) as total')
                ->when($hasFilter, fn($q) => $q->whereIn('cabang_id', $filterCabangIds))
                ->groupBy('cabang_id', 'class')
                ->get()
                ->groupBy('cabang_id');

            // Kunjungan aggregates per cabang — 1 query CASE WHEN
            $kunjunganPerCabang = DB::table('kunjungans')
                ->selectRaw('
                    cabang_id,
                    SUM(CASE WHEN MONTH(tanggal_kunjungan) = ? AND YEAR(tanggal_kunjungan) = ? THEN 1 ELSE 0 END) as bulan_ini,
                    SUM(CASE WHEN MONTH(tanggal_kunjungan) = ? AND YEAR(tanggal_kunjungan) = ? THEN 1 ELSE 0 END) as bulan_kemarin,
                    SUM(CASE WHEN YEAR(tanggal_kunjungan) = ? THEN 1 ELSE 0 END) as tahun_ini
                ', [$thisMonth, $thisYear, $lastMonthNumber, $lastMonthYear, $thisYear])
                ->when($hasFilter, fn($q) => $q->whereIn('cabang_id', $filterCabangIds))
                ->groupBy('cabang_id')
                ->get()
                ->keyBy('cabang_id');

            // Pelanggan baru bulan kemarin per cabang (kunjungan pertama ada di bulan kemarin)
            $firstDayLastMonth = $lastMonth->copy()->startOfMonth()->toDateString();
            $pelangganBaruPerCabang = DB::table('pelanggans')
                ->selectRaw('pelanggans.cabang_id, COUNT(*) as total')
                ->when($hasFilter, fn($q) => $q->whereIn('pelanggans.cabang_id', $filterCabangIds))
                ->whereExists(function ($q) use ($lastMonthNumber, $lastMonthYear) {
                    $q->from('kunjungans')
                      ->whereColumn('kunjungans.pelanggan_id', 'pelanggans.id')
                      ->whereMonth('tanggal_kunjungan', $lastMonthNumber)
                      ->whereYear('tanggal_kunjungan', $lastMonthYear);
                })
                ->whereNotExists(function ($q) use ($firstDayLastMonth) {
                    $q->from('kunjungans')
                      ->whereColumn('kunjungans.pelanggan_id', 'pelanggans.id')
                      ->where('tanggal_kunjungan', '<', $firstDayLastMonth);
                })
                ->groupBy('pelanggans.cabang_id')
                ->pluck('total', 'cabang_id');

            $result = [];
            foreach ($cabangs as $cabang) {
                $id        = $cabang->id;
                $classData = $classPerCabang->get($id, collect());
                $kunjungan = $kunjunganPerCabang->get($id);

                $result[$id] = [
                    'nama'                       => $cabang->nama,
                    'totalPelanggan'             => (int) ($totalPerCabang[$id] ?? 0),
                    'totalKunjunganBulanIni'     => (int) ($kunjungan?->bulan_ini ?? 0),
                    'totalKunjunganBulanKemarin' => (int) ($kunjungan?->bulan_kemarin ?? 0),
                    'totalKunjunganTahunIni'     => (int) ($kunjungan?->tahun_ini ?? 0),
                    'pelangganBaruBulanKemarin'  => (int) ($pelangganBaruPerCabang[$id] ?? 0),
                    'totalPelangganPrioritas'    => (int) ($classData->firstWhere('class', 'Prioritas')?->total ?? 0),
                    'totalPelangganLoyal'        => (int) ($classData->firstWhere('class', 'Loyal')?->total ?? 0),
                    'totalPelangganPotensial'    => (int) ($classData->firstWhere('class', 'Potensial')?->total ?? 0),
                    'totalPelangganUmum'         => (int) ($classData->firstWhere('class', 'Umum')?->total ?? 0),
                ];
            }

            return $result;
        });

        // Single cabang: extract values for view
        $singleStats = !$isMultiCabang && !empty($perCabangStats) ? reset($perCabangStats) : [];

        return view('dashboard.index', [
            'chartData'      => $chartData,
            'chartLabels'    => $chartLabels,
            'chartTitle'     => $chartTitle,
            'filterType'     => $filterType,
            'year'           => $year,
            'month'          => $month,
            'isMultiCabang'  => $isMultiCabang,
            'perCabangStats' => $perCabangStats,
            'activeCabang'   => $activeCabangId,
            // Single-cabang fallback (tetap backward compatible)
            'cabangNama'                  => $singleStats['nama'] ?? '',
            'totalPelanggan'              => $singleStats['totalPelanggan'] ?? 0,
            'totalKunjunganBulanIni'      => $singleStats['totalKunjunganBulanIni'] ?? 0,
            'totalKunjunganBulanKemarin'  => $singleStats['totalKunjunganBulanKemarin'] ?? 0,
            'totalKunjunganTahunIni'      => $singleStats['totalKunjunganTahunIni'] ?? 0,
            'pelangganBaruBulanKemarin'   => $singleStats['pelangganBaruBulanKemarin'] ?? 0,
            'totalPelangganPrioritas'     => $singleStats['totalPelangganPrioritas'] ?? 0,
            'totalPelangganLoyal'         => $singleStats['totalPelangganLoyal'] ?? 0,
            'totalPelangganPotensial'     => $singleStats['totalPelangganPotensial'] ?? 0,
            'totalPelangganUmum'          => $singleStats['totalPelangganUmum'] ?? 0,
            'singleCabangId'              => !$isMultiCabang && !empty($perCabangStats) ? (int) array_key_first($perCabangStats) : 0,
        ]);
    }

    public function detail(Request $request)
    {
        ini_set('memory_limit', '2048M');
        $type     = $request->input('type', 'total');
        $cabangId = $request->integer('cabang_id', 0);

        /** @var \App\Models\User $user */
        $user                = $request->user();
        $accessibleCabangIds = $user->getAccessibleCabangIds();

        if ($cabangId && !empty($accessibleCabangIds) && !in_array($cabangId, $accessibleCabangIds)) {
            abort(403);
        }

        $query      = $this->buildDetailQuery($type, $cabangId, $accessibleCabangIds);
        $pelanggan  = $query->paginate(50)->withQueryString();
        $cabangNama = $cabangId ? (Cabang::find($cabangId)?->nama ?? 'Semua Cabang') : 'Semua Cabang';

        return view('dashboard.detail', [
            'pelanggan'  => $pelanggan,
            'title'      => $this->detailTitle($type),
            'type'       => $type,
            'cabangId'   => $cabangId,
            'cabangNama' => $cabangNama,
        ]);
    }

    public function export(Request $request)
    {
        ini_set('memory_limit', '2048M');
        $type     = $request->input('type', 'total');
        $cabangId = $request->integer('cabang_id', 0);

        /** @var \App\Models\User $user */
        $user                = $request->user();
        $accessibleCabangIds = $user->getAccessibleCabangIds();

        if ($cabangId && !empty($accessibleCabangIds) && !in_array($cabangId, $accessibleCabangIds)) {
            abort(403);
        }

        $query      = $this->buildDetailQuery($type, $cabangId, $accessibleCabangIds);
        $cabangNama = $cabangId ? (Cabang::find($cabangId)?->nama ?? 'Semua Cabang') : 'Semua Cabang';
        $filename   = 'detail_' . $type . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new DashboardDetailExport($query, $this->detailTitle($type), $cabangNama), $filename);
    }

    private function buildDetailQuery(string $type, int $cabangId, array $accessibleCabangIds)
    {
        $now               = Carbon::now();
        $lastMonth         = $now->copy()->subMonth();
        $lastMonthNum      = $lastMonth->month;
        $lastMonthYear     = $lastMonth->year;
        $firstDayLastMonth = $lastMonth->copy()->startOfMonth()->toDateString();
        $thisYear          = $now->year;

        $query = Pelanggan::with('cabang')
            ->select([
                'pelanggans.id', 'pelanggans.pid', 'pelanggans.nama',
                'pelanggans.nik', 'pelanggans.cabang_id', 'pelanggans.no_telp',
                'pelanggans.dob', 'pelanggans.alamat',
                'pelanggans.total_kedatangan', 'pelanggans.total_biaya', 'pelanggans.class',
            ])
            ->selectRaw('(SELECT MAX(tanggal_kunjungan) FROM kunjungans WHERE kunjungans.pelanggan_id = pelanggans.id) as tgl_kunjungan_terakhir');

        if ($cabangId) {
            $query->where('pelanggans.cabang_id', $cabangId);
        } elseif (!empty($accessibleCabangIds)) {
            $query->whereIn('pelanggans.cabang_id', $accessibleCabangIds);
        }

        switch ($type) {
            case 'kunjungan_bulan_kemarin':
                $query->whereHas('kunjungans', fn($q) =>
                    $q->whereMonth('tanggal_kunjungan', $lastMonthNum)
                      ->whereYear('tanggal_kunjungan', $lastMonthYear));
                break;
            case 'kunjungan_tahun_ini':
                $query->whereHas('kunjungans', fn($q) =>
                    $q->whereYear('tanggal_kunjungan', $thisYear));
                break;
            case 'pelanggan_baru_bulan_kemarin':
                $query->whereHas('kunjungans', fn($q) =>
                    $q->whereMonth('tanggal_kunjungan', $lastMonthNum)
                      ->whereYear('tanggal_kunjungan', $lastMonthYear))
                    ->whereDoesntHave('kunjungans', fn($q) =>
                        $q->where('tanggal_kunjungan', '<', $firstDayLastMonth));
                break;
            case 'prioritas': $query->where('class', 'Prioritas'); break;
            case 'loyal':     $query->where('class', 'Loyal');     break;
            case 'potensial': $query->where('class', 'Potensial'); break;
            case 'umum':      $query->where('class', 'Umum');      break;
        }

        return $query->orderBy('pelanggans.nama');
    }

    private function detailTitle(string $type): string
    {
        return match ($type) {
            'kunjungan_bulan_kemarin'      => 'Kunjungan Bulan Kemarin',
            'kunjungan_tahun_ini'          => 'Kunjungan Tahun Ini',
            'pelanggan_baru_bulan_kemarin' => 'Pelanggan Baru Bulan Kemarin',
            'prioritas'                    => 'Pelanggan Prioritas',
            'loyal'                        => 'Pelanggan Loyal',
            'potensial'                    => 'Pelanggan Potensial',
            'umum'                         => 'Pelanggan Umum',
            default                        => 'Total Pelanggan',
        };
    }
}
