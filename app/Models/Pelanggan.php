<?php

namespace App\Models;

use App\Models\Kunjungan;
use Illuminate\Database\Eloquent\Model;

class Pelanggan extends Model
{
   protected $fillable = ['nik','nama','alamat','class'];


    public function kunjungans()
    {
        return $this->hasMany(Kunjungan::class);
    }

}

