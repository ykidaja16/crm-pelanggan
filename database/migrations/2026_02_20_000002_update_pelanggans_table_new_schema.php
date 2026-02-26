<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop existing table data and recreate with new schema
        // Since we can delete data, we'll drop and recreate
        
        // First drop foreign key constraints if any
        Schema::table('kunjungans', function (Blueprint $table) {
            if (Schema::hasColumn('kunjungans', 'pelanggan_id')) {
                $table->dropForeign(['pelanggan_id']);
            }
        });
        
        // Drop and recreate pelanggans table with new schema
        Schema::dropIfExists('pelanggans');
        
        Schema::create('pelanggans', function (Blueprint $table) {
            $table->id();
            $table->string('pid')->unique();
            $table->string('no_ktp')->nullable();
            $table->foreignId('cabang_id')->nullable()->constrained('cabangs');
            $table->string('nama');
            $table->string('no_telp')->nullable();
            $table->date('dob')->nullable();
            $table->text('alamat')->nullable();
            $table->string('kota')->nullable();
            $table->string('class')->default('Potensial');
            $table->integer('total_kedatangan')->default(0);
            $table->double('total_biaya')->default(0);
            $table->timestamps();
            
            // Indexes
            $table->index('cabang_id');
            $table->index('class');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelanggans');
        
        // Recreate old structure
        Schema::create('pelanggans', function (Blueprint $table) {
            $table->id();
            $table->string('nik')->unique();
            $table->string('nama');
            $table->text('alamat')->nullable();
            $table->string('class')->default('Silver');
            $table->timestamps();
        });
    }
};
