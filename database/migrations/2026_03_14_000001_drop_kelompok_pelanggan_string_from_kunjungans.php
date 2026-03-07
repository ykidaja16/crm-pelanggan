<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration ini menghapus kolom lama `kelompok_pelanggan` (string/teks) dari tabel kunjungans.
 * 
 * Kolom ini sudah digantikan oleh `kelompok_pelanggan_id` (foreign key ke tabel kelompok_pelanggans)
 * yang dibuat di migration 2026_03_13_000003.
 * 
 * Sebelum drop, data dari kolom lama sudah dimigrasikan ke kelompok_pelanggan_id
 * di migration sebelumnya (2026_03_13_000003).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('kunjungans', 'kelompok_pelanggan')) {
            Schema::table('kunjungans', function (Blueprint $table) {
                $table->dropColumn('kelompok_pelanggan');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('kunjungans', 'kelompok_pelanggan')) {
            Schema::table('kunjungans', function (Blueprint $table) {
                $table->string('kelompok_pelanggan')->default('mandiri')->after('biaya');
            });
        }
    }
};
