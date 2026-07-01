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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('nama_bisnis');
            $table->string('domain')->unique();
            $table->enum('paket', ['basic', 'pro', 'premium'])->default('basic');
            $table->boolean('status_aktif')->default(true);
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_berakhir')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('warna_utama')->default('#3A6B4A');
            $table->string('warna_aksen')->nullable();
            $table->string('whatsapp_admin')->nullable();
            $table->string('email_admin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
