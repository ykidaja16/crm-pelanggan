<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah role IT jika belum ada
        $exists = DB::table('roles')->where('name', 'IT')->exists();
        if (!$exists) {
            DB::table('roles')->insert([
                'name'       => 'IT',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('roles')->where('name', 'IT')->delete();
    }
};
