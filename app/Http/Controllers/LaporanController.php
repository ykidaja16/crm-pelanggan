<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Pelanggan;
use App\Models\Cabang;
use App\Models\User;
use App\Models\PelangganClassHistory;
use App\Models\Kunjungan;
use App\Exports\LaporanExport;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        $accessibleCabangIds = $user->getAccessibleCabangIds();
        $cabangs = empty($accessibleCabangIds)
            ? Cabang::orderBy('nama')->get()
            : Cabang::whereIn('id', $accessibleCabangIds)->orderBy('nama')->get();

        return view('laporan.index', compact('cabangs'));
    }

    /**
     * Preview data laporan (AJAX JSON) dengan filter yang benar.
     * Filter periode menggunakan whereHas (ada kunjungan di periode),
     * bukan whereRaw pada tanggal kunjungan terakhir.
     */
    public function preview(Request $request)
    {
        try {
            $usePeriodeBiaya = $this->isUsePeriodeBiaya($request);

            // Build query utama
            $baseQuery    = $this->buildQuery($request);
            $baseSql      = $baseQuery->toSql();
            $baseBindings = $baseQuery->getBindings();

            // Kolom summary: gunakan period-specific jika ada filter periode
            $biayaCol      = $usePeriodeBiaya ? 'biaya_periode'      : 'total_biaya';
            $kedatanganCol = $usePeriodeBiaya ? 'kedatangan_periode'  : 'total_kedatangan';

            $summaryRaw = DB::table(DB::raw("({$baseSql}) as sub"))
                ->selectRaw(
                    "COUNT(*) as total_pelanggan,
                     COALESCE(SUM({$biayaCol}), 0)  as total_omset,
                     COALESCE(AVG({$biayaCol}), 0)  as rata_rata_omset,
                     COALESCE(SUM({$kedatanganCol}), 0) as total_kunjungan"
                )
                ->setBindings($baseBindings)
                ->first();

            $summary = [
                'total_pelanggan' => (int)   ($summaryRaw->total_pelanggan  ?? 0),
                'total_omset'     => (float)  ($summaryRaw->total_omset      ?? 0),
                'rata_rata_omset' => (float)  ($summaryRaw->rata_rata_omset  ?? 0),
                'total_kunjungan' => (int)    ($summaryRaw->total_kunjungan  ?? 0),
            ];

            // Paginate (query baru agar bersih)
            $pelanggan = $this->buildQuery($request)->paginate(25);

            // Hitung class_at_period jika ada filter periode
            $endOfPeriod = $this->getEndOfPeriod($request);
            if ($endOfPeriod) {
                $this->enrichWithClassAtPeriod($pelanggan->getCollection(), $endOfPeriod);
            }

            // Tambahkan flag usePeriodeBiaya ke setiap item agar JS bisa memilih kolom
            $pelanggan->getCollection()->transform(function ($item) use ($usePeriodeBiaya) {
                $item->use_periode_biaya = $usePeriodeBiaya;
                return $item;
            });

            return response()->json([
                'data'            => $pelanggan,
                'summary'         => $summary,
                'usePeriodeBiaya' => $usePeriodeBiaya,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => true,
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ], 500);
        }
    }

    /**
     * Export laporan ke Excel atau Print.
     */
    public function export(Request $request)
    {
        $format          = $request->get('format', 'excel');
        $pelanggan       = $this->buildQuery($request)->get();
        $filters         = $this->getFilterLabels($request);
        $usePeriodeBiaya = $this->isUsePeriodeBiaya($request);
        $endOfPeriod     = $this->getEndOfPeriod($request);

        // Hitung class_at_period untuk export
        if ($endOfPeriod) {
            $this->enrichWithClassAtPeriod($pelanggan, $endOfPeriod);
        }

        if ($format === 'print') {
            return view('laporan.print', compact('pelanggan', 'filters', 'usePeriodeBiaya'));
        }

        // Excel
        return Excel::download(
            new LaporanExport($pelanggan, $filters, $usePeriodeBiaya),
            'laporan-pelanggan-' . date('Y-m-d') . '.xlsx'
        );
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Apakah filter periode aktif (sehingga perlu kolom period-specific)?
     */
    private function isUsePeriodeBiaya(Request $request): bool
    {
        $type           = $request->get('type', 'semua');
        $tanggalMulai   = $request->get('tanggal_mulai');
        $tanggalSelesai = $request->get('tanggal_selesai');

        return $type === 'perbulan'
            || $type === 'pertahun'
            || ($type === 'range' && $tanggalMulai && $tanggalSelesai);
    }

    /**
     * Dapatkan tanggal akhir periode (untuk subquery dan class_at_period).
     */
    private function getEndOfPeriod(Request $request): ?string
    {
        $type           = $request->get('type', 'semua');
        $bulan          = (int) $request->get('bulan', date('m'));
        $tahun          = (int) $request->get('tahun', date('Y'));
        $tanggalMulai   = $request->get('tanggal_mulai');
        $tanggalSelesai = $request->get('tanggal_selesai');

        if ($type === 'perbulan') {
            return Carbon::create($tahun, $bulan, 1)->endOfMonth()->format('Y-m-d');
        }
        if ($type === 'pertahun') {
            return Carbon::create($tahun, 12, 31)->format('Y-m-d');
        }
        if ($type === 'range' && $tanggalMulai && $tanggalSelesai) {
            return $tanggalSelesai;
        }
        return null;
    }

    /**
     * Bangun query utama dengan semua filter.
     *
     * PERBAIKAN UTAMA:
     * - Filter periode menggunakan whereHas('kunjungans', ...) bukan whereRaw pada last visit
     * - Subquery biaya/kedatangan bersifat period-specific
     */
    private function buildQuery(Request $request)
    {
        $type           = $request->get('type', 'semua');
        $bulan          = (int) $request->get('bulan', date('m'));
        $tahun          = (int) $request->get('tahun', date('Y'));
        $tanggalMulai   = $request->get('tanggal_mulai');
        $tanggalSelesai = $request->get('tanggal_selesai');

        // ── Subquery tgl_kunjungan_terakhir (period-specific) ─────────────────
        if ($type === 'perbulan') {
            $tglSub = "SELECT MAX(tanggal_kunjungan) FROM kunjungans
                       WHERE kunjungans.pelanggan_id = pelanggans.id
                         AND MONTH(tanggal_kunjungan) = {$bulan}
                         AND YEAR(tanggal_kunjungan)  = {$tahun}";
        } elseif ($type === 'pertahun') {
            $tglSub = "SELECT MAX(tanggal_kunjungan) FROM kunjungans
                       WHERE kunjungans.pelanggan_id = pelanggans.id
                         AND YEAR(tanggal_kunjungan) = {$tahun}";
        } elseif ($type === 'range' && $tanggalMulai && $tanggalSelesai) {
            $safeMulai   = addslashes($tanggalMulai);
            $safeSelesai = addslashes($tanggalSelesai);
            $tglSub = "SELECT MAX(tanggal_kunjungan) FROM kunjungans
                       WHERE kunjungans.pelanggan_id = pelanggans.id
                         AND tanggal_kunjungan BETWEEN '{$safeMulai}' AND '{$safeSelesai}'";
        } else {
            $tglSub = "SELECT MAX(tanggal_kunjungan) FROM kunjungans
                       WHERE kunjungans.pelanggan_id = pelanggans.id";
        }

        // ── Subquery biaya & kedatangan period-specific ───────────────────────
        $endOfPeriod = $this->getEndOfPeriod($request);

        if ($type === 'perbulan') {
            // Kumulatif sampai akhir bulan yang dipilih (bukan hanya bulan itu saja)
            $endOfPeriodStr = Carbon::create($tahun, $bulan, 1)->endOfMonth()->format('Y-m-d');
            $biayaSub      = "SELECT COALESCE(SUM(biaya), 0) FROM kunjungans
                              WHERE kunjungans.pelanggan_id = pelanggans.id
                                AND DATE(tanggal_kunjungan) <= '{$endOfPeriodStr}'";
            $kedatanganSub = "SELECT COALESCE(SUM(total_kedatangan), 0) FROM kunjungans
                              WHERE kunjungans.pelanggan_id = pelanggans.id
                                AND DATE(tanggal_kunjungan) <= '{$endOfPeriodStr}'";
        } elseif ($type === 'pertahun') {
            // Kumulatif sampai akhir tahun yang dipilih
            $endOfPeriodStr = "{$tahun}-12-31";
            $biayaSub      = "SELECT COALESCE(SUM(biaya), 0) FROM kunjungans
                              WHERE kunjungans.pelanggan_id = pelanggans.id
                                AND DATE(tanggal_kunjungan) <= '{$endOfPeriodStr}'";
            $kedatanganSub = "SELECT COALESCE(SUM(total_kedatangan), 0) FROM kunjungans
                              WHERE kunjungans.pelanggan_id = pelanggans.id
                                AND DATE(tanggal_kunjungan) <= '{$endOfPeriodStr}'";
        } elseif ($type === 'range' && $tanggalMulai && $tanggalSelesai) {
            $safeSelesai = addslashes($tanggalSelesai);
            // Kumulatif sampai akhir range (tanggal_selesai), konsisten dengan perbulan/pertahun
            $biayaSub      = "SELECT COALESCE(SUM(biaya), 0) FROM kunjungans
                              WHERE kunjungans.pelanggan_id = pelanggans.id
                                AND DATE(tanggal_kunjungan) <= '{$safeSelesai}'";
            $kedatanganSub = "SELECT COALESCE(SUM(total_kedatangan), 0) FROM kunjungans
                              WHERE kunjungans.pelanggan_id = pelanggans.id
                                AND DATE(tanggal_kunjungan) <= '{$safeSelesai}'";
        } else {
            $biayaSub      = null;
            $kedatanganSub = null;
        }

        // ── Base query ────────────────────────────────────────────────────────
        $query = Pelanggan::with(['cabang'])
            ->select('pelanggans.*')
            ->selectRaw("({$tglSub}) as tgl_kunjungan_terakhir");

        if ($biayaSub) {
            $query->selectRaw("({$biayaSub}) as biaya_periode")
                  ->selectRaw("({$kedatanganSub}) as kedatangan_periode");
        }

        // ── Filter Periode (BENAR: whereHas, bukan whereRaw pada last visit) ──
        if ($type === 'perbulan') {
            $query->whereHas('kunjungans', function ($q) use ($bulan, $tahun) {
                $q->whereMonth('tanggal_kunjungan', $bulan)
                  ->whereYear('tanggal_kunjungan', $tahun);
            });
        } elseif ($type === 'pertahun') {
            $query->whereHas('kunjungans', function ($q) use ($tahun) {
                $q->whereYear('tanggal_kunjungan', $tahun);
            });
        } elseif ($type === 'range' && $tanggalMulai && $tanggalSelesai) {
            $query->whereHas('kunjungans', function ($q) use ($tanggalMulai, $tanggalSelesai) {
                $q->whereBetween('tanggal_kunjungan', [$tanggalMulai, $tanggalSelesai]);
            });
        }

        // ── Filter Cabang ─────────────────────────────────────────────────────
        /** @var User $user */
        $user = Auth::user();
        $accessibleCabangIds = $user->getAccessibleCabangIds();

        if (!empty($accessibleCabangIds)) {
            $query->whereIn('pelanggans.cabang_id', $accessibleCabangIds);
        }

        if ($request->filled('cabang_id')) {
            $cabangId = (int) $request->get('cabang_id');
            if (empty($accessibleCabangIds) || in_array($cabangId, $accessibleCabangIds)) {
                $query->where('pelanggans.cabang_id', $cabangId);
            }
        }

        // ── Filter Kelas ──────────────────────────────────────────────────────
        if ($request->filled('kelas')) {
            $query->where('pelanggans.class', $request->get('kelas'));
        }

        // ── Filter Omset Range ────────────────────────────────────────────────
        if ($request->filled('omset_range')) {
            switch ($request->get('omset_range')) {
                case '0': $query->where('pelanggans.total_biaya', '<', 1000000); break;
                case '1': $query->whereBetween('pelanggans.total_biaya', [1000000, 4000000]); break;
                case '2': $query->where('pelanggans.total_biaya', '>=', 4000000); break;
            }
        }

        // ── Filter Kedatangan Range ───────────────────────────────────────────
        if ($request->filled('kedatangan_range')) {
            switch ($request->get('kedatangan_range')) {
                case '0': $query->where('pelanggans.total_kedatangan', '<=', 2); break;
                case '1': $query->whereBetween('pelanggans.total_kedatangan', [3, 4]); break;
                case '2': $query->where('pelanggans.total_kedatangan', '>', 4); break;
            }
        }

        // ── Filter Tipe Pelanggan ─────────────────────────────────────────────
        if ($request->filled('tipe_pelanggan')) {
            $tipe = $request->get('tipe_pelanggan');
            if ($tipe === 'khusus') {
                $query->where('pelanggans.is_pelanggan_khusus', true);
            } elseif ($tipe === 'biasa') {
                $query->where('pelanggans.is_pelanggan_khusus', false);
            }
        }

        // ── Sorting ───────────────────────────────────────────────────────────
        $allowedSorts = [
            'nama'                  => 'pelanggans.nama',
            'pid'                   => 'pelanggans.pid',
            'total_biaya'           => 'pelanggans.total_biaya',
            'total_kedatangan'      => 'pelanggans.total_kedatangan',
            'tgl_kunjungan_terakhir'=> 'tgl_kunjungan_terakhir',
            'class'                 => 'pelanggans.class',
        ];

        $sort      = $request->get('sort', 'nama');
        $direction = $request->get('direction', 'asc');
        $sortCol   = $allowedSorts[$sort] ?? 'pelanggans.nama';
        $direction = in_array(strtolower($direction), ['asc', 'desc']) ? $direction : 'asc';

        $query->orderBy($sortCol, $direction);

        return $query;
    }

    /**
     * Hitung class_at_period untuk koleksi pelanggan.
     * Menggunakan PelangganClassHistory dan logika resolveClassAtDate.
     *
     * @param \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection $collection
     * @param string $endOfPeriod  Format Y-m-d
     */
    private function enrichWithClassAtPeriod($collection, string $endOfPeriod): void
    {
        if ($collection->isEmpty()) return;

        $pelangganIds = $collection->pluck('id');

        // Ambil semua histories sekaligus (1 query)
        $allHistories = PelangganClassHistory::whereIn('pelanggan_id', $pelangganIds)
            ->orderBy('changed_at', 'asc')
            ->get()
            ->groupBy('pelanggan_id');

        // Ambil pelanggan yang punya kunjungan high-value s.d. endOfPeriod (1 query)
        $highValueIds = Kunjungan::whereIn('pelanggan_id', $pelangganIds)
            ->where('biaya', '>=', 4000000)
            ->where('tanggal_kunjungan', '<=', $endOfPeriod)
            ->pluck('pelanggan_id')
            ->unique()
            ->flip()
            ->toArray(); // [id => true] untuk O(1) lookup

        $collection->each(function ($p) use ($allHistories, $endOfPeriod, $highValueIds) {
            $histories = $allHistories->get($p->id, collect());

            if ($histories->isEmpty()) {
                // Tidak ada history → hitung dari stats period-specific
                $kedatangan = (int)   ($p->kedatangan_periode ?? $p->total_kedatangan ?? 0);
                $biaya      = (float) ($p->biaya_periode      ?? $p->total_biaya      ?? 0);
                $hasHigh    = isset($highValueIds[$p->id]);

                $p->class_at_period = Pelanggan::calculateClass(
                    $kedatangan,
                    $biaya,
                    $hasHigh,
                    (bool) $p->is_pelanggan_khusus
                );
            } else {
                $p->class_at_period = Pelanggan::resolveClassAtDate(
                    $endOfPeriod,
                    $histories,
                    $p->class
                );
            }
        });
    }

    /**
     * Label filter untuk print/export.
     */
    private function getFilterLabels(Request $request): array
    {
        $labels = [];

        // Periode
        $type = $request->get('type', 'semua');
        if ($type === 'perbulan') {
            $bulanNum = $request->get('bulan', date('m'));
            $bulanNama = Carbon::create()->month((int)$bulanNum)->locale('id')->monthName;
            $labels['Periode'] = ucfirst($bulanNama) . ' ' . $request->get('tahun', date('Y'));
        } elseif ($type === 'pertahun') {
            $labels['Periode'] = 'Tahun ' . $request->get('tahun', date('Y'));
        } elseif ($type === 'range') {
            $labels['Periode'] = $request->get('tanggal_mulai') . ' s/d ' . $request->get('tanggal_selesai');
        } else {
            $labels['Periode'] = 'Semua Periode';
        }

        // Cabang
        if ($request->filled('cabang_id')) {
            $cabang = Cabang::find($request->cabang_id);
            $labels['Cabang'] = $cabang ? $cabang->nama : '-';
        }

        // Kelas
        if ($request->filled('kelas')) {
            $labels['Kelas'] = $request->kelas;
        }

        // Omset
        if ($request->filled('omset_range')) {
            $omsetLabels = [
                '0' => '< Rp 1 Juta',
                '1' => 'Rp 1 Juta - Rp 4 Juta',
                '2' => '> Rp 4 Juta',
            ];
            $labels['Omset'] = $omsetLabels[$request->omset_range] ?? '';
        }

        // Kedatangan
        if ($request->filled('kedatangan_range')) {
            $kedatanganLabels = [
                '0' => '≤ 2 Kali',
                '1' => '3 - 4 Kali',
                '2' => '> 4 Kali',
            ];
            $labels['Kunjungan'] = $kedatanganLabels[$request->kedatangan_range] ?? '';
        }

        // Tipe
        if ($request->filled('tipe_pelanggan')) {
            $labels['Tipe'] = $request->tipe_pelanggan === 'khusus' ? 'Pelanggan Khusus' : 'Pelanggan Biasa';
        }

        return $labels;
    }
}
