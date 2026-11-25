<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Banner pop-up promo di situs korporat (greendeahan.com). Bukan data
     * tenant, jadi tanpa tenant_id.
     */
    public function up(): void
    {
        Schema::create('banner_promo', function (Blueprint $table) {
            $table->id();
            $table->string('judul', 150);
            $table->string('foto_url', 500);
            $table->string('tautan', 500)->nullable();
            $table->string('label_tombol', 40)->nullable();
            $table->dateTime('mulai_pada')->nullable();
            $table->dateTime('berakhir_pada')->nullable();
            $table->string('target', 10)->default('semua');
            $table->string('frekuensi', 10)->default('sekali');
            $table->unsignedTinyInteger('tunda_detik')->default(2);
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();

            $table->index(['status_aktif', 'mulai_pada', 'berakhir_pada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banner_promo');
    }
};
