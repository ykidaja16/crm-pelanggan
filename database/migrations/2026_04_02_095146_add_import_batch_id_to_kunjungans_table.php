<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom import_batch_id ke tabel kunjungans.
     * Kolom ini NULLABLE agar data kunjungan lama tidak terpengaruh.
     * Data kunjungan lama akan memiliki import_batch_id = NULL.
     * Data kunjungan baru dari import akan memiliki import_batch_id = UUID.
     */
    public function up(): void
    {
        Schema::table('kunjungans', function (Blueprint $table) {
            $table->string('import_batch_id', 36)->nullable()->after('kelompok_pelanggan_id');
            $table->index('import_batch_id', 'idx_kunjungans_import_batch_id');
        });
    }

    public function down(): void
    {
        Schema::table('kunjungans', function (Blueprint $table) {
            $table->dropIndex('idx_kunjungans_import_batch_id');
            $table->dropColumn('import_batch_id');
        });
    }
};
