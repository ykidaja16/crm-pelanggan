<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelompok_pelanggans', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->unique();
            $table->string('nama', 100)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('kelompok_pelanggans')->insert([
            [
                'kode' => 'mandiri',
                'nama' => 'Mandiri',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode' => 'klinisi',
                'nama' => 'Klinisi',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        if (!Schema::hasColumn('kunjungans', 'kelompok_pelanggan_id')) {
            Schema::table('kunjungans', function (Blueprint $table) {
                $table->foreignId('kelompok_pelanggan_id')->nullable()->after('kelompok_pelanggan')->constrained('kelompok_pelanggans');
            });
        }

        $mandiriId = DB::table('kelompok_pelanggans')->where('kode', 'mandiri')->value('id');
        $klinisiId = DB::table('kelompok_pelanggans')->where('kode', 'klinisi')->value('id');

        DB::table('kunjungans')
            ->whereRaw('LOWER(COALESCE(kelompok_pelanggan, "")) = "klinisi"')
            ->update(['kelompok_pelanggan_id' => $klinisiId]);

        DB::table('kunjungans')
            ->whereNull('kelompok_pelanggan_id')
            ->update(['kelompok_pelanggan_id' => $mandiriId]);

        if (Schema::hasColumn('pelanggans', 'kelompok_pelanggan')) {
            Schema::table('pelanggans', function (Blueprint $table) {
                $table->dropColumn('kelompok_pelanggan');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('pelanggans', 'kelompok_pelanggan')) {
            Schema::table('pelanggans', function (Blueprint $table) {
                $table->string('kelompok_pelanggan')->nullable()->after('class');
            });
        }

        if (Schema::hasColumn('kunjungans', 'kelompok_pelanggan_id')) {
            Schema::table('kunjungans', function (Blueprint $table) {
                $table->dropConstrainedForeignId('kelompok_pelanggan_id');
            });
        }

        Schema::dropIfExists('kelompok_pelanggans');
    }
};
