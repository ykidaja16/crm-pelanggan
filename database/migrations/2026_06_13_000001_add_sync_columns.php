<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pelanggan_class_histories', function (Blueprint $table) {
            $table->boolean('is_sync')->default(false)->after('reason');
        });

        Schema::table('pelanggans', function (Blueprint $table) {
            $table->string('pre_sync_class')->nullable()->after('class');
        });
    }

    public function down(): void
    {
        Schema::table('pelanggan_class_histories', function (Blueprint $table) {
            $table->dropColumn('is_sync');
        });

        Schema::table('pelanggans', function (Blueprint $table) {
            $table->dropColumn('pre_sync_class');
        });
    }
};
