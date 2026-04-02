<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel untuk mencatat setiap sesi import Excel.
     * Setiap kali user melakukan import, 1 record batch dibuat.
     * Digunakan oleh IT untuk melihat riwayat import dan melakukan rollback.
     */
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id', 36)->unique();          // UUID unik per sesi import
            $table->unsignedBigInteger('user_id')->nullable(); // User yang melakukan import
            $table->unsignedBigInteger('cabang_id')->nullable(); // Cabang yang dipilih saat import
            $table->string('filename');                         // Nama file Excel yang diimport
            $table->integer('total_rows')->default(0);          // Jumlah baris yang berhasil diproses
            $table->enum('status', ['completed', 'rolled_back'])->default('completed');
            $table->timestamp('imported_at');                   // Waktu import dilakukan
            $table->timestamp('rolled_back_at')->nullable();    // Waktu rollback dilakukan
            $table->unsignedBigInteger('rolled_back_by')->nullable(); // User IT yang melakukan rollback
            $table->timestamps();

            $table->index('batch_id');
            $table->index('status');
            $table->index('imported_at');

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('cabang_id')->references('id')->on('cabangs')->nullOnDelete();
            $table->foreign('rolled_back_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
