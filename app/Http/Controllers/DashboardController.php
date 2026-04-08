<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Pelanggan;
use App\Models\Kunjungan;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $filterType = $request->filter_type ?? 'monthly'; // monthly, yearly, class
        $year       = $request->year ?? date('Y');
        $month      = $request->month ?? null;

        // Data untuk grafik
        $chartData   = [];
        $chartLabels = [];
        $chartTitle  = '';

        if ($filterType === 'monthly') {
            // Pertumbuhan per bulan — 1 query GROUP BY, bukan 12 query terpisah
            $chartTitle = 'Pertumbuhan Pasien per Bulan - Tahun ' . $year;

            // Ambil jumlah pelanggan unik per bulan dalam 1 query
            $monthlyCounts = DB::table('kunjungans')
                ->select(DB::raw('MONTH(tanggal_kunjungan) as bulan'), DB::raw('COUNT(DISTINCT pelanggan_id) as total'))
                ->whereYear('tanggal_kunjungan', $year)
                ->groupBy(DB::raw('MONTH(tanggal_kunjungan)'))
                ->pluck('total', 'bulan');

            for ($i = 1; $i <= 12; $i++) {
                $chartLabels[] = Carbon::create()->month($i)->format('F');
                $chartData[]   = (int) ($monthlyCounts[$i] ?? 0);
            }

        } elseif ($filterType === 'yearly') {
            // Pertumbuhan per tahun — 1 query GROUP BY, bukan 5 query terpisah
            $chartTitle  = 'Pertumbuhan Pasien per Tahun';
            $currentYear = (int) date('Y');
            $startYear   = $currentYear - 4;

            $yearlyCounts = DB::table('kunjungans')
                ->select(DB::raw('YEAR(tanggal_kunjungan) as tahun'), DB::raw('COUNT(DISTINCT pelanggan_id) as total'))
                ->whereBetween(DB::raw('YEAR(tanggal_kunjungan)'), [$startYear, $currentYear])
                ->groupBy(DB::raw('YEAR(tanggal_kunjungan)'))
                ->pluck('total', 'tahun');

            for ($i = $startYear; $i <= $currentYear; $i++) {
                $chartLabels[] = $i;
                $chartData[]   = (int) ($yearlyCounts[$i] ?? 0);
            }

        } elseif ($filterType === 'class') {
            // Berdasarkan klasifikasi — 1 query GROUP BY, bukan N query terpisah
            $chartTitle = 'Jumlah Pasien per Klasifikasi';

            $classCounts = Pelanggan::select('class', DB::raw('COUNT(*) as total'))
                ->groupBy('class')
                ->pluck('total', 'class');

            $classes = ['Prioritas', 'Loyal', 'Potensial', 'Umum'];
            foreach ($classes as $class) {
                $chartLabels[] = $class;
                $chartData[]   = (int) ($classCounts[$class] ?? 0);
            }
        }

        // Statistik ringkasan — di-cache 5 menit agar tidak query ulang setiap request
        $stats = Cache::remember('dashboard_stats', 300, function () {
            $now       = Carbon::now();
            $thisMonth = $now->month;
            $thisYear  = $now->year;
            $firstDay  = $now->startOfMonth()->toDateString();

            // Hitung bulan kemarin
            $lastMonth = $now->copy()->subMonth();
            $lastMonthNumber = $lastMonth->month;
            $lastMonthYear   = $lastMonth->year;

            return [
                'totalPelanggan' => Pelanggan::count(),

                'totalKunjunganBulanIni' => Kunjungan::whereMonth('tanggal_kunjungan', $thisMonth)
                    ->whereYear('tanggal_kunjungan', $thisYear)
                    ->count(),

                'totalKunjunganBulanKemarin' => Kunjungan::whereMonth('tanggal_kunjungan', $lastMonthNumber)
                    ->whereYear('tanggal_kunjungan', $lastMonthYear)
                    ->count(),

                'totalKunjunganTahunIni' => Kunjungan::whereYear('tanggal_kunjungan', $thisYear)->count(),

                // Pelanggan baru: kunjungan pertama mereka ada di bulan ini
                'pelangganBaruBulanIni' => DB::table('pelanggans')
                    ->whereExists(function ($q) use ($thisMonth, $thisYear) {
                        $q->from('kunjungans')
                          ->whereColumn('kunjungans.pelanggan_id', 'pelanggans.id')
                          ->whereMonth('tanggal_kunjungan', $thisMonth)
                          ->whereYear('tanggal_kunjungan', $thisYear);
                    })
                    ->whereNotExists(function ($q) use ($firstDay) {
                        $q->from('kunjungans')
                          ->whereColumn('kunjungans.pelanggan_id', 'pelanggans.id')
                          ->where('tanggal_kunjungan', '<', $firstDay);
                    })
                    ->count(),

                // Statistik per klasifikasi
                'totalPelangganPrioritas' => Pelanggan::where('class', 'Prioritas')->count(),
                'totalPelangganLoyal' => Pelanggan::where('class', 'Loyal')->count(),
                'totalPelangganPotensial' => Pelanggan::where('class', 'Potensial')->count(),
                'totalPelangganUmum' => Pelanggan::where('class', 'Umum')->count(),
            ];
        });

        return view('dashboard.index', [
            'chartData'                   => $chartData,
            'chartLabels'                 => $chartLabels,
            'chartTitle'                  => $chartTitle,
            'filterType'                  => $filterType,
            'year'                        => $year,
            'month'                       => $month,
            'totalPelanggan'              => $stats['totalPelanggan'],
            'totalKunjunganBulanIni'      => $stats['totalKunjunganBulanIni'],
            'totalKunjunganBulanKemarin'  => $stats['totalKunjunganBulanKemarin'],
            'totalKunjunganTahunIni'      => $stats['totalKunjunganTahunIni'],
            'pelangganBaruBulanIni'       => $stats['pelangganBaruBulanIni'],
            'totalPelangganPrioritas'     => $stats['totalPelangganPrioritas'],
            'totalPelangganLoyal'         => $stats['totalPelangganLoyal'],
            'totalPelangganPotensial'     => $stats['totalPelangganPotensial'],
            'totalPelangganUmum'          => $stats['totalPelangganUmum'],
        ]);
    }
}
