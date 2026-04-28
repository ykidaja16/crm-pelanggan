<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportBatch extends Model
{
    protected $fillable = [
        'batch_id',
        'user_id',
        'cabang_id',
        'filename',
        'total_rows',
        'status',
        'imported_at',
        'rolled_back_at',
        'rolled_back_by',
    ];

    protected $casts = [
        'imported_at'    => 'datetime',
        'rolled_back_at' => 'datetime',
        'total_rows'     => 'integer',
    ];

    /**
     * User yang melakukan import
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Cabang yang dipilih saat import
     */
    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    /**
     * User IT yang melakukan rollback
     */
    public function rolledBackByUser()
    {
        return $this->belongsTo(User::class, 'rolled_back_by');
    }

    /**
     * Snapshot data pelanggan sebelum import ini
     */
    public function snapshots()
    {
        return $this->hasMany(ImportBatchPelangganSnapshot::class, 'import_batch_id', 'batch_id');
    }

    /**
     * Kunjungan yang diimport dalam batch ini
     */
    public function kunjungans()
    {
        return $this->hasMany(Kunjungan::class);
    }


    /**
     * Apakah batch ini sudah di-rollback?
     */
    public function isRolledBack(): bool
    {
        return $this->status === 'rolled_back';
    }

    /**
     * Apakah batch ini bisa di-rollback?
     * Validasi: rollback harus dilakukan secara berurutan dari yang terbaru.
     * Tidak boleh rollback batch di tengah/tengah jika ada batch lebih baru yang belum di-rollback.
     */
    public function canBeRolledBack(): bool
    {
        // Jika sudah di-rollback, tidak bisa di-rollback lagi
        if ($this->isRolledBack()) {
            return false;
        }

        // Cek apakah ada batch lain di cabang yang sama dengan imported_at lebih baru
        // dan statusnya bukan 'rolled_back' (masih aktif)
        $newerActiveBatch = self::where('cabang_id', $this->cabang_id)
            ->where('imported_at', '>', $this->imported_at)
            ->where('status', '!=', 'rolled_back')
            ->exists();

        // Jika ada batch lebih baru yang masih aktif, batch ini TIDAK bisa di-rollback
        // (harus rollback yang terbaru dulu)
        return !$newerActiveBatch;
    }
}
