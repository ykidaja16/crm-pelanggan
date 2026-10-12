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
        // 1. Tambah kolom alamat, kecamatan, kota, provinsi ke tabel cabangs
        Schema::table('cabangs', function (Blueprint $table) {
            if (!Schema::hasColumn('cabangs', 'alamat')) {
                $table->text('alamat')->nullable()->after('nama');
            }
            if (!Schema::hasColumn('cabangs', 'kecamatan')) {
                $table->string('kecamatan', 100)->nullable()->after('alamat');
            }
            if (!Schema::hasColumn('cabangs', 'kota')) {
                $table->string('kota', 100)->nullable()->after('kecamatan');
            }
            if (!Schema::hasColumn('cabangs', 'provinsi')) {
                $table->string('provinsi', 100)->nullable()->after('kota');
            }
        });

        // 2. Tambah kolom jenis_kelamin ke tabel pelanggans
        Schema::table('pelanggans', function (Blueprint $table) {
            if (!Schema::hasColumn('pelanggans', 'jenis_kelamin')) {
                $table->string('jenis_kelamin', 20)->nullable()->after('nama');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cabangs', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('cabangs', 'alamat') ? 'alamat' : null,
                Schema::hasColumn('cabangs', 'kecamatan') ? 'kecamatan' : null,
                Schema::hasColumn('cabangs', 'kota') ? 'kota' : null,
                Schema::hasColumn('cabangs', 'provinsi') ? 'provinsi' : null,
            ]));
        });

        Schema::table('pelanggans', function (Blueprint $table) {
            if (Schema::hasColumn('pelanggans', 'jenis_kelamin')) {
                $table->dropColumn('jenis_kelamin');
            }
        });
    }
};
