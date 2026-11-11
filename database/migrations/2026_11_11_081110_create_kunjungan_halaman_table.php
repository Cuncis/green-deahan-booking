<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan kunjungan halaman situs korporat (greendeahan.com) untuk grafik
 * pengunjung di dashboard superadmin. Bukan data tenant, jadi tanpa
 * tenant_id (sama seperti artikel dan galeri_items). Tidak menyimpan IP:
 * pengunjung hanya dikenali lewat hash harian yang tidak bisa dibalik.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kunjungan_halaman', function (Blueprint $table) {
            $table->id();
            $table->string('path', 255);
            $table->date('tanggal');
            $table->char('pengunjung_hash', 64);
            $table->string('perangkat', 10);
            $table->string('sumber_host', 100)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['tanggal', 'path']);
            $table->index(['tanggal', 'pengunjung_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kunjungan_halaman');
    }
};
