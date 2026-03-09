<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Models\Cabang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpecialDayController extends Controller
{
    public function index(Request $request)
    {
        $filter   = $request->get('filter', 'birthday'); // 'birthday' | 'anniversary'
        $cabangId = $request->get('cabang_id');
        $kelas    = $request->get('kelas');

        $query = Pelanggan::with(['cabang', 'latestKunjungan.kelompokPelanggan'])
            ->withMax('kunjungans', 'tanggal_kunjungan');

        // ── Filter tipe ──────────────────────────────────────────────────────
        if ($filter === 'birthday') {
            // Pelanggan yang ulang tahun hari ini
            $query->whereNotNull('dob')
                  ->whereMonth('dob', now()->month)
                  ->whereDay('dob', now()->day);
        } elseif ($filter === 'birthday_month') {
            // Pelanggan yang ulang tahun bulan ini
            $query->whereNotNull('dob')
                  ->whereMonth('dob', now()->month);
        } elseif ($filter === 'anniversary') {
            // Pelanggan yang kunjungan terakhirnya tepat 1 tahun yang lalu
            $anniversaryDate = now()->subYear()->format('Y-m-d');
            $query->whereRaw(
                '(SELECT DATE(MAX(tanggal_kunjungan)) FROM kunjungans WHERE kunjungans.pelanggan_id = pelanggans.id) = ?',
                [$anniversaryDate]
            );
        }

        // ── Filter Cabang ────────────────────────────────────────────────────
        if ($cabangId) {
            $query->where('cabang_id', $cabangId);
        }

        // ── Filter Kelas ─────────────────────────────────────────────────────
        if ($kelas) {
            $query->where('class', $kelas);
        }

        $pelanggans = $query->orderBy('nama')->paginate(20)->withQueryString();
        $cabangs    = Cabang::orderBy('nama')->get();

        return view('special-day.index', compact('pelanggans', 'cabangs', 'filter'));
    }
}
