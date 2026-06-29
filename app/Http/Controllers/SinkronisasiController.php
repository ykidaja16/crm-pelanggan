<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Pelanggan;
use App\Models\PelangganClassHistory;
use App\Exports\SinkronisasiResultExport;
use Maatwebsite\Excel\Facades\Excel;


class SinkronisasiController extends Controller
{
    public function index()
    {
        return view('pelanggan.sinkronisasi');
    }

    /**
     * Jalankan sinkronisasi kelas pelanggan.
     * Prioritas → Loyal, Loyal → Potensial, jika tidak berkunjung >= 2 tahun.
     * Semua record sync dalam 1 batch mendapat changed_at yang sama ($syncAt)
     * sehingga halaman result bisa query berdasarkan timestamp tersebut.
     */
    public function synchronize()
    {
        $syncAt      = Carbon::now();
        $twoYearsAgo = $syncAt->copy()->subYears(2)->toDateString();

        $candidates = Pelanggan::whereIn('class', ['Prioritas', 'Loyal'])
            ->whereDoesntHave('kunjungans', function ($q) use ($twoYearsAgo) {
                $q->where('tanggal_kunjungan', '>', $twoYearsAgo);
            })
            ->get();

        foreach ($candidates as $pelanggan) {
            $oldClass = $pelanggan->class;
            $newClass = $oldClass === 'Prioritas' ? 'Loyal' : 'Potensial';

            if ($pelanggan->pre_sync_class === null) {
                $pelanggan->pre_sync_class = $oldClass;
            }
            $pelanggan->class = $newClass;
            $pelanggan->save();

            $pelanggan->classHistories()->create([
                'previous_class' => $oldClass,
                'new_class'      => $newClass,
                'changed_at'     => $syncAt,
                'changed_by'     => Auth::id(),
                'reason'         => 'Sinkronisasi: pelanggan tidak berkunjung selama 2 tahun atau lebih',
                'is_sync'        => true,
            ]);
        }

        // Simpan timestamp sync ke session untuk halaman result
        session(['last_sync_at' => $syncAt->toDateTimeString()]);

        return redirect()->route('pelanggan.sinkronisasi.result');
    }

    /**
     * Tampilkan hasil sinkronisasi terakhir.
     */
    public function result()
    {
        $syncAt = session('last_sync_at');
        if (!$syncAt) {
            return redirect()->route('pelanggan.sinkronisasi')
                ->with('warning', 'Belum ada data sinkronisasi. Silakan jalankan Synchronize terlebih dahulu.');
        }

        $with = ['pelanggan.cabang', 'pelanggan.latestKunjungan'];

        $prioritasToLoyal = PelangganClassHistory::where('is_sync', true)
            ->where('previous_class', 'Prioritas')
            ->where('new_class', 'Loyal')
            ->where('changed_at', $syncAt)
            ->with($with)
            ->get();

        $loyalToPotensial = PelangganClassHistory::where('is_sync', true)
            ->where('previous_class', 'Loyal')
            ->where('new_class', 'Potensial')
            ->where('changed_at', $syncAt)
            ->with($with)
            ->get();

        $totalSynced = $prioritasToLoyal->count() + $loyalToPotensial->count();

        return view('pelanggan.sinkronisasi-result', compact(
            'prioritasToLoyal', 'loyalToPotensial', 'totalSynced', 'syncAt'
        ));
    }

    /**
     * Export hasil sinkronisasi ke Excel.
     * type: 'prioritas-loyal' atau 'loyal-potensial'
     */
    public function export(Request $request, string $type)
    {
        $syncAt = $request->query('sync_at');
        if (!$syncAt) {
            return redirect()->route('pelanggan.sinkronisasi');
        }

        $with = ['pelanggan.cabang', 'pelanggan.latestKunjungan'];

        if ($type === 'prioritas-loyal') {
            $histories  = PelangganClassHistory::where('is_sync', true)
                ->where('previous_class', 'Prioritas')
                ->where('new_class', 'Loyal')
                ->where('changed_at', $syncAt)
                ->with($with)
                ->get();
            $filename   = 'sinkronisasi_prioritas_ke_loyal_' . now()->format('Ymd_His') . '.xlsx';
            $sheetTitle = 'Prioritas ke Loyal';
        } elseif ($type === 'loyal-potensial') {
            $histories  = PelangganClassHistory::where('is_sync', true)
                ->where('previous_class', 'Loyal')
                ->where('new_class', 'Potensial')
                ->where('changed_at', $syncAt)
                ->with($with)
                ->get();
            $filename   = 'sinkronisasi_loyal_ke_potensial_' . now()->format('Ymd_His') . '.xlsx';
            $sheetTitle = 'Loyal ke Potensial';
        } else {
            return redirect()->route('pelanggan.sinkronisasi');
        }

        return Excel::download(new SinkronisasiResultExport($histories, $sheetTitle), $filename);
    }
}
