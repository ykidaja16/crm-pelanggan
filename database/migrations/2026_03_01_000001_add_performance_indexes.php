<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan indexes untuk meningkatkan performa query saat data semakin banyak.
     *
     * Indexes yang ditambahkan:
     * - pelanggans.nama          : untuk LIKE search dan ORDER BY nama
     * - pelanggans.total_biaya   : untuk filter range omset
     * - pelanggans.total_kedatangan : untuk filter range kedatangan
     * - pelanggans.(class, cabang_id) : composite untuk filter kelas + cabang
     * - kunjungans.(pelanggan_id, tanggal_kunjungan) : composite untuk subquery MAX(tanggal_kunjungan)
     * - kunjungans.(tanggal_kunjungan, pelanggan_id) : untuk GROUP BY bulan/tahun di dashboard
     */
    public function up(): void
    {
        Schema::table('pelanggans', function (Blueprint $table) {
            // Index untuk pencarian nama (LIKE '%...%' tidak bisa pakai index penuh,
            // tapi LIKE 'nama%' dan ORDER BY nama bisa memanfaatkan index ini)
            if (!$this->indexExists('pelanggans', 'pelanggans_nama_index')) {
                $table->index('nama', 'pelanggans_nama_index');
            }

            // Index untuk filter range omset (WHERE total_biaya < / BETWEEN / >=)
            if (!$this->indexExists('pelanggans', 'pelanggans_total_biaya_index')) {
                $table->index('total_biaya', 'pelanggans_total_biaya_index');
            }

            // Index untuk filter range kedatangan (WHERE total_kedatangan <= / BETWEEN / >)
            if (!$this->indexExists('pelanggans', 'pelanggans_total_kedatangan_index')) {
                $table->index('total_kedatangan', 'pelanggans_total_kedatangan_index');
            }

            // Composite index untuk filter kelas + cabang (query paling umum di laporan)
            if (!$this->indexExists('pelanggans', 'pelanggans_class_cabang_index')) {
                $table->index(['class', 'cabang_id'], 'pelanggans_class_cabang_index');
            }
        });

        Schema::table('kunjungans', function (Blueprint $table) {
            // Composite index untuk subquery correlated:
            // SELECT MAX(tanggal_kunjungan) FROM kunjungans WHERE pelanggan_id = X
            // MySQL akan menggunakan index ini untuk lookup cepat per pelanggan
            if (!$this->indexExists('kunjungans', 'kunjungans_pelanggan_tanggal_index')) {
                $table->index(['pelanggan_id', 'tanggal_kunjungan'], 'kunjungans_pelanggan_tanggal_index');
            }

            // Composite index untuk GROUP BY bulan/tahun di dashboard:
            // SELECT MONTH(tanggal_kunjungan), COUNT(DISTINCT pelanggan_id) GROUP BY MONTH(...)
            if (!$this->indexExists('kunjungans', 'kunjungans_tanggal_pelanggan_index')) {
                $table->index(['tanggal_kunjungan', 'pelanggan_id'], 'kunjungans_tanggal_pelanggan_index');
            }
        });
    }

    /**
     * Hapus indexes yang ditambahkan.
     */
    public function down(): void
    {
        Schema::table('pelanggans', function (Blueprint $table) {
            $table->dropIndexIfExists('pelanggans_nama_index');
            $table->dropIndexIfExists('pelanggans_total_biaya_index');
            $table->dropIndexIfExists('pelanggans_total_kedatangan_index');
            $table->dropIndexIfExists('pelanggans_class_cabang_index');
        });

        Schema::table('kunjungans', function (Blueprint $table) {
            $table->dropIndexIfExists('kunjungans_pelanggan_tanggal_index');
            $table->dropIndexIfExists('kunjungans_tanggal_pelanggan_index');
        });
    }

    /**
     * Cek apakah index sudah ada (agar migration idempotent / aman dijalankan ulang).
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = \Illuminate\Support\Facades\DB::select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$indexName]
        );
        return count($indexes) > 0;
    }
};
