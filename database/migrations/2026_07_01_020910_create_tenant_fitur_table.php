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
        Schema::create('tenant_fitur', function (Blueprint $table) {
            $table->foreignId('tenant_id')->primary()->constrained('tenants')->cascadeOnDelete();

            // Fitur dasar, selalu true untuk semua paket
            $table->boolean('booking_online')->default(true);
            $table->boolean('notifikasi_whatsapp')->default(true);

            // Fitur paket Pro ke atas
            $table->boolean('pembayaran_online')->default(false);
            $table->boolean('dp_pembayaran')->default(false);
            $table->boolean('kode_promo')->default(false);
            $table->boolean('booking_berulang')->default(false);
            $table->boolean('rating_ulasan')->default(false);
            $table->boolean('laporan_pendapatan')->default(false);

            // Fitur khusus paket Premium
            $table->boolean('multi_cabang')->default(false);
            $table->boolean('sistem_membership')->default(false);
            $table->boolean('reminder_otomatis')->default(false);
            $table->boolean('manajemen_staf')->default(false);
            $table->boolean('analitik_lanjutan')->default(false);

            // Bukan boolean: null berarti unlimited (paket Premium)
            $table->unsignedInteger('batas_lapangan')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_fitur');
    }
};
