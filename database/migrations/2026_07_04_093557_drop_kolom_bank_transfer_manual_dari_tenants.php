<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom ini dulu dipakai <x-manual-transfer-info> untuk tampilkan info
 * rekening tenant Basic di halaman booking, sebelum transfer manual dihapus
 * total dari project (semua tenant sekarang bayar online lewat Midtrans).
 * Tidak ada tenant yang pernah mengisi kolom ini (dicek dulu sebelum bikin
 * migration ini), aman dihapus tanpa kehilangan data.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['bank_nama', 'bank_no_rekening', 'bank_pemilik_rekening']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('bank_nama')->nullable();
            $table->string('bank_no_rekening')->nullable();
            $table->string('bank_pemilik_rekening')->nullable();
        });
    }
};
