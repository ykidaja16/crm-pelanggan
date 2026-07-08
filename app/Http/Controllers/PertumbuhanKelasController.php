<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Cabang;
use App\Models\Pelanggan;
use App\Exports\PertumbuhanKelasExport;
use App\Exports\PertumbuhanKelasDetailExport;
use Maatwebsite\Excel\Facades\Excel;

class PertumbuhanKelasController extends Controller
{
    private const KELAS_ORDER = ['Prioritas', 'Loyal', 'Potensial', 'Umum'];
    private const KELAS_COLORS = [
        'Prioritas' => '#7C3AED',
        'Loyal'     => '#2563EB',
        'Potensial' => '#D97706',
        'Umum'      => '#6B7280',
    ];

    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user                = Auth::user();
        $accessibleCabangIds = $user->getAccessibleCabangIds();
        $cabangs             = empty($accessibleCabangIds)
            ? Cabang::orderBy('nama')->get()
            : Cabang::whereIn('id', $accessibleCabangIds)->orderBy('nama')->get();

        // Filter params
        $filterType = $request->filter_type ?? 'monthly'; // monthly | yearly | range
        $year       = (int) ($request->year  ?? date('Y'));
        $month      = (int) ($request->month ?? date('m'));
        $dateFrom   = $request->date_from ?? null;
        $dateTo     = $request->date_to   ?? null;
        $cabangId   = $request->cabang_id  ? (int) $request->cabang_id : null;

        // Sanitize cabang
        if ($cabangId && !empty($accessibleCabangIds) && !in_array($cabangId, $accessibleCabangIds)) {
            $cabangId = null;
        }

        // Build date boundaries
        [$startStr, $endStr, $periodLabel] = $this->buildPeriod($filterType, $year, $month, $dateFrom, $dateTo);

        // Build chart data: jumlah pelanggan per kelas per periode
        $chartData   = $this->buildChartData($filterType, $year, $month, $dateFrom, $dateTo, $accessibleCabangIds, $cabangId);
        $summaryData = $this->buildSummaryData($startStr, $endStr, $accessibleCabangIds, $cabangId);

        return view('pertumbuhan-kelas.index', compact(
            'cabangs', 'cabangId',
            'filterType', 'year', 'month', 'dateFrom', 'dateTo',
            'periodLabel', 'chartData', 'summaryData',
        ));
    }

    public function detail(Request $request)
    {
        /** @var \App\Models\User $user */
        $user                = Auth::user();
        $accessibleCabangIds = $user->getAccessibleCabangIds();

        $kelas    = $request->kelas;
        $dateFrom = $request->date_from ?? null;
        $dateTo   = $request->date_to   ?? null;
        $cabangId = $request->cabang_id  ? (int) $request->cabang_id : null;

        if ($cabangId && !empty($accessibleCabangIds) && !in_array($cabangId, $accessibleCabangIds)) {
            $cabangId = null;
        }

        $query = Pelanggan::with('cabang')
            ->whereNull('deleted_at')
            ->when($kelas && $kelas !== 'all', fn($q) => $q->where('class', $kelas))
            ->when($dateFrom, fn($q) => $q->whereHas('kunjungans', fn($kq) => $kq->where('tanggal_kunjungan', '>=', $dateFrom)))
            ->when($dateTo,   fn($q) => $q->whereHas('kunjungans', fn($kq) => $kq->where('tanggal_kunjungan', '<=', $dateTo)))
            ->when($cabangId, fn($q) => $q->where('cabang_id', $cabangId))
            ->when(!$cabangId && !empty($accessibleCabangIds), fn($q) => $q->whereIn('cabang_id', $accessibleCabangIds))
            ->orderByRaw("FIELD(class, 'Prioritas','Loyal','Potensial','Umum')")
            ->orderBy('total_kedatangan', 'desc');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['data' => $query->paginate(25)->withQueryString()]);
        }

        $pelangganList = $query->paginate(25)->withQueryString();

        return view('pertumbuhan-kelas.detail', compact(
            'pelangganList', 'kelas', 'dateFrom', 'dateTo', 'cabangId'
        ));
    }

    public function exportRingkasan(Request $request)
    {
        /** @var \App\Models\User $user */
        $user                = Auth::user();
        $accessibleCabangIds = $user->getAccessibleCabangIds();

        $filterType = $request->filter_type ?? 'monthly';
        $year       = (int) ($request->year  ?? date('Y'));
        $month      = (int) ($request->month ?? date('m'));
        $dateFrom   = $request->date_from ?? null;
        $dateTo     = $request->date_to   ?? null;
        $cabangId   = $request->cabang_id  ? (int) $request->cabang_id : null;

        if ($cabangId && !empty($accessibleCabangIds) && !in_array($cabangId, $accessibleCabangIds)) {
            $cabangId = null;
        }

        [$startStr, $endStr, $periodLabel] = $this->buildPeriod($filterType, $year, $month, $dateFrom, $dateTo);
        $chartData   = $this->buildChartData($filterType, $year, $month, $dateFrom, $dateTo, $accessibleCabangIds, $cabangId);
        $summaryData = $this->buildSummaryData($startStr, $endStr, $accessibleCabangIds, $cabangId);

        $filename = "Pertumbuhan_Kelas_{$periodLabel}.xlsx";
        return Excel::download(new PertumbuhanKelasExport($chartData, $summaryData, $periodLabel), $filename);
    }

    public function exportDetail(Request $request)
    {
        /** @var \App\Models\User $user */
        $user                = Auth::user();
        $accessibleCabangIds = $user->getAccessibleCabangIds();

        $kelas    = $request->kelas;
        $dateFrom = $request->date_from ?? null;
        $dateTo   = $request->date_to   ?? null;
        $cabangId = $request->cabang_id  ? (int) $request->cabang_id : null;

        if ($cabangId && !empty($accessibleCabangIds) && !in_array($cabangId, $accessibleCabangIds)) {
            $cabangId = null;
        }

        $query = Pelanggan::with('cabang')
            ->whereNull('deleted_at')
            ->when($kelas && $kelas !== 'all', fn($q) => $q->where('class', $kelas))
            ->when($dateFrom, fn($q) => $q->whereHas('kunjungans', fn($kq) => $kq->where('tanggal_kunjungan', '>=', $dateFrom)))
            ->when($dateTo,   fn($q) => $q->whereHas('kunjungans', fn($kq) => $kq->where('tanggal_kunjungan', '<=', $dateTo)))
            ->when($cabangId, fn($q) => $q->where('cabang_id', $cabangId))
            ->when(!$cabangId && !empty($accessibleCabangIds), fn($q) => $q->whereIn('cabang_id', $accessibleCabangIds))
            ->orderByRaw("FIELD(class, 'Prioritas','Loyal','Potensial','Umum')")
            ->orderBy('total_kedatangan', 'desc');

        $kelasLabel = $kelas && $kelas !== 'all' ? $kelas : 'Semua Kelas';
        $filename   = "Detail_Kelas_{$kelasLabel}.xlsx";
        return Excel::download(new PertumbuhanKelasDetailExport($query, $kelasLabel), $filename);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function buildPeriod(string $filterType, int $year, int $month, ?string $dateFrom, ?string $dateTo): array
    {
        switch ($filterType) {
            case 'yearly':
                $start  = Carbon::create($year, 1, 1)->toDateString();
                $end    = Carbon::create($year, 12, 31)->toDateString();
                $label  = (string) $year;
                break;
            case 'range':
                // dateFrom/dateTo dalam format YYYY-MM (month picker)
                $startC = $dateFrom
                    ? Carbon::createFromFormat('Y-m', $dateFrom)->startOfMonth()
                    : Carbon::now()->subYear()->startOfMonth();
                $endC   = $dateTo
                    ? Carbon::createFromFormat('Y-m', $dateTo)->endOfMonth()
                    : Carbon::now()->endOfMonth();
                $start  = $startC->toDateString();
                $end    = $endC->toDateString();
                $label  = $startC->format('M Y') . ' s.d. ' . $endC->format('M Y');
                break;
            default: // monthly → tampil per bulan seluruh tahun
                $start  = Carbon::create($year, 1, 1)->startOfMonth()->toDateString();
                $end    = Carbon::create($year, 12, 31)->endOfMonth()->toDateString();
                $label  = (string) $year;
                break;
        }
        return [$start, $end, $label];
    }

    private function buildChartData(string $filterType, int $year, int $month, ?string $dateFrom, ?string $dateTo, array $accessibleCabangIds, ?int $cabangId): array
    {
        $kelas = self::KELAS_ORDER;

        if ($filterType === 'monthly') {
            // Per bulan dalam 1 tahun
            $labels   = [];
            $datasets = [];
            foreach ($kelas as $k) {
                $datasets[$k] = ['label' => $k, 'color' => self::KELAS_COLORS[$k], 'data' => []];
            }

            for ($m = 1; $m <= 12; $m++) {
                $startM = Carbon::create($year, $m, 1)->startOfMonth()->toDateString();
                $endM   = Carbon::create($year, $m, 1)->endOfMonth()->toDateString();
                $labels[] = Carbon::create($year, $m, 1)->format('M Y');

                $counts = $this->countNewByKelas($startM, $endM, $accessibleCabangIds, $cabangId);
                foreach ($kelas as $k) {
                    $datasets[$k]['data'][] = $counts[$k] ?? 0;
                }
            }
            return ['labels' => $labels, 'datasets' => array_values($datasets), 'type' => 'bar'];

        } elseif ($filterType === 'yearly') {
            // Per tahun, 5 tahun terakhir
            $currentYear = (int) date('Y');
            $startYear   = $currentYear - 4;
            $labels      = [];
            $datasets    = [];
            foreach ($kelas as $k) {
                $datasets[$k] = ['label' => $k, 'color' => self::KELAS_COLORS[$k], 'data' => []];
            }

            for ($y = $startYear; $y <= $currentYear; $y++) {
                $startY   = Carbon::create($y, 1, 1)->toDateString();
                $endY     = Carbon::create($y, 12, 31)->toDateString();
                $labels[] = (string) $y;
                $counts   = $this->countNewByKelas($startY, $endY, $accessibleCabangIds, $cabangId);
                foreach ($kelas as $k) {
                    $datasets[$k]['data'][] = $counts[$k] ?? 0;
                }
            }
            return ['labels' => $labels, 'datasets' => array_values($datasets), 'type' => 'bar'];

        } else {
            // Range Bulan: per bulan dari dateFrom sampai dateTo
            $startC = $dateFrom
                ? Carbon::createFromFormat('Y-m', $dateFrom)->startOfMonth()
                : Carbon::now()->subYear()->startOfMonth();
            $endC   = $dateTo
                ? Carbon::createFromFormat('Y-m', $dateTo)->endOfMonth()
                : Carbon::now()->endOfMonth();

            $labels   = [];
            $datasets = [];
            foreach ($kelas as $k) {
                $datasets[$k] = ['label' => $k, 'color' => self::KELAS_COLORS[$k], 'data' => []];
            }

            $cur = $startC->copy()->startOfMonth();
            while ($cur->lte($endC)) {
                $labels[]   = $cur->format('M Y');
                $curEnd     = $cur->copy()->endOfMonth();
                $counts     = $this->countNewByKelas($cur->toDateString(), $curEnd->toDateString(), $accessibleCabangIds, $cabangId);
                foreach ($kelas as $k) {
                    $datasets[$k]['data'][] = $counts[$k] ?? 0;
                }
                $cur->addMonth();
            }
            return ['labels' => $labels, 'datasets' => array_values($datasets), 'type' => 'bar'];
        }
    }

    /**
     * Hitung pelanggan yang AKTIF (punya kunjungan) dalam periode — digunakan untuk summaryData.
     */
    private function countByKelas(string $startStr, string $endStr, array $accessibleCabangIds, ?int $cabangId): array
    {
        $q = DB::table('pelanggans as p')
            ->whereNull('p.deleted_at')
            ->whereExists(fn($sub) => $sub->from('kunjungans as k')
                ->whereColumn('k.pelanggan_id', 'p.id')
                ->whereBetween('k.tanggal_kunjungan', [$startStr, $endStr]))
            ->selectRaw('p.class as kelas, COUNT(DISTINCT p.id) as jumlah')
            ->groupBy('p.class');

        if ($cabangId) {
            $q->where('p.cabang_id', $cabangId);
        } elseif (!empty($accessibleCabangIds)) {
            $q->whereIn('p.cabang_id', $accessibleCabangIds);
        }

        return $this->normalizeKelasResult($q->get());
    }

    /**
     * Hitung pelanggan BARU per kelas berdasarkan tanggal kunjungan pertama mereka.
     * Digunakan untuk grafik trend pertumbuhan — menampilkan pertambahan historis nyata,
     * bukan terpaku pada created_at yang bisa saja semua data diimport sekaligus.
     */
    private function countNewByKelas(string $startStr, string $endStr, array $accessibleCabangIds, ?int $cabangId): array
    {
        $firstVisitInPeriod = DB::table('kunjungans')
            ->selectRaw('pelanggan_id')
            ->groupBy('pelanggan_id')
            ->havingRaw('MIN(tanggal_kunjungan) BETWEEN ? AND ?', [$startStr, $endStr]);

        $q = DB::table('pelanggans as p')
            ->whereNull('p.deleted_at')
            ->whereIn('p.id', $firstVisitInPeriod)
            ->selectRaw('p.class as kelas, COUNT(DISTINCT p.id) as jumlah')
            ->groupBy('p.class');

        if ($cabangId) {
            $q->where('p.cabang_id', $cabangId);
        } elseif (!empty($accessibleCabangIds)) {
            $q->whereIn('p.cabang_id', $accessibleCabangIds);
        }

        return $this->normalizeKelasResult($q->get());
    }

    /**
     * Normalisasi hasil query GROUP BY p.class:
     * map null/empty string ke 'Umum', merge jika ada duplikat.
     */
    private function normalizeKelasResult($rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $kelas = ($row->kelas === null || $row->kelas === '') ? 'Umum' : $row->kelas;
            $result[$kelas] = ($result[$kelas] ?? 0) + (int) $row->jumlah;
        }
        return $result;
    }

    private function buildSummaryData(string $startStr, string $endStr, array $accessibleCabangIds, ?int $cabangId): array
    {
        $perKelas = $this->countByKelas($startStr, $endStr, $accessibleCabangIds, $cabangId);

        $q = DB::table('pelanggans as p')
            ->whereNull('p.deleted_at')
            ->selectRaw('p.class as kelas, COUNT(DISTINCT p.id) as jumlah')
            ->groupBy('p.class');

        if ($cabangId) {
            $q->where('p.cabang_id', $cabangId);
        } elseif (!empty($accessibleCabangIds)) {
            $q->whereIn('p.cabang_id', $accessibleCabangIds);
        }

        $totalPerKelas = $this->normalizeKelasResult($q->get());
        $grandTotal    = array_sum($totalPerKelas);

        $result = [];
        foreach (self::KELAS_ORDER as $k) {
            $total    = (int) ($totalPerKelas[$k] ?? 0);
            $aktif    = (int) ($perKelas[$k] ?? 0);
            $result[] = [
                'kelas'   => $k,
                'color'   => self::KELAS_COLORS[$k],
                'total'   => $total,
                'aktif'   => $aktif,
                'pct'     => $grandTotal > 0 ? round($total / $grandTotal * 100, 1) : 0,
            ];
        }
        return $result;
    }
}
