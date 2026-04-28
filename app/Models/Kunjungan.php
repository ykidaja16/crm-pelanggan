<?php

namespace App\Models;

use App\Models\Pelanggan;
use App\Models\Cabang;
use App\Models\KelompokPelanggan;
use Illuminate\Database\Eloquent\Model;

class Kunjungan extends Model
{
    protected $fillable = [
        'no',
        'total_kedatangan',
        'pelanggan_id',
        'cabang_id',
        'tanggal_kunjungan',
        'biaya',
        'kelompok_pelanggan_id',
        'import_batch_id',
    ];

    protected $casts = [
        'tanggal_kunjungan' => 'date',
        'no' => 'integer',
        'total_kedatangan' => 'integer',
    ];

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class);
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function kelompokPelanggan()
    {
        return $this->belongsTo(KelompokPelanggan::class, 'kelompok_pelanggan_id');
    }

    public function importBatch()
    {
        return $this->belongsTo(ImportBatch::class);
    }
}
