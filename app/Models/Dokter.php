<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Kunjungan;

class Dokter extends Model
{
    protected $fillable = [
        'kode_dokter',
        'nama_dokter',
    ];

    public function kunjungans()
    {
        return $this->hasMany(Kunjungan::class);
    }
}
