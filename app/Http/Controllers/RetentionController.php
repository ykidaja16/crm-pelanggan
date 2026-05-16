<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Cabang;
use App\Models\Pelanggan;
use App\Models\User;

class RetentionController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $accessibleCabangIds = $user->getAccessibleCabangIds();
        $cabangs = empty($accessibleCabangIds)
            ? Cabang::all()
            : Cabang::whereIn('id', $accessibleCabangIds)->get();

        $period       = $request->period ?? 'monthly';
        $year         = (int) ($request->year  ?? date('Y'));
        $month        = (int) ($request->month ?? date('m'));
        $cabangId     = $request->cabang_id ? (int) $request->cabang_id : null;
        $statusFilter = $request->status; // at_risk | dormant | lost

        // Sanitize cabang access
        if ($cabangId && !empty($accessibleCabangIds) && !in_array($cabangId, $accessibleCabangIds)) {
            $cabangId = null;
        }

        // Period boundaries
        if ($period === 'monthly') {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate   = Carbon::create($year, $month, 1)->endOfMonth();
        } else {
            $startDate = Carbon::create($year, 1, 1)->startOfYear();
            $endDate   = Carbon::create($year, 12, 31)->endOfYear();
        }

        $startStr = $startDate->toDateString();
        $endStr   = $endDate->toDateString();

        // --- RETENTION RATE ---
        // Pelanggan Awal = pernah datang sebelum periode (MIN first visit < startStr)
        $qAwal = DB::table('pelanggans as p')
            ->whereNull('p.deleted_at')
            ->whereExists(function ($q) use ($startStr) {
                $q->from('kunjungans as k1')
                  ->whereColumn('k1.pelanggan_id', 'p.id')
                  ->where('k1.tanggal_kunjungan', '<', $startStr);
            });
        $this->applyCabangFilter($qAwal, $accessibleCabangIds, $cabangId, 'p.cabang_id');
        $pelangganAwal = $qAwal->count();

        // Pelanggan Baru = kunjungan pertama (MIN) ada di periode ini
        $qBaru = DB::table('pelanggans as p')
            ->whereNull('p.deleted_at')
            ->whereExists(function ($q) use ($startStr, $endStr) {
                $q->from('kunjungans as k2')
                  ->whereColumn('k2.pelanggan_id', 'p.id')
                  ->whereBetween('k2.tanggal_kunjungan', [$startStr, $endStr]);
            })
            ->whereNotExists(function ($q) use ($startStr) {
                $q->from('kunjungans as k3')
                  ->whereColumn('k3.pelanggan_id', 'p.id')
                  ->where('k3.tanggal_kunjungan', '<', $startStr);
            });
        $this->applyCabangFilter($qBaru, $accessibleCabangIds, $cabangId, 'p.cabang_id');
        $pelangganBaru = $qBaru->count();

        // Retained Customer = pelanggan lama (pernah datang sebelum periode)
        //                     yang DATANG KEMBALI di periode ini
        $qRetained = DB::table('pelanggans as p')
            ->whereNull('p.deleted_at')
            ->whereExists(function ($q) use ($startStr) {
                $q->from('kunjungans as k1')
                  ->whereColumn('k1.pelanggan_id', 'p.id')
                  ->where('k1.tanggal_kunjungan', '<', $startStr);
            })
            ->whereExists(function ($q) use ($startStr, $endStr) {
                $q->from('kunjungans as k2')
                  ->whereColumn('k2.pelanggan_id', 'p.id')
                  ->whereBetween('k2.tanggal_kunjungan', [$startStr, $endStr]);
            });
        $this->applyCabangFilter($qRetained, $accessibleCabangIds, $cabangId, 'p.cabang_id');
        $pelangganRetained = $qRetained->count();

        // Retention Rate = Retained / Awal × 100%
        $retentionRate = $pelangganAwal > 0
            ? round(($pelangganRetained / $pelangganAwal) * 100, 1)
            : null;

        // --- STATUS RETENTION (real-time, berdasarkan hari ini) ---
        // Hitung per pelanggan: last_visit mereka, lalu categorize
        $lastVisitSub = DB::table('kunjungans as k')
            ->join('pelanggans as p', 'p.id', '=', 'k.pelanggan_id')
            ->whereNull('p.deleted_at')
            ->select('p.id as pelanggan_id')
            ->selectRaw('MAX(k.tanggal_kunjungan) as last_visit');
        $this->applyCabangFilter($lastVisitSub, $accessibleCabangIds, $cabangId, 'p.cabang_id');
        $lastVisitSub->groupBy('p.id');

        $statusCounts = DB::table(DB::raw("({$lastVisitSub->toSql()}) as lv"))
            ->mergeBindings($lastVisitSub)
            ->selectRaw("
                SUM(CASE WHEN DATEDIFF(CURDATE(), last_visit) > 60  THEN 1 ELSE 0 END) as at_risk_total,
                SUM(CASE WHEN DATEDIFF(CURDATE(), last_visit) > 90  THEN 1 ELSE 0 END) as dormant_total,
                SUM(CASE WHEN DATEDIFF(CURDATE(), last_visit) > 180 THEN 1 ELSE 0 END) as lost_total,
                SUM(CASE WHEN DATEDIFF(CURDATE(), last_visit) BETWEEN 61 AND 90  THEN 1 ELSE 0 END) as at_risk_only,
                SUM(CASE WHEN DATEDIFF(CURDATE(), last_visit) BETWEEN 91 AND 180 THEN 1 ELSE 0 END) as dormant_only
            ")
            ->first();

        // --- TREND DATA: 12 bulan terakhir (active customers per month) ---
        $trendEnd   = Carbon::today();
        $trendStart = $trendEnd->copy()->subMonths(11)->startOfMonth();

        $trendRaw = DB::table('kunjungans as k')
            ->join('pelanggans as p', 'p.id', '=', 'k.pelanggan_id')
            ->whereNull('p.deleted_at')
            ->whereBetween('k.tanggal_kunjungan', [$trendStart->toDateString(), $trendEnd->toDateString()])
            ->selectRaw("DATE_FORMAT(k.tanggal_kunjungan, '%Y-%m') as periode")
            ->selectRaw("COUNT(DISTINCT k.pelanggan_id) as total_pelanggan")
            ->selectRaw("SUM(k.total_kedatangan) as total_kunjungan");
        $this->applyCabangFilter($trendRaw, $accessibleCabangIds, $cabangId, 'p.cabang_id');
        $trendRaw = $trendRaw
            ->groupBy(DB::raw("DATE_FORMAT(k.tanggal_kunjungan, '%Y-%m')"))
            ->orderBy('periode')
            ->get()
            ->keyBy('periode');

        $trendLabels    = [];
        $trendPelanggan = [];
        $trendKunjungan = [];
        for ($i = 0; $i < 12; $i++) {
            $key              = $trendStart->copy()->addMonths($i)->format('Y-m');
            $trendLabels[]    = $trendStart->copy()->addMonths($i)->format('M Y');
            $trendPelanggan[] = (int) ($trendRaw[$key]->total_pelanggan ?? 0);
            $trendKunjungan[] = (int) ($trendRaw[$key]->total_kunjungan ?? 0);
        }

        // --- LIST PELANGGAN BY STATUS (paginated) ---
        $statusPelanggan = null;
        if ($statusFilter && in_array($statusFilter, ['at_risk', 'dormant', 'lost'])) {
            $statusPelanggan = $this->buildStatusQuery($statusFilter, $accessibleCabangIds, $cabangId)
                ->paginate(25)
                ->withQueryString();
        }

        return view('retention.index', compact(
            'period', 'year', 'month', 'cabangs', 'cabangId',
            'startDate', 'endDate',
            'pelangganAwal', 'pelangganBaru', 'pelangganRetained',
            'retentionRate', 'statusCounts',
            'trendLabels', 'trendPelanggan', 'trendKunjungan',
            'statusFilter', 'statusPelanggan'
        ));
    }

    /**
     * Terapkan filter cabang ke query builder.
     */
    private function applyCabangFilter($query, array $accessibleCabangIds, ?int $cabangId, string $column): void
    {
        if ($cabangId) {
            $query->where($column, $cabangId);
        } elseif (!empty($accessibleCabangIds)) {
            $query->whereIn($column, $accessibleCabangIds);
        }
    }

    /**
     * Bangun query daftar pelanggan berdasarkan status retention.
     * Menggunakan GROUP BY + HAVING agar efisien di DB level.
     */
    private function buildStatusQuery(string $status, array $accessibleCabangIds, ?int $cabangId)
    {
        [$minDays, $maxDays] = match ($status) {
            'lost'    => [180, null],
            'dormant' => [90, 180],
            'at_risk' => [60, 90],
            default   => [60, null],
        };

        $query = Pelanggan::with('cabang')
            ->select('pelanggans.*')
            ->selectRaw('MAX(kunjungans.tanggal_kunjungan) as last_visit')
            ->selectRaw('DATEDIFF(CURDATE(), MAX(kunjungans.tanggal_kunjungan)) as days_since')
            ->join('kunjungans', 'kunjungans.pelanggan_id', '=', 'pelanggans.id')
            ->whereNull('pelanggans.deleted_at')
            ->groupBy('pelanggans.id')
            ->havingRaw('DATEDIFF(CURDATE(), MAX(kunjungans.tanggal_kunjungan)) > ?', [$minDays]);

        if ($maxDays) {
            $query->havingRaw('DATEDIFF(CURDATE(), MAX(kunjungans.tanggal_kunjungan)) <= ?', [$maxDays]);
        }

        if ($cabangId) {
            $query->where('pelanggans.cabang_id', $cabangId);
        } elseif (!empty($accessibleCabangIds)) {
            $query->whereIn('pelanggans.cabang_id', $accessibleCabangIds);
        }

        return $query->orderByRaw('days_since DESC');
    }
}
