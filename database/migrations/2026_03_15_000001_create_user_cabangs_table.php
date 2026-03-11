<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_cabangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('cabang_id')->constrained('cabangs')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['user_id', 'cabang_id']);
        });

        // Seed: semua user existing mendapat akses ke semua cabang
        $userIds   = DB::table('users')->pluck('id');
        $cabangIds = DB::table('cabangs')->pluck('id');
        $now       = now();

        foreach ($userIds as $userId) {
            foreach ($cabangIds as $cabangId) {
                DB::table('user_cabangs')->insertOrIgnore([
                    'user_id'    => $userId,
                    'cabang_id'  => $cabangId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_cabangs');
    }
};
