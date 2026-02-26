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
        Schema::create('pelanggan_class_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pelanggan_id')->constrained('pelanggans')->cascadeOnDelete();
            $table->string('previous_class')->nullable();
            $table->string('new_class');
            $table->dateTime('changed_at');
            $table->foreignId('changed_by')->nullable()->constrained('users');
            $table->text('reason')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('pelanggan_id');
            $table->index('changed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelanggan_class_histories');
    }
};
