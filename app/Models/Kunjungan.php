<?php

namespace App\Models;

use App\Models\Pelanggan;
use App\Models\Cabang;
use Illuminate\Database\Eloquent\Model;

class Kunjungan extends Model
{
    protected $fillable = [
        'no',
        'pelanggan_id',
        'cabang_id',
        'tanggal_kunjungan',
        'biaya'
    ];

    protected $casts = [
        'tanggal_kunjungan' => 'date',
        'no' => 'integer',
    ];

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class);
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }
}
