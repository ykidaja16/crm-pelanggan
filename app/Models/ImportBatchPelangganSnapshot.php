<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportBatchPelangganSnapshot extends Model
{
    protected $fillable = [
        'import_batch_id',
        'pelanggan_id',
        'is_new_pelanggan',
        'total_kedatangan_before',
        'total_biaya_before',
        'class_before',
    ];

    protected $casts = [
        'is_new_pelanggan'       => 'boolean',
        'total_kedatangan_before' => 'integer',
        'total_biaya_before'     => 'double',
    ];

    /**
     * Relasi ke pelanggan
     */
    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class);
    }
}
