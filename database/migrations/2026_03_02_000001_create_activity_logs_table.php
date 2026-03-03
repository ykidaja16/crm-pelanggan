<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('username')->default('guest');
            $table->string('role')->default('-');
            $table->string('action', 50);       // login, logout, create, update, delete, import
            $table->string('module', 50);        // auth, pelanggan, user
            $table->string('description');       // Deskripsi singkat aktivitas
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Indexes untuk filter yang cepat
            $table->index('user_id');
            $table->index('action');
            $table->index('module');
            $table->index('created_at');

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
