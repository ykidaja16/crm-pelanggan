<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cabang extends Model
{
    protected $fillable = ['kode', 'nama', 'tipe', 'keterangan'];

    public function pelanggans()
    {
        return $this->hasMany(Pelanggan::class);
    }

    public function kunjungans()
    {
        return $this->hasMany(Kunjungan::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_cabangs');
    }
}
