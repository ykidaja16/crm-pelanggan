<?php

namespace App\Models;

use App\Models\Kunjungan;
use App\Models\Cabang;
use App\Models\PelangganClassHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Pelanggan extends Model
{
    protected $fillable = [
        'pid',
        'cabang_id',
        'nama',
        'no_telp',
        'dob',
        'alamat',
        'kota',
        'class',
        'total_kedatangan',
        'total_biaya'
    ];

    protected $casts = [
        'dob' => 'date',
        'total_biaya' => 'double',
    ];

    public function kunjungans()
    {
        return $this->hasMany(Kunjungan::class);
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    /**
     * Relasi ke riwayat perubahan kelas
     */
    public function classHistories()
    {
        return $this->hasMany(PelangganClassHistory::class)->orderBy('changed_at', 'desc');
    }

    /**
     * Generate PID based on cabang kode
     * Format: {KodeCabang}{UniqNumber}
     * Example: LXB0049356, LZD0010534
     */
    public static function generatePid($cabangKode, $existingPid = null): string
    {
        // If updating and PID exists, keep it
        if ($existingPid) {
            return $existingPid;
        }

        // Get last PID for this cabang using DB-level MAX for efficiency
        $lastPid = self::where('pid', 'like', $cabangKode . '%')
            ->max('pid');

        if ($lastPid) {
            $lastNumber = (int) substr($lastPid, strlen($cabangKode));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        // Format: CabangKode + 8 digit number
        return $cabangKode . str_pad($newNumber, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate class based on visit count and total spending
     * Potensial: Kedatangan minimal 2x dengan biaya berapapun OR 1x datang dengan minimal biaya 1 Juta
     * Loyal: Kedatangan minimal 5x dengan total biaya berapapun
     * Prioritas: 1x Kedatangan minimal 4 Juta OR total biaya sudah lebih dari 4 juta
     */
    public static function calculateClass(int $totalKedatangan, float $totalBiaya): string
    {
        // Prioritas: 1x Kedatangan minimal 4 Juta OR total biaya > 4 juta
        if ($totalKedatangan >= 1 && $totalBiaya >= 4000000) {
            return 'Prioritas';
        }

        // Loyal: Kedatangan minimal 5x dengan total biaya berapapun
        if ($totalKedatangan >= 5) {
            return 'Loyal';
        }

        // Potensial: Kedatangan minimal 2x OR 1x datang dengan minimal biaya 1 Juta
        if ($totalKedatangan >= 2 || ($totalKedatangan >= 1 && $totalBiaya >= 1000000)) {
            return 'Potensial';
        }

        // Default to Potensial for new customers
        return 'Potensial';
    }

    /**
     * Update computed fields and recalculate class.
     * Optimized: menggunakan 1 query (selectRaw) untuk count + sum sekaligus,
     * bukan 2 query terpisah.
     */
    public function updateStats(?\Carbon\Carbon $visitDate = null): void
    {
        $oldClass = $this->class;

        // Single query untuk sum total_kedatangan dan sum biaya sekaligus
        // Menggunakan SUM(total_kedatangan) bukan COUNT(*) karena setiap record kunjungan
        // bisa mewakili lebih dari 1 kunjungan (misal: import data historis)
        $stats = DB::table('kunjungans')
            ->where('pelanggan_id', $this->id)
            ->selectRaw('COALESCE(SUM(total_kedatangan), 0) as total_kedatangan, COALESCE(SUM(biaya), 0) as total_biaya')
            ->first();

        $this->total_kedatangan = (int) $stats->total_kedatangan;
        $this->total_biaya = (float) $stats->total_biaya;
        $newClass = self::calculateClass($this->total_kedatangan, $this->total_biaya);

        // Catat perubahan kelas jika berbeda
        if ($oldClass !== $newClass) {
            $this->classHistories()->create([
                'previous_class' => $oldClass,
                'new_class'      => $newClass,
                'changed_at'     => now(),
                'changed_by'     => Auth::check() ? Auth::id() : null,
                'reason'         => 'Perubahan otomatis berdasarkan statistik kunjungan',
            ]);
        }

        $this->class = $newClass;
        $this->save();
    }

    /**
     * Catat kelas awal saat pelanggan baru dibuat
     */
    public function recordInitialClass(?\Carbon\Carbon $visitDate = null): void
    {
        $this->classHistories()->create([
            'previous_class' => null,
            'new_class'      => $this->class,
            'changed_at'     => now(),
            'changed_by'     => Auth::check() ? Auth::id() : null,
            'reason'         => 'Kelas awal pelanggan baru',
        ]);
    }

    /**
     * Update biaya dan class tanpa mengubah total_kedatangan
     * Digunakan saat edit kunjungan agar total_kedatangan tetap (sesuai data import)
     * 
     * @param float $biayaDifference Selisih biaya (biaya_baru - biaya_lama)
     * @param \Carbon\Carbon|null $visitDate Tanggal kunjungan untuk riwayat
     */
    public function updateBiayaAndClass(float $biayaDifference, ?\Carbon\Carbon $visitDate = null): void
    {
        $oldClass = $this->class;

        // Update total_biaya dengan selisih
        $this->total_biaya += $biayaDifference;
        
        // Pastikan total_biaya tidak negatif
        if ($this->total_biaya < 0) {
            $this->total_biaya = 0;
        }

        // Hitung class baru berdasarkan total_kedatangan (tetap) dan total_biaya (baru)
        $newClass = self::calculateClass($this->total_kedatangan, $this->total_biaya);

        // Catat perubahan kelas jika berbeda
        if ($oldClass !== $newClass) {
            $this->classHistories()->create([
                'previous_class' => $oldClass,
                'new_class'      => $newClass,
                'changed_at'     => now(),
                'changed_by'     => Auth::check() ? Auth::id() : null,
                'reason'         => 'Perubahan dari edit kunjungan',
            ]);
        }

        $this->class = $newClass;
        $this->save();
    }
}
