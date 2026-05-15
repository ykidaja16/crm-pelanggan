<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    protected $table      = 'kelas';
    protected $primaryKey = 'id_class';

    protected $fillable = ['kode_kelas', 'nama_kelas', 'urutan'];

    /**
     * Mengembalikan Collection nama_kelas urut tampilan (Prioritas → Umum).
     */
    public static function orderedNames(): \Illuminate\Support\Collection
    {
        return self::orderBy('urutan')->pluck('nama_kelas');
    }

    /**
     * Cek apakah nama_kelas valid (ada di tabel kelas).
     */
    public static function isValid(string $namaKelas): bool
    {
        return self::where('nama_kelas', $namaKelas)->exists();
    }
}
