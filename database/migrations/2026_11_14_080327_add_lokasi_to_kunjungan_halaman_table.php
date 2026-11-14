<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lokasi kasar pengunjung dari header Cloudflare: kode negara dan kode
 * provinsi/wilayah. IP tetap tidak disimpan. Kunjungan sebelum kolom ini
 * ada tetap null (tidak diketahui).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kunjungan_halaman', function (Blueprint $table) {
            $table->char('negara', 2)->nullable()->after('sumber_host');
            $table->string('wilayah', 3)->nullable()->after('negara');

            $table->index(['tanggal', 'negara']);
        });
    }

    public function down(): void
    {
        Schema::table('kunjungan_halaman', function (Blueprint $table) {
            $table->dropIndex(['tanggal', 'negara']);
            $table->dropColumn(['negara', 'wilayah']);
        });
    }
};
