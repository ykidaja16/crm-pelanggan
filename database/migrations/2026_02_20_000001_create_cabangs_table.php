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
        Schema::create('cabangs', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique(); // LX, LZ, etc
            $table->string('nama'); // Ciliwung, Tangkuban Perahu, etc
            $table->string('tipe')->default('cabang'); // cabang, regional
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        // Insert default cabangs
        DB::table('cabangs')->insert([
            [
                'kode' => 'LX',
                'nama' => 'Ciliwung',
                'tipe' => 'cabang',
                'keterangan' => 'Lab Ciliwung',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode' => 'LZ',
                'nama' => 'Tangkuban Perahu',
                'tipe' => 'cabang',
                'keterangan' => 'Lab Tangkuban Perahu',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cabangs');
    }
};
