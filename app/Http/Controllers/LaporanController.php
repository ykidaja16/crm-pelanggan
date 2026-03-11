<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Pelanggan;
use App\Models\Cabang;
use App\Models\User;
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
     * Point 4: Helper untuk validasi prefix PID sesuai cabang
     */
    public static function validatePidCabang(string $pid, int $cabangId): bool
    {
        $cabang = Cabang::find($cabangId);
        if (!$cabang) return false;
        $pidPrefix = strtoupper(substr($pid, 0, 2));
        return $pidPrefix === strtoupper($cabang->kode);
    }

    public function preview(Request $request)
    {
        try {
            // Build query utama dengan semua filter
            $baseQuery = $this->buildQuery($request);

            // Untuk summary: wrap query sebagai subquery agar tidak konflik dengan
            // only_full_group_by (karena buildQuery() sudah punya SELECT pelanggans.*)
            $baseSql      = $baseQuery->toSql();
            $baseBindings = $baseQuery->getBindings();

            $summaryRaw = DB::table(DB::raw("({$baseSql}) as sub"))
                ->selectRaw('COUNT(*) as total_pelanggan, COALESCE(SUM(total_biaya),0) as total_omset, COALESCE(AVG(total_biaya),0) as rata_rata_omset, COALESCE(SUM(total_kedatangan),0) as total_kunjungan')
                ->setBindings($baseBindings)
                ->first();

            $summary = [
                'total_pelanggan' => (int)   ($summaryRaw->total_pelanggan  ?? 0),
                'total_omset'     => (float)  ($summaryRaw->total_omset      ?? 0),
                'rata_rata_omset' => (float)  ($summaryRaw->rata_rata_omset  ?? 0),
                'total_kunjungan' => (int)    ($summaryRaw->total_kunjungan  ?? 0),
            ];

            // Paginate menggunakan query baru (buildQuery dipanggil ulang agar bersih)
            $pelanggan = $this->buildQuery($request)->paginate(25);

            return response()->json([
                'data'    => $pelanggan,
                'summary' => $summary,
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

    public function export(Request $request)
    {
        $format = $request->get('format', 'excel');
        $query = $this->buildQuery($request);
        $pelanggan = $query->get();
        
        $filters = $this->getFilterLabels($request);
        
        switch ($format) {
            case 'print':
                return view('laporan.print', compact('pelanggan', 'filters'));
                
            case 'excel':
            default:
                return Excel::download(new LaporanExport($pelanggan, $filters), 'laporan-pelanggan-' . date('Y-m-d') . '.xlsx');
        }

    }

    private function buildQuery(Request $request)
    {
        $query = Pelanggan::with(['cabang', 'kunjungans'])
            ->select('pelanggans.*')
            ->selectRaw('(SELECT MAX(tanggal_kunjungan) FROM kunjungans WHERE kunjungans.pelanggan_id = pelanggans.id) as tgl_kunjungan_terakhir');
        
        // Filter Periode
        $type = $request->get('type', 'semua');
        $bulan = $request->get('bulan', date('m'));
        $tahun = $request->get('tahun', date('Y'));
        $tanggal_mulai = $request->get('tanggal_mulai');
        $tanggal_selesai = $request->get('tanggal_selesai');
        
        // Subquery untuk last visit date (dipakai di WHERE — alias tidak bisa dipakai di WHERE MySQL)
        $lastVisitSub = '(SELECT MAX(tanggal_kunjungan) FROM kunjungans WHERE kunjungans.pelanggan_id = pelanggans.id)';

        if ($type === 'perbulan') {
            $query->whereRaw("YEAR($lastVisitSub) = ?", [$tahun])
                  ->whereRaw("MONTH($lastVisitSub) = ?", [$bulan]);
        } elseif ($type === 'pertahun') {
            $query->whereRaw("YEAR($lastVisitSub) = ?", [$tahun]);
        } elseif ($type === 'range' && $tanggal_mulai && $tanggal_selesai) {
            $query->whereRaw("$lastVisitSub BETWEEN ? AND ?", [$tanggal_mulai, $tanggal_selesai]);
        }

        
        // Filter Cabang
        if ($request->filled('cabang_id')) {
            $query->where('cabang_id', $request->cabang_id);
        }
        
        // Filter Kelas
        if ($request->filled('kelas')) {
            $query->where('class', $request->kelas);
        }
        
        // Filter Range Omset
        if ($request->filled('omset_range')) {
            switch ($request->omset_range) {
                case '0':
                    $query->where('total_biaya', '<', 1000000);
                    break;
                case '1':
                    $query->whereBetween('total_biaya', [1000000, 4000000]);
                    break;
                case '2':
                    $query->where('total_biaya', '>', 4000000);
                    break;
            }
        }
        
        // Filter Range Kedatangan
        if ($request->filled('kedatangan_range')) {
            switch ($request->kedatangan_range) {
                case '0':
                    $query->where('total_kedatangan', '<=', 2);
                    break;
                case '1':
                    $query->whereBetween('total_kedatangan', [3, 4]);
                    break;
                case '2':
                    $query->where('total_kedatangan', '>', 4);
                    break;
            }
        }
        
        // Point 4: Filter kelompok pelanggan (mandiri/klinisi)
        if ($request->filled('kelompok_pelanggan')) {
            $kelompok = $request->get('kelompok_pelanggan');
            $query->whereHas('kunjungans.kelompokPelanggan', function ($q) use ($kelompok) {
                $q->where('kode', $kelompok);
            });
        }

        // Point 4: Filter tipe pelanggan (biasa/khusus)
        if ($request->filled('tipe_pelanggan')) {
            $tipe = $request->get('tipe_pelanggan');
            if ($tipe === 'khusus') {
                $query->where('is_pelanggan_khusus', true);
            } elseif ($tipe === 'biasa') {
                $query->where('is_pelanggan_khusus', false);
            }
        }

        // Sorting
        $sort = $request->get('sort', 'nama');
        $direction = $request->get('direction', 'asc');
        $query->orderBy($sort, $direction);
        
        return $query;
    }

    private function getFilterLabels(Request $request)
    {
        $labels = [];
        
        // Periode
        $type = $request->get('type', 'semua');
        if ($type === 'perbulan') {
            $bulan = \DateTime::createFromFormat('!m', $request->get('bulan', date('m')))->format('F');

            $labels['periode'] = "Periode: $bulan " . $request->get('tahun', date('Y'));
        } elseif ($type === 'pertahun') {
            $labels['periode'] = "Tahun: " . $request->get('tahun', date('Y'));
        } elseif ($type === 'range') {
            $labels['periode'] = "Range: " . $request->get('tanggal_mulai') . " s/d " . $request->get('tanggal_selesai');
        } else {
            $labels['periode'] = "Semua Periode";
        }
        
        // Cabang
        if ($request->filled('cabang_id')) {
            $cabang = Cabang::find($request->cabang_id);
            $labels['cabang'] = "Cabang: " . ($cabang ? $cabang->nama : '-');
        }
        
        // Kelas
        if ($request->filled('kelas')) {
            $labels['kelas'] = "Kelas: " . $request->kelas;
        }
        
        // Omset
        if ($request->filled('omset_range')) {
            $omsetLabels = [
                '0' => 'Omset: < 1 Juta',
                '1' => 'Omset: 1 - 4 Juta',
                '2' => 'Omset: > 4 Juta'
            ];
            $labels['omset'] = $omsetLabels[$request->omset_range] ?? '';
        }
        
        // Kedatangan
        if ($request->filled('kedatangan_range')) {
            $kedatanganLabels = [
                '0' => 'Kunjungan: ≤ 2 Kali',
                '1' => 'Kunjungan: 3 - 4 Kali',
                '2' => 'Kunjungan: > 4 Kali'
            ];
            $labels['kedatangan'] = $kedatanganLabels[$request->kedatangan_range] ?? '';
        }
        
        return $labels;
    }
}
