<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pelanggan;
use App\Models\Kunjungan;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $filterType = $request->filter_type ?? 'monthly'; // monthly, yearly, class
        $year = $request->year ?? date('Y');
        $month = $request->month ?? null;
        
        // Data untuk grafik
        $chartData = [];
        $chartLabels = [];
        $chartTitle = '';
        
        if ($filterType == 'monthly') {
            // Pertumbuhan per bulan dalam tahun tertentu
            $chartTitle = 'Pertumbuhan Pasien per Bulan - Tahun ' . $year;
            
            for ($i = 1; $i <= 12; $i++) {
                $count = Pelanggan::whereHas('kunjungans', function($q) use ($i, $year) {
                    $q->whereMonth('tanggal_kunjungan', $i)
                      ->whereYear('tanggal_kunjungan', $year);
                })->count();
                
                $chartLabels[] = Carbon::create()->month($i)->format('F');
                $chartData[] = $count;
            }
        } elseif ($filterType == 'yearly') {
            // Pertumbuhan per tahun (5 tahun terakhir)
            $chartTitle = 'Pertumbuhan Pasien per Tahun';
            $currentYear = date('Y');
            
            for ($i = $currentYear - 4; $i <= $currentYear; $i++) {
                $count = Pelanggan::whereHas('kunjungans', function($q) use ($i) {
                    $q->whereYear('tanggal_kunjungan', $i);
                })->count();
                
                $chartLabels[] = $i;
                $chartData[] = $count;
            }
        } elseif ($filterType == 'class') {
            // Berdasarkan klasifikasi
            $chartTitle = 'Jumlah Pasien per Klasifikasi';
            
            $classes = ['Platinum', 'Gold', 'Silver', 'Basic'];
            foreach ($classes as $class) {
                $count = Pelanggan::where('class', $class)->count();
                $chartLabels[] = $class;
                $chartData[] = $count;
            }
        }
        
        // Statistik tambahan
        $totalPelanggan = Pelanggan::count();
        $totalKunjunganBulanIni = Kunjungan::whereMonth('tanggal_kunjungan', date('m'))
                                           ->whereYear('tanggal_kunjungan', date('Y'))
                                           ->count();
        $totalKunjunganTahunIni = Kunjungan::whereYear('tanggal_kunjungan', date('Y'))->count();
        
        // Pelanggan baru bulan ini (berdasarkan kunjungan pertama kali di bulan ini)
        $pelangganBaruBulanIni = Pelanggan::whereHas('kunjungans', function($q) {
            $q->whereMonth('tanggal_kunjungan', date('m'))
              ->whereYear('tanggal_kunjungan', date('Y'));
        })->whereDoesntHave('kunjungans', function($q) {
            $q->whereDate('tanggal_kunjungan', '<', date('Y-m-01'));
        })->count();

        
        return view('dashboard.index', compact(
            'chartData',
            'chartLabels',
            'chartTitle',
            'filterType',
            'year',
            'month',
            'totalPelanggan',
            'totalKunjunganBulanIni',
            'totalKunjunganTahunIni',
            'pelangganBaruBulanIni'
        ));
    }
}
