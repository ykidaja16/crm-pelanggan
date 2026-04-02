<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel snapshot data pelanggan SEBELUM import dilakukan.
     * Digunakan untuk mengembalikan data pelanggan ke kondisi sebelum import
     * ketika IT melakukan rollback.
     *
     * - is_new_pelanggan = true  → pelanggan baru dari import, rollback = soft delete
     * - is_new_pelanggan = false → pelanggan lama yang diupdate, rollback = restore nilai lama
     */
    public function up(): void
    {
        Schema::create('import_batch_pelanggan_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('import_batch_id', 36);              // Referensi ke import_batches.batch_id
            $table->unsignedBigInteger('pelanggan_id');         // Pelanggan yang terpengaruh
            $table->boolean('is_new_pelanggan')->default(false); // TRUE jika pelanggan baru dibuat saat import ini
            $table->integer('total_kedatangan_before')->default(0); // Nilai sebelum import
            $table->decimal('total_biaya_before', 15, 2)->default(0); // Nilai sebelum import
            $table->string('class_before', 50)->nullable();     // Kelas sebelum import
            $table->timestamps();

            $table->index('import_batch_id');
            $table->foreign('pelanggan_id')->references('id')->on('pelanggans')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batch_pelanggan_snapshots');
    }
};
