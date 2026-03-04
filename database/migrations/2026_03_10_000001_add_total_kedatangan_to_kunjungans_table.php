<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('kunjungans', function (Blueprint $table) {
            // Tambah kolom total_kedatangan setelah kolom 'no'
            // Default 1 untuk data kunjungan yang sudah ada (manual entry = 1 kunjungan)
            $table->integer('total_kedatangan')->default(1)->after('no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kunjungans', function (Blueprint $table) {
            $table->dropColumn('total_kedatangan');
        });
    }
};
