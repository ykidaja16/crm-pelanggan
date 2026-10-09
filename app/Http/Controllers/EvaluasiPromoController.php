<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Cabang;
use App\Models\Pelanggan;
use App\Models\Kunjungan;
use App\Exports\EvaluasiPromoExport;
use Maatwebsite\Excel\Facades\Excel;

class EvaluasiPromoController extends Controller
{
    /**
     * Tampilkan halaman evaluasi event / promo
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $accessibleCabangIds = $user->getAccessibleCabangIds();
        $cabangs = empty($accessibleCabangIds)
            ? Cabang::orderBy('nama')->get()
            : Cabang::whereIn('id', $accessibleCabangIds)->orderBy('nama')->get();

        $isMultiCabang = $cabangs->count() > 1;

        // Parameter Filter
        $mou                = trim((string) $request->get('mou'));
        $tglPromoMulai      = $request->get('tgl_promo_mulai');
        $tglPromoSelesai    = $request->get('tgl_promo_selesai');
        $tglEvaluasiMulai   = $request->get('tgl_evaluasi_mulai');
        $tglEvaluasiSelesai = $request->get('tgl_evaluasi_selesai');
        $jenisPelanggan     = $request->get('jenis_pelanggan', 'semua'); // 'semua' | 'baru' | 'lama'
        $cabangId           = $request->get('cabang_id');

        // Sanitize cabang
        if ($cabangId && !empty($accessibleCabangIds) && !in_array((int)$cabangId, $accessibleCabangIds)) {
            $cabangId = null;
        }

        // Jika user hanya memiliki akses ke 1 cabang, kunci cabangId ke cabang tersebut
        if (!$isMultiCabang && $cabangs->isNotEmpty()) {
            $cabangId = $cabangs->first()->id;
        }

        // Tentukan cabang yang efektif untuk query:
        // Jika cabangId dipilih: gunakan cabang tersebut saja.
        // Jika "Semua Cabang": jika user dibatasi cabang tertentu, batasi hanya ke accessibleCabangIds.
        // Jika user IT / Super Admin tanpa batasan, array kosong = semua cabang di DB.
        if (!empty($cabangId)) {
            $effectiveCabangIds = [(int)$cabangId];
        } elseif (!empty($accessibleCabangIds)) {
            $effectiveCabangIds = array_map('intval', $accessibleCabangIds);
        } else {
            $effectiveCabangIds = [];
        }

        // Ambil opsi unik MOU / Agreement dari database sesuai cabang yang bisa diakses user
        $mouOptions = $this->getMouOptions($effectiveCabangIds);

        $isFiltered = !empty($mou) && !empty($tglEvaluasiMulai) && !empty($tglEvaluasiSelesai);

        $results = collect();
        $summary = [
            'total_peserta_promo'      => 0,
            'total_peserta_baru'       => 0,
            'total_peserta_lama'       => 0,
            'total_repeat_visitor'     => 0,
            'repeat_visitor_baru'      => 0,
            'repeat_visitor_lama'      => 0,
            'retention_rate'           => 0,
        ];

        if ($isFiltered) {
            $evaluasiData = $this->calculateEvaluasi(
                $mou,
                $tglPromoMulai,
                $tglPromoSelesai,
                $tglEvaluasiMulai,
                $tglEvaluasiSelesai,
                $jenisPelanggan,
                $effectiveCabangIds
            );

            $results = $evaluasiData['results'];
            $summary = $evaluasiData['summary'];
        }

        return view('evaluasi-promo.index', compact(
            'cabangs',
            'cabangId',
            'isMultiCabang',
            'mouOptions',
            'mou',
            'tglPromoMulai',
            'tglPromoSelesai',
            'tglEvaluasiMulai',
            'tglEvaluasiSelesai',
            'jenisPelanggan',
            'isFiltered',
            'results',
            'summary'
        ));
    }

    /**
     * Export hasil evaluasi event/promo ke Excel
     */
    public function export(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $accessibleCabangIds = $user->getAccessibleCabangIds();
        $cabangs = empty($accessibleCabangIds)
            ? Cabang::orderBy('nama')->get()
            : Cabang::whereIn('id', $accessibleCabangIds)->orderBy('nama')->get();

        $isMultiCabang = $cabangs->count() > 1;

        $mou                = trim((string) $request->get('mou'));
        $tglPromoMulai      = $request->get('tgl_promo_mulai');
        $tglPromoSelesai    = $request->get('tgl_promo_selesai');
        $tglEvaluasiMulai   = $request->get('tgl_evaluasi_mulai');
        $tglEvaluasiSelesai = $request->get('tgl_evaluasi_selesai');
        $jenisPelanggan     = $request->get('jenis_pelanggan', 'semua');
        $cabangId           = $request->get('cabang_id');

        if ($cabangId && !empty($accessibleCabangIds) && !in_array((int)$cabangId, $accessibleCabangIds)) {
            $cabangId = null;
        }

        if (!$isMultiCabang && $cabangs->isNotEmpty()) {
            $cabangId = $cabangs->first()->id;
        }

        if (!empty($cabangId)) {
            $effectiveCabangIds = [(int)$cabangId];
        } elseif (!empty($accessibleCabangIds)) {
            $effectiveCabangIds = array_map('intval', $accessibleCabangIds);
        } else {
            $effectiveCabangIds = [];
        }

        if (empty($mou) || empty($tglEvaluasiMulai) || empty($tglEvaluasiSelesai)) {
            return redirect()->route('evaluasi-promo.index')
                ->with('error', 'Silakan pilih MOU/Agreement serta Periode Evaluasi sebelum melakukan export.');
        }

        $evaluasiData = $this->calculateEvaluasi(
            $mou,
            $tglPromoMulai,
            $tglPromoSelesai,
            $tglEvaluasiMulai,
            $tglEvaluasiSelesai,
            $jenisPelanggan,
            $effectiveCabangIds
        );

        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', substr($mou, 0, 25));
        $filename = "Evaluasi_Promo_{$safeName}_" . date('Ymd_His') . ".xlsx";

        return Excel::download(
            new EvaluasiPromoExport($evaluasiData['results'], [
                'mou' => $mou,
                'jenis_pelanggan' => $jenisPelanggan,
            ]),
            $filename
        );
    }

    /**
     * Logika inti penghitungan evaluasi event/promo
     */
    private function calculateEvaluasi(
        string $mou,
        ?string $tglPromoMulai,
        ?string $tglPromoSelesai,
        string $tglEvaluasiMulai,
        string $tglEvaluasiSelesai,
        string $jenisPelanggan,
        array $effectiveCabangIds = []
    ): array {
        // 1. Query Kunjungan Promo yang sesuai kriteria (dibatasi cabang yang efektif)
        $promoQuery = Kunjungan::query()
            ->whereNotNull('mou')
            ->where('mou', '!=', '')
            ->where('mou', 'LIKE', '%' . $mou . '%');

        if (!empty($effectiveCabangIds)) {
            $promoQuery->whereIn('cabang_id', $effectiveCabangIds);
        }
        if (!empty($tglPromoMulai) && !empty($tglPromoSelesai)) {
            $promoQuery->whereBetween('tanggal_kunjungan', [$tglPromoMulai, $tglPromoSelesai]);
        }

        // Ambil data pasien unik yang mengikuti promo beserta tanggal kunjungan promo pertama mereka
        $promoParticipants = $promoQuery
            ->select('pelanggan_id', DB::raw('MIN(tanggal_kunjungan) as first_promo_date'))
            ->groupBy('pelanggan_id')
            ->get();

        if ($promoParticipants->isEmpty()) {
            return [
                'results' => collect(),
                'summary' => [
                    'total_peserta_promo'  => 0,
                    'total_peserta_baru'   => 0,
                    'total_peserta_lama'   => 0,
                    'total_repeat_visitor' => 0,
                    'repeat_visitor_baru'  => 0,
                    'repeat_visitor_lama'  => 0,
                    'retention_rate'       => 0,
                ],
            ];
        }

        $promoPelangganIds = $promoParticipants->pluck('pelanggan_id')->toArray();
        $firstPromoDates   = $promoParticipants->pluck('first_promo_date', 'pelanggan_id');

        // 2. Tentukan status Jenis Pelanggan (Baru atau Lama)
        // Pelanggan Baru = sebelum promo ini tidak ada riwayat kunjungan di cabang terkait.
        // Pelanggan Lama = sudah ada kunjungan sebelum tanggal kunjungan promo tersebut.
        $earliestQuery = Kunjungan::whereIn('pelanggan_id', $promoPelangganIds);
        if (!empty($effectiveCabangIds)) {
            $earliestQuery->whereIn('cabang_id', $effectiveCabangIds);
        }
        $earliestVisits = $earliestQuery
            ->select('pelanggan_id', DB::raw('MIN(tanggal_kunjungan) as earliest_date'))
            ->groupBy('pelanggan_id')
            ->pluck('earliest_date', 'pelanggan_id');

        $customerStatusMap = [];
        $pesertaBaruCount = 0;
        $pesertaLamaCount = 0;

        foreach ($promoPelangganIds as $pid) {
            $firstPromo = $firstPromoDates[$pid] ?? null;
            $earliest   = $earliestVisits[$pid] ?? null;

            // Jika kunjungan paling awal lebih kecil dari kunjungan promo pertama, maka pelanggan lama
            if ($earliest && $firstPromo && $earliest < $firstPromo) {
                $status = 'Pelanggan Lama';
                $pesertaLamaCount++;
            } else {
                $status = 'Pelanggan Baru';
                $pesertaBaruCount++;
            }
            $customerStatusMap[$pid] = $status;
        }

        // 3. Evaluasi kedatangan kembali di periode evaluasi (HANYA di cabang yang dapat diakses user)
        $evaluasiQuery = Kunjungan::whereIn('pelanggan_id', $promoPelangganIds)
            ->whereBetween('tanggal_kunjungan', [$tglEvaluasiMulai, $tglEvaluasiSelesai]);

        if (!empty($effectiveCabangIds)) {
            $evaluasiQuery->whereIn('cabang_id', $effectiveCabangIds);
        }

        $evaluasiVisits = $evaluasiQuery
            ->select('pelanggan_id', DB::raw('COUNT(*) as visit_count_eval'))
            ->groupBy('pelanggan_id')
            ->pluck('visit_count_eval', 'pelanggan_id');

        $repeatVisitorIds = $evaluasiVisits->keys()->toArray();

        // Hitung breakdown repeat visitor
        $repeatBaruCount = 0;
        $repeatLamaCount = 0;
        foreach ($repeatVisitorIds as $rPid) {
            if (($customerStatusMap[$rPid] ?? '') === 'Pelanggan Baru') {
                $repeatBaruCount++;
            } else {
                $repeatLamaCount++;
            }
        }

        $totalPeserta = count($promoPelangganIds);
        $totalRepeat  = count($repeatVisitorIds);
        $retentionRate = $totalPeserta > 0 ? round(($totalRepeat / $totalPeserta) * 100, 1) : 0;

        $summary = [
            'total_peserta_promo'  => $totalPeserta,
            'total_peserta_baru'   => $pesertaBaruCount,
            'total_peserta_lama'   => $pesertaLamaCount,
            'total_repeat_visitor' => $totalRepeat,
            'repeat_visitor_baru'  => $repeatBaruCount,
            'repeat_visitor_lama'  => $repeatLamaCount,
            'retention_rate'       => $retentionRate,
        ];

        // 4. Saring ID pelanggan yang akan ditampilkan berdasarkan repeat visits dan filter jenis_pelanggan
        $displayCustomerIds = [];
        foreach ($repeatVisitorIds as $rPid) {
            $cStatus = $customerStatusMap[$rPid] ?? 'Pelanggan Baru';
            if ($jenisPelanggan === 'baru' && $cStatus !== 'Pelanggan Baru') {
                continue;
            }
            if ($jenisPelanggan === 'lama' && $cStatus !== 'Pelanggan Lama') {
                continue;
            }
            $displayCustomerIds[] = $rPid;
        }

        if (empty($displayCustomerIds)) {
            return [
                'results' => collect(),
                'summary' => $summary,
            ];
        }

        // 5. Ambil data profil pelanggan lengkap beserta relasi kunjungan (dibatasi cabang yang efektif)
        $pelangganQuery = Pelanggan::with(['cabang', 'kunjungans' => function ($q) use ($effectiveCabangIds) {
            if (!empty($effectiveCabangIds)) {
                $q->whereIn('cabang_id', $effectiveCabangIds);
            }
            $q->orderBy('tanggal_kunjungan', 'desc')->orderBy('id', 'desc');
        }])
        ->whereIn('id', $displayCustomerIds);

        if (!empty($effectiveCabangIds)) {
            $pelangganQuery->whereIn('cabang_id', $effectiveCabangIds);
        }

        $pelanggans = $pelangganQuery->get();

        $rows = $pelanggans->map(function ($p) use ($customerStatusMap) {
            $kunjungans = $p->kunjungans;

            $latestKunjungan = $kunjungans->first();

            // Kunjungan terakhir yang ada MOU-nya
            $latestWithMou = $kunjungans->first(function ($k) {
                return !empty($k->mou);
            });

            // Kunjungan terakhir yang ada Pemeriksaannya
            $latestWithPemeriksaan = $kunjungans->first(function ($k) {
                return !empty($k->pemeriksaan);
            });

            $tglKunjunganTerakhir = $latestKunjungan && $latestKunjungan->tanggal_kunjungan
                ? Carbon::parse($latestKunjungan->tanggal_kunjungan)->format('d-m-Y')
                : '-';

            $mouTerakhir = $latestWithMou && !empty($latestWithMou->mou)
                ? $latestWithMou->mou
                : ($latestKunjungan?->mou ?? '-');

            $pemeriksaanTerakhir = $latestWithPemeriksaan && !empty($latestWithPemeriksaan->pemeriksaan)
                ? $latestWithPemeriksaan->pemeriksaan
                : ($latestKunjungan?->pemeriksaan ?? '-');

            $tglPemeriksaanTerakhir = $latestWithPemeriksaan && $latestWithPemeriksaan->tanggal_kunjungan
                ? Carbon::parse($latestWithPemeriksaan->tanggal_kunjungan)->format('d-m-Y')
                : '-';

            return [
                'id'                       => $p->id,
                'pid'                      => $p->pid,
                'nama'                     => $p->nama,
                'nik'                      => $p->nik ?? '-',
                'cabang'                   => $p->cabang?->nama ?? '-',
                'no_telp'                  => $p->no_telp ?? '-',
                'dob'                      => $p->dob ? Carbon::parse($p->dob)->format('d-m-Y') : '-',
                'alamat'                   => $p->alamat ?? '-',
                'total_kunjungan'          => $p->total_kedatangan ?: $kunjungans->count(),
                'kunjungan_terakhir'       => $tglKunjunganTerakhir,
                'mou_terakhir'             => $mouTerakhir,
                'pemeriksaan_terakhir'     => $pemeriksaanTerakhir,
                'tgl_pemeriksaan_terakhir' => $tglPemeriksaanTerakhir,
                'class'                    => $p->class ?? 'Umum',
                'status_pelanggan'         => $customerStatusMap[$p->id] ?? 'Pelanggan Baru',
            ];
        });

        // Urutkan berdasarkan kunjungan terakhir descending
        $rows = $rows->sortByDesc('total_kunjungan')->values();

        return [
            'results' => $rows,
            'summary' => $summary,
        ];
    }

    /**
     * Dapatkan daftar unik MOU dari tabel kunjungans sesuai cabang yang bisa diakses
     */
    private function getMouOptions(array $effectiveCabangIds = [])
    {
        $query = Kunjungan::whereNotNull('mou')
            ->where('mou', '!=', '');

        if (!empty($effectiveCabangIds)) {
            $query->whereIn('cabang_id', $effectiveCabangIds);
        }

        $rawList = $query->select('mou')
            ->distinct()
            ->pluck('mou');

        $options = collect();
        foreach ($rawList as $item) {
            $parts = explode(',', (string) $item);
            foreach ($parts as $part) {
                $trimmed = trim($part);
                if ($trimmed !== '') {
                    $options->push($trimmed);
                }
            }
        }

        return $options->unique()->sort()->values();
    }
}
