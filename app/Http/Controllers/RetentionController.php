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

        // ── ROLE FLAGS ──
        $roleName       = $user->role?->name;
        $isDirektur     = $roleName === 'Direktur';
        $isAdminOrAbove = in_array($roleName, ['Admin', 'Super Admin', 'Direktur']);

        // ── A. ANALISIS CABANG (Direktur only) ──
        $analisisCabang = null;
        if ($isDirektur && $cabangs->count() > 1) {
            $acIds = !empty($accessibleCabangIds) ? $accessibleCabangIds : $cabangs->pluck('id')->toArray();
            if ($cabangId) $acIds = [$cabangId];

            $awalPerCabang = DB::table('pelanggans as p')
                ->whereNull('p.deleted_at')->whereIn('p.cabang_id', $acIds)
                ->whereExists(fn($q) => $q->from('kunjungans as k')->whereColumn('k.pelanggan_id', 'p.id')->where('k.tanggal_kunjungan', '<', $startStr))
                ->selectRaw('p.cabang_id, COUNT(DISTINCT p.id) as jumlah')->groupBy('p.cabang_id')
                ->pluck('jumlah', 'cabang_id');

            $retainedPerCabang = DB::table('pelanggans as p')
                ->whereNull('p.deleted_at')->whereIn('p.cabang_id', $acIds)
                ->whereExists(fn($q) => $q->from('kunjungans as k')->whereColumn('k.pelanggan_id', 'p.id')->where('k.tanggal_kunjungan', '<', $startStr))
                ->whereExists(fn($q) => $q->from('kunjungans as k2')->whereColumn('k2.pelanggan_id', 'p.id')->whereBetween('k2.tanggal_kunjungan', [$startStr, $endStr]))
                ->selectRaw('p.cabang_id, COUNT(DISTINCT p.id) as jumlah')->groupBy('p.cabang_id')
                ->pluck('jumlah', 'cabang_id');

            $baruPerCabang = DB::table('pelanggans as p')
                ->whereNull('p.deleted_at')->whereIn('p.cabang_id', $acIds)
                ->whereExists(fn($q) => $q->from('kunjungans as k')->whereColumn('k.pelanggan_id', 'p.id')->whereBetween('k.tanggal_kunjungan', [$startStr, $endStr]))
                ->whereNotExists(fn($q) => $q->from('kunjungans as k2')->whereColumn('k2.pelanggan_id', 'p.id')->where('k2.tanggal_kunjungan', '<', $startStr))
                ->selectRaw('p.cabang_id, COUNT(DISTINCT p.id) as jumlah')->groupBy('p.cabang_id')
                ->pluck('jumlah', 'cabang_id');

            $lostPerCabang = DB::table('pelanggans as p')
                ->whereNull('p.deleted_at')->whereIn('p.cabang_id', $acIds)
                ->join(DB::raw('(SELECT pelanggan_id, MAX(tanggal_kunjungan) as lv FROM kunjungans GROUP BY pelanggan_id) as lv_sub'), 'lv_sub.pelanggan_id', '=', 'p.id')
                ->whereRaw('DATEDIFF(CURDATE(), lv_sub.lv) > 180')
                ->selectRaw('p.cabang_id, COUNT(DISTINCT p.id) as jumlah')->groupBy('p.cabang_id')
                ->pluck('jumlah', 'cabang_id');

            $analisisCabang = [];
            $displayedCabangs = $cabangId ? $cabangs->where('id', $cabangId) : $cabangs;
            foreach ($displayedCabangs as $c) {
                $awal     = (int)($awalPerCabang[$c->id]     ?? 0);
                $retained = (int)($retainedPerCabang[$c->id] ?? 0);
                $baru     = (int)($baruPerCabang[$c->id]     ?? 0);
                $lost     = (int)($lostPerCabang[$c->id]     ?? 0);
                $retRate  = $awal > 0 ? round(($retained / $awal) * 100, 1) : null;
                $growth   = $awal > 0 ? round((($retained + $baru - $awal) / $awal) * 100, 1) : null;
                $analisisCabang[] = [
                    'id' => $c->id, 'nama' => $c->nama,
                    'awal' => $awal, 'retained' => $retained, 'baru' => $baru, 'lost' => $lost,
                    'retRate' => $retRate, 'growth' => $growth,
                ];
            }
            usort($analisisCabang, fn($a, $b) => ($b['retRate'] ?? -999) <=> ($a['retRate'] ?? -999));
        }

        // ── B. SMART INSIGHTS (Admin, Super Admin, Direktur) ──
        $smartInsights = [];
        if ($isAdminOrAbove) {
            // 1. Retention vs periode sebelumnya
            $prevStart = ($period === 'monthly')
                ? Carbon::create($year, $month, 1)->subMonth()->startOfMonth()->toDateString()
                : Carbon::create($year - 1, 1, 1)->toDateString();
            $prevEnd = ($period === 'monthly')
                ? Carbon::create($year, $month, 1)->subMonth()->endOfMonth()->toDateString()
                : Carbon::create($year - 1, 12, 31)->toDateString();
            $prevLabel = ($period === 'monthly')
                ? Carbon::create($year, $month, 1)->subMonth()->format('F Y')
                : (string)($year - 1);

            $qPA = DB::table('pelanggans as p')->whereNull('p.deleted_at')
                ->whereExists(fn($q) => $q->from('kunjungans as k')->whereColumn('k.pelanggan_id', 'p.id')->where('k.tanggal_kunjungan', '<', $prevStart));
            $this->applyCabangFilter($qPA, $accessibleCabangIds, $cabangId, 'p.cabang_id');
            $prevAwal = $qPA->count();

            $qPR = DB::table('pelanggans as p')->whereNull('p.deleted_at')
                ->whereExists(fn($q) => $q->from('kunjungans as k')->whereColumn('k.pelanggan_id', 'p.id')->where('k.tanggal_kunjungan', '<', $prevStart))
                ->whereExists(fn($q) => $q->from('kunjungans as k2')->whereColumn('k2.pelanggan_id', 'p.id')->whereBetween('k2.tanggal_kunjungan', [$prevStart, $prevEnd]));
            $this->applyCabangFilter($qPR, $accessibleCabangIds, $cabangId, 'p.cabang_id');
            $prevRetRate = $prevAwal > 0 ? round(($qPR->count() / $prevAwal) * 100, 1) : null;

            if (!is_null($retentionRate) && !is_null($prevRetRate)) {
                $diff = round($retentionRate - $prevRetRate, 1);
                if ($diff < 0) {
                    $smartInsights[] = ['type' => 'danger',  'icon' => 'arrow-trend-down', 'text' => "Retention turun {$diff}% dibanding {$prevLabel} ({$prevRetRate}% → {$retentionRate}%)"];
                } elseif ($diff > 0) {
                    $smartInsights[] = ['type' => 'success', 'icon' => 'arrow-trend-up',   'text' => "Retention naik +{$diff}% dibanding {$prevLabel} ({$prevRetRate}% → {$retentionRate}%)"];
                } else {
                    $smartInsights[] = ['type' => 'info',    'icon' => 'equals',            'text' => "Retention stabil dibanding {$prevLabel} ({$retentionRate}%)"];
                }
            }

            // 2. Cabang dengan pelanggan lost tertinggi
            if ($cabangs->count() > 1 && !$cabangId) {
                $topLost = DB::table('pelanggans as p')
                    ->join('cabangs as c', 'c.id', '=', 'p.cabang_id')
                    ->join(DB::raw('(SELECT pelanggan_id, MAX(tanggal_kunjungan) as lv FROM kunjungans GROUP BY pelanggan_id) as lv_s'), 'lv_s.pelanggan_id', '=', 'p.id')
                    ->whereNull('p.deleted_at')
                    ->whereRaw('DATEDIFF(CURDATE(), lv_s.lv) > 180');
                if (!empty($accessibleCabangIds)) $topLost->whereIn('p.cabang_id', $accessibleCabangIds);
                $topLost = $topLost->selectRaw('c.nama as cabang_nama, COUNT(DISTINCT p.id) as jumlah')
                    ->groupBy('c.id', 'c.nama')->orderByRaw('jumlah DESC')->first();
                if ($topLost && $topLost->jumlah > 0) {
                    $smartInsights[] = ['type' => 'warning', 'icon' => 'map-marker-alt', 'text' => "Cabang {$topLost->cabang_nama} kehilangan pelanggan tertinggi ({$topLost->jumlah} pelanggan lost)"];
                }
            }

            // 3. Hari dengan kunjungan tertinggi
            $qDay = DB::table('kunjungans as k')
                ->join('pelanggans as p', 'p.id', '=', 'k.pelanggan_id')
                ->whereNull('p.deleted_at')->whereBetween('k.tanggal_kunjungan', [$startStr, $endStr]);
            $this->applyCabangFilter($qDay, $accessibleCabangIds, $cabangId, 'p.cabang_id');
            $bestDay = $qDay->selectRaw('DAYOFWEEK(k.tanggal_kunjungan) as dow, COUNT(k.id) as total')
                ->groupBy(DB::raw('DAYOFWEEK(k.tanggal_kunjungan)'))->orderByRaw('total DESC')->first();
            if ($bestDay) {
                $dayNames = ['', 'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                $smartInsights[] = ['type' => 'info', 'icon' => 'calendar-day', 'text' => "Hari {$dayNames[$bestDay->dow]} memiliki kunjungan tertinggi ({$bestDay->total} kunjungan) pada periode ini"];
            }

            // 4. % pelanggan yang hanya datang sekali
            $rvSubOnce = DB::table('kunjungans as k')
                ->join('pelanggans as p', 'p.id', '=', 'k.pelanggan_id')
                ->whereNull('p.deleted_at')->whereBetween('k.tanggal_kunjungan', [$startStr, $endStr]);
            $this->applyCabangFilter($rvSubOnce, $accessibleCabangIds, $cabangId, 'p.cabang_id');
            $rvSubOnce->selectRaw('k.pelanggan_id, COUNT(k.id) as cnt')->groupBy('k.pelanggan_id');
            $rvOnceStats = DB::table(DB::raw("({$rvSubOnce->toSql()}) as rv_once"))
                ->mergeBindings($rvSubOnce)
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN cnt = 1 THEN 1 ELSE 0 END) as sekali')
                ->first();
            if ($rvOnceStats && $rvOnceStats->total > 0) {
                $pct = round($rvOnceStats->sekali / $rvOnceStats->total * 100);
                $smartInsights[] = ['type' => $pct > 60 ? 'warning' : 'success', 'icon' => 'user-clock',
                    'text' => "{$pct}% pelanggan ({$rvOnceStats->sekali} dari {$rvOnceStats->total}) hanya datang sekali pada periode ini"];
            }
        }

        // ── C. ANALISIS RETENTION LEBIH DALAM ──
        $repeatVisitData  = null;
        $cohortData       = null;
        $revenueData      = null;
        $retByKlasifikasi = null;
        if ($isAdminOrAbove) {
            // Repeat Visit Rate (satu query efisien)
            $rvSub = DB::table('kunjungans as k')
                ->join('pelanggans as p', 'p.id', '=', 'k.pelanggan_id')
                ->whereNull('p.deleted_at')->whereBetween('k.tanggal_kunjungan', [$startStr, $endStr]);
            $this->applyCabangFilter($rvSub, $accessibleCabangIds, $cabangId, 'p.cabang_id');
            $rvSub->selectRaw('k.pelanggan_id, COUNT(k.id) as visit_count')->groupBy('k.pelanggan_id');

            $rvAgg = DB::table(DB::raw("({$rvSub->toSql()}) as rv"))
                ->mergeBindings($rvSub)
                ->selectRaw('COUNT(*) as total_pelanggan, SUM(visit_count) as total_kunjungan,
                    SUM(CASE WHEN visit_count > 2 THEN 1 ELSE 0 END) as more_than_2,
                    SUM(CASE WHEN visit_count > 5 THEN 1 ELSE 0 END) as more_than_5,
                    ROUND(AVG(visit_count), 1) as avg_visit')->first();

            $topActiveSub = DB::table('kunjungans as k')
                ->join('pelanggans as p', 'p.id', '=', 'k.pelanggan_id')
                ->whereNull('p.deleted_at')->whereBetween('k.tanggal_kunjungan', [$startStr, $endStr]);
            $this->applyCabangFilter($topActiveSub, $accessibleCabangIds, $cabangId, 'p.cabang_id');
            $topActive = $topActiveSub->selectRaw('p.id, p.pid, p.nama, p.cabang_id, COUNT(k.id) as visit_count')
                ->groupBy('p.id', 'p.pid', 'p.nama', 'p.cabang_id')
                ->orderByRaw('visit_count DESC')->limit(5)->get();

            $repeatVisitData = [
                'total_pelanggan' => (int)($rvAgg->total_pelanggan ?? 0),
                'total_kunjungan' => (int)($rvAgg->total_kunjungan ?? 0),
                'avg_visit'       => (float)($rvAgg->avg_visit    ?? 0),
                'more_than_2'     => (int)($rvAgg->more_than_2    ?? 0),
                'more_than_5'     => (int)($rvAgg->more_than_5    ?? 0),
                'top_active'      => $topActive,
            ];

            // Cohort Analysis (6 bulan terakhir dari endDate)
            $cohortStart = $endDate->copy()->subMonths(5)->startOfMonth();
            $cohortStartStr = $cohortStart->toDateString();

            $firstVisitQ = DB::table('kunjungans as k')
                ->join('pelanggans as p', 'p.id', '=', 'k.pelanggan_id')
                ->whereNull('p.deleted_at')->where('k.tanggal_kunjungan', '>=', $cohortStartStr);
            $this->applyCabangFilter($firstVisitQ, $accessibleCabangIds, $cabangId, 'p.cabang_id');
            $firstVisitQ->selectRaw("k.pelanggan_id, DATE_FORMAT(MIN(k.tanggal_kunjungan), '%Y-%m') as cohort_month")
                ->groupBy('k.pelanggan_id');

            $cohortRaw = DB::table(DB::raw("({$firstVisitQ->toSql()}) as fv"))
                ->mergeBindings($firstVisitQ)
                ->join('kunjungans as kv', 'kv.pelanggan_id', '=', 'fv.pelanggan_id')
                ->join('pelanggans as p2', 'p2.id', '=', 'kv.pelanggan_id')
                ->whereNull('p2.deleted_at')->where('kv.tanggal_kunjungan', '>=', $cohortStartStr)
                ->selectRaw("fv.cohort_month, DATE_FORMAT(kv.tanggal_kunjungan, '%Y-%m') as visit_month, COUNT(DISTINCT kv.pelanggan_id) as cnt")
                ->groupBy('fv.cohort_month', DB::raw("DATE_FORMAT(kv.tanggal_kunjungan, '%Y-%m')"))->get()
                ->groupBy('cohort_month');

            $cohortSizes = DB::table(DB::raw("({$firstVisitQ->toSql()}) as fv2"))
                ->mergeBindings($firstVisitQ)
                ->selectRaw('cohort_month, COUNT(*) as sz')->groupBy('cohort_month')
                ->pluck('sz', 'cohort_month');

            $cohortMonths = [];
            for ($i = 0; $i < 6; $i++) {
                $cohortMonths[] = $cohortStart->copy()->addMonths($i)->format('Y-m');
            }
            $todayYm = Carbon::today()->format('Y-m');

            $cohortMatrix = [];
            foreach ($cohortMonths as $cm) {
                $size = (int)($cohortSizes[$cm] ?? 0);
                $row  = ['month' => $cm, 'size' => $size, 'months' => []];
                $rowData = $cohortRaw->get($cm, collect())->keyBy('visit_month');
                for ($m = 0; $m < 6; $m++) {
                    $vm = Carbon::createFromFormat('Y-m', $cm)->addMonths($m)->format('Y-m');
                    if ($vm > $todayYm) {
                        $row['months'][] = null;
                    } else {
                        $cnt = (int)($rowData->get($vm)->cnt ?? 0);
                        $row['months'][] = ['count' => $cnt, 'pct' => $size > 0 ? round($cnt / $size * 100) : 0];
                    }
                }
                $cohortMatrix[] = $row;
            }

            $cohortData = ['months' => $cohortMonths, 'matrix' => $cohortMatrix, 'start' => $cohortStart->format('M Y')];

            // ── REVENUE RETENTION ──
            $qRevTotal = DB::table('kunjungans as k')
                ->join('pelanggans as p', 'p.id', '=', 'k.pelanggan_id')
                ->whereNull('p.deleted_at')->whereBetween('k.tanggal_kunjungan', [$startStr, $endStr]);
            $this->applyCabangFilter($qRevTotal, $accessibleCabangIds, $cabangId, 'p.cabang_id');
            $revTotal = (float) $qRevTotal->sum('k.biaya');

            $qRevRetained = DB::table('kunjungans as k')
                ->join('pelanggans as p', 'p.id', '=', 'k.pelanggan_id')
                ->whereNull('p.deleted_at')->whereBetween('k.tanggal_kunjungan', [$startStr, $endStr])
                ->whereExists(fn($q) => $q->from('kunjungans as k_prev')
                    ->whereColumn('k_prev.pelanggan_id', 'k.pelanggan_id')
                    ->where('k_prev.tanggal_kunjungan', '<', $startStr));
            $this->applyCabangFilter($qRevRetained, $accessibleCabangIds, $cabangId, 'p.cabang_id');
            $revRetained = (float) $qRevRetained->sum('k.biaya');

            $qRevPrev = DB::table('kunjungans as k')
                ->join('pelanggans as p', 'p.id', '=', 'k.pelanggan_id')
                ->whereNull('p.deleted_at')->whereBetween('k.tanggal_kunjungan', [$prevStart, $prevEnd]);
            $this->applyCabangFilter($qRevPrev, $accessibleCabangIds, $cabangId, 'p.cabang_id');
            $revPrev = (float) $qRevPrev->sum('k.biaya');

            $revBaru      = $revTotal - $revRetained;
            $revRetRate   = $revPrev > 0 ? round(($revRetained / $revPrev) * 100, 1) : null;
            $revGrowth    = $revPrev > 0 ? round((($revTotal - $revPrev) / $revPrev) * 100, 1) : null;

            $revenueData = [
                'total'      => $revTotal,    'retained'    => $revRetained,
                'baru'       => $revBaru,     'prev'        => $revPrev,
                'ret_rate'   => $revRetRate,  'growth'      => $revGrowth,
                'prev_label' => $prevLabel,
            ];

            // ── RETENTION BY KLASIFIKASI ──
            // Pre-compute kelas in inner query, then GROUP BY the alias in outer query
            // to avoid MySQL ONLY_FULL_GROUP_BY error (error 1055) on production.

            $subAwal = DB::table('pelanggans as p')
                ->whereNull('p.deleted_at')
                ->whereExists(fn($q) => $q->from('kunjungans as k')->whereColumn('k.pelanggan_id', 'p.id')->where('k.tanggal_kunjungan', '<', $startStr))
                ->select('p.id', DB::raw('COALESCE(NULLIF(p.class,""),"Lainnya") as kelas'));
            $this->applyCabangFilter($subAwal, $accessibleCabangIds, $cabangId, 'p.cabang_id');
            $kelasAwal = DB::table(DB::raw("({$subAwal->toSql()}) as t"))
                ->mergeBindings($subAwal)
                ->selectRaw('kelas, COUNT(DISTINCT id) as jumlah')
                ->groupBy('kelas')
                ->pluck('jumlah', 'kelas');

            $subRetained = DB::table('pelanggans as p')
                ->whereNull('p.deleted_at')
                ->whereExists(fn($q) => $q->from('kunjungans as k')->whereColumn('k.pelanggan_id', 'p.id')->where('k.tanggal_kunjungan', '<', $startStr))
                ->whereExists(fn($q) => $q->from('kunjungans as k2')->whereColumn('k2.pelanggan_id', 'p.id')->whereBetween('k2.tanggal_kunjungan', [$startStr, $endStr]))
                ->select('p.id', DB::raw('COALESCE(NULLIF(p.class,""),"Lainnya") as kelas'));
            $this->applyCabangFilter($subRetained, $accessibleCabangIds, $cabangId, 'p.cabang_id');
            $kelasRetained = DB::table(DB::raw("({$subRetained->toSql()}) as t"))
                ->mergeBindings($subRetained)
                ->selectRaw('kelas, COUNT(DISTINCT id) as jumlah')
                ->groupBy('kelas')
                ->pluck('jumlah', 'kelas');

            $subBaru = DB::table('pelanggans as p')
                ->whereNull('p.deleted_at')
                ->whereExists(fn($q) => $q->from('kunjungans as k')->whereColumn('k.pelanggan_id', 'p.id')->whereBetween('k.tanggal_kunjungan', [$startStr, $endStr]))
                ->whereNotExists(fn($q) => $q->from('kunjungans as k2')->whereColumn('k2.pelanggan_id', 'p.id')->where('k2.tanggal_kunjungan', '<', $startStr))
                ->select('p.id', DB::raw('COALESCE(NULLIF(p.class,""),"Lainnya") as kelas'));
            $this->applyCabangFilter($subBaru, $accessibleCabangIds, $cabangId, 'p.cabang_id');
            $kelasBaru = DB::table(DB::raw("({$subBaru->toSql()}) as t"))
                ->mergeBindings($subBaru)
                ->selectRaw('kelas, COUNT(DISTINCT id) as jumlah')
                ->groupBy('kelas')
                ->pluck('jumlah', 'kelas');

            $allKelas = $kelasAwal->keys()->merge($kelasRetained->keys())->merge($kelasBaru->keys())->unique();
            $klasifikasiOrder = ['Prioritas', 'Loyal', 'Potensial', 'Lainnya'];
            $allKelas = $allKelas->sortBy(fn($k) => array_search($k, $klasifikasiOrder) !== false ? array_search($k, $klasifikasiOrder) : 99);

            $retByKlasifikasi = $allKelas->map(function ($kelas) use ($kelasAwal, $kelasRetained, $kelasBaru) {
                $awal     = (int)($kelasAwal[$kelas]     ?? 0);
                $retained = (int)($kelasRetained[$kelas] ?? 0);
                $baru     = (int)($kelasBaru[$kelas]     ?? 0);
                return [
                    'kelas'    => $kelas,
                    'awal'     => $awal,
                    'retained' => $retained,
                    'baru'     => $baru,
                    'rate'     => $awal > 0 ? round(($retained / $awal) * 100, 1) : null,
                ];
            })->values()->all();
        }

        return view('retention.index', compact(
            'period', 'year', 'month', 'cabangs', 'cabangId',
            'startDate', 'endDate',
            'pelangganAwal', 'pelangganBaru', 'pelangganRetained',
            'retentionRate', 'statusCounts',
            'trendLabels', 'trendPelanggan', 'trendKunjungan',
            'statusFilter', 'statusPelanggan',
            'isDirektur', 'isAdminOrAbove',
            'analisisCabang', 'smartInsights', 'repeatVisitData', 'cohortData',
            'revenueData', 'retByKlasifikasi'
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
