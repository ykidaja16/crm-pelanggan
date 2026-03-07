<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KelompokPelanggan extends Model
{
    protected $fillable = [
        'kode',
        'nama',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
