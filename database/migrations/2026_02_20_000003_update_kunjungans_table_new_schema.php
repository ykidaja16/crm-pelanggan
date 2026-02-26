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
        // Drop and recreate kunjungans table with new schema
        Schema::dropIfExists('kunjungans');
        
        Schema::create('kunjungans', function (Blueprint $table) {
            $table->id();
            $table->integer('no')->nullable(); // Row number from Excel
            $table->foreignId('pelanggan_id')->constrained('pelanggans')->cascadeOnDelete();
            $table->foreignId('cabang_id')->nullable()->constrained('cabangs');
            $table->date('tanggal_kunjungan');
            $table->double('biaya');
            $table->timestamps();
            
            // Indexes
            $table->index('pelanggan_id');
            $table->index('cabang_id');
            $table->index('tanggal_kunjungan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kunjungans');
        
        // Recreate old structure
        Schema::create('kunjungans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pelanggan_id')->constrained()->cascadeOnDelete();
            $table->date('tanggal_kunjungan');
            $table->double('biaya');
            $table->timestamps();
        });
    }
};
