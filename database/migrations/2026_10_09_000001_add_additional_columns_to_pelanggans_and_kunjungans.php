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
        // 1. Buat tabel master dokters
        if (!Schema::hasTable('dokters')) {
            Schema::create('dokters', function (Blueprint $table) {
                $table->id();
                $table->string('kode_dokter', 50)->nullable()->index();
                $table->string('nama_dokter', 150)->index();
                $table->timestamps();
            });
        }

        // 2. Buat tabel master marketings
        if (!Schema::hasTable('marketings')) {
            Schema::create('marketings', function (Blueprint $table) {
                $table->id();
                $table->string('nama_marketing', 150)->index();
                $table->timestamps();
            });
        }

        // 3. Tambahkan kolom ke tabel pelanggans
        Schema::table('pelanggans', function (Blueprint $table) {
            if (!Schema::hasColumn('pelanggans', 'kecamatan')) {
                $table->string('kecamatan', 100)->nullable()->after('kota');
            }
            if (!Schema::hasColumn('pelanggans', 'nip')) {
                $table->string('nip', 50)->nullable()->after('nik');
            }
            if (!Schema::hasColumn('pelanggans', 'jabatan')) {
                $table->string('jabatan', 100)->nullable()->after('nip');
            }
            if (!Schema::hasColumn('pelanggans', 'location')) {
                $table->string('location', 150)->nullable()->after('jabatan');
            }
        });

        // 4. Tambahkan kolom ke tabel kunjungans
        Schema::table('kunjungans', function (Blueprint $table) {
            if (!Schema::hasColumn('kunjungans', 'tipe_omset')) {
                $table->string('tipe_omset', 50)->nullable()->after('mou');
            }
            if (!Schema::hasColumn('kunjungans', 'dokter_id')) {
                $table->foreignId('dokter_id')->nullable()->after('tipe_omset')->constrained('dokters')->nullOnDelete();
            }
            if (!Schema::hasColumn('kunjungans', 'marketing_id')) {
                $table->foreignId('marketing_id')->nullable()->after('dokter_id')->constrained('marketings')->nullOnDelete();
            }
            if (!Schema::hasColumn('kunjungans', 'bruto')) {
                $table->double('bruto')->nullable()->after('marketing_id');
            }
            if (!Schema::hasColumn('kunjungans', 'disc')) {
                $table->double('disc')->nullable()->after('bruto');
            }
            if (!Schema::hasColumn('kunjungans', 'pay')) {
                $table->double('pay')->nullable()->after('biaya');
            }
            if (!Schema::hasColumn('kunjungans', 'jml_test')) {
                $table->integer('jml_test')->nullable()->after('pay');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kunjungans', function (Blueprint $table) {
            if (Schema::hasColumn('kunjungans', 'dokter_id')) {
                $table->dropForeign(['dokter_id']);
                $table->dropColumn('dokter_id');
            }
            if (Schema::hasColumn('kunjungans', 'marketing_id')) {
                $table->dropForeign(['marketing_id']);
                $table->dropColumn('marketing_id');
            }
            $table->dropColumn(array_filter([
                Schema::hasColumn('kunjungans', 'tipe_omset') ? 'tipe_omset' : null,
                Schema::hasColumn('kunjungans', 'bruto') ? 'bruto' : null,
                Schema::hasColumn('kunjungans', 'disc') ? 'disc' : null,
                Schema::hasColumn('kunjungans', 'pay') ? 'pay' : null,
                Schema::hasColumn('kunjungans', 'jml_test') ? 'jml_test' : null,
            ]));
        });

        Schema::table('pelanggans', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('pelanggans', 'kecamatan') ? 'kecamatan' : null,
                Schema::hasColumn('pelanggans', 'nip') ? 'nip' : null,
                Schema::hasColumn('pelanggans', 'jabatan') ? 'jabatan' : null,
                Schema::hasColumn('pelanggans', 'location') ? 'location' : null,
            ]));
        });

        Schema::dropIfExists('marketings');
        Schema::dropIfExists('dokters');
    }
};
