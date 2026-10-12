<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Kunjungan;

class Marketing extends Model
{
    protected $fillable = [
        'nama_marketing',
    ];

    public function kunjungans()
    {
        return $this->hasMany(Kunjungan::class);
    }
}
