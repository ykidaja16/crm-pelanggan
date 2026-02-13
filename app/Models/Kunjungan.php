<?php

namespace App\Models;

use App\Models\Pelanggan;
use Illuminate\Database\Eloquent\Model;

class Kunjungan extends Model
{
    protected $fillable = ['pelanggan_id','tanggal_kunjungan','biaya'];

    protected $casts = [
        'tanggal_kunjungan' => 'date',
    ];

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class);
    }

}


