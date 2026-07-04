<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Project ini tidak lagi punya opsi transfer manual, semua tenant
 * (termasuk Basic) sekarang bayar online lewat Midtrans (lihat
 * TenantFitur::presetUntukPaket()). Booking lama yang masih
 * tipe_pembayaran='manual' di-backfill jadi 'lunas' dulu sebelum enum-nya
 * diperketat, supaya tidak ada baris yang jadi tidak valid.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('booking')->where('tipe_pembayaran', 'manual')->update(['tipe_pembayaran' => 'lunas']);

        Schema::table('booking', function (Blueprint $table) {
            $table->enum('tipe_pembayaran', ['dp', 'lunas'])->change();
        });

        Schema::table('pembayaran', function (Blueprint $table) {
            $table->enum('metode', ['qris', 'ewallet', 'va'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking', function (Blueprint $table) {
            $table->enum('tipe_pembayaran', ['manual', 'dp', 'lunas'])->change();
        });

        Schema::table('pembayaran', function (Blueprint $table) {
            $table->enum('metode', ['qris', 'ewallet', 'va', 'manual'])->change();
        });
    }
};
