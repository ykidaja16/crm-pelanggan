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
     * Apakah batch ini sudah di-rollback?
     */
    public function isRolledBack(): bool
    {
        return $this->status === 'rolled_back';
    }
}
