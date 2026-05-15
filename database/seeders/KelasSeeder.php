<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KelasSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('kelas')->insertOrIgnore([
            ['kode_kelas' => 'PRI', 'nama_kelas' => 'Prioritas', 'urutan' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['kode_kelas' => 'LOY', 'nama_kelas' => 'Loyal',     'urutan' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['kode_kelas' => 'POT', 'nama_kelas' => 'Potensial', 'urutan' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['kode_kelas' => 'UMM', 'nama_kelas' => 'Umum',      'urutan' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
