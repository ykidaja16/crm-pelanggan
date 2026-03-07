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
        Schema::table('pelanggans', function (Blueprint $table) {
            if (!Schema::hasColumn('pelanggans', 'kelompok_pelanggan')) {
                $table->string('kelompok_pelanggan')->default('mandiri')->after('class');
            }

            if (!Schema::hasColumn('pelanggans', 'is_pelanggan_khusus')) {
                $table->boolean('is_pelanggan_khusus')->default(false)->after('kelompok_pelanggan');
            }

            if (!Schema::hasColumn('pelanggans', 'kategori_khusus')) {
                $table->string('kategori_khusus')->nullable()->after('is_pelanggan_khusus');
            }
        });

        Schema::table('kunjungans', function (Blueprint $table) {
            if (!Schema::hasColumn('kunjungans', 'kelompok_pelanggan')) {
                $table->string('kelompok_pelanggan')->default('mandiri')->after('biaya');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kunjungans', function (Blueprint $table) {
            if (Schema::hasColumn('kunjungans', 'kelompok_pelanggan')) {
                $table->dropColumn('kelompok_pelanggan');
            }
        });

        Schema::table('pelanggans', function (Blueprint $table) {
            if (Schema::hasColumn('pelanggans', 'kategori_khusus')) {
                $table->dropColumn('kategori_khusus');
            }

            if (Schema::hasColumn('pelanggans', 'is_pelanggan_khusus')) {
                $table->dropColumn('is_pelanggan_khusus');
            }

            if (Schema::hasColumn('pelanggans', 'kelompok_pelanggan')) {
                $table->dropColumn('kelompok_pelanggan');
            }
        });
    }
};
