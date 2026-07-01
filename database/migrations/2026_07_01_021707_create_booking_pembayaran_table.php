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
        Schema::create('booking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('slot_id')->constrained('jadwal_slot')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('kode_promo_id')->nullable()->constrained('kode_promo')->cascadeOnDelete();
            $table->foreignId('membership_id')->nullable()->constrained('membership')->cascadeOnDelete();
            $table->string('kode_booking')->unique();
            $table->unsignedInteger('harga_normal');
            $table->unsignedInteger('diskon_jumlah')->default(0);
            $table->unsignedInteger('total_bayar');
            $table->enum('tipe_pembayaran', ['manual', 'dp', 'lunas']);
            $table->enum('status_booking', ['menunggu', 'dikonfirmasi', 'selesai', 'dibatalkan'])->default('menunggu');
            $table->uuid('recurring_group_id')->nullable();
            $table->unsignedInteger('recurring_ke')->nullable();
            $table->timestamps();
        });

        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('booking')->cascadeOnDelete();
            $table->enum('metode', ['qris', 'ewallet', 'va', 'manual']);
            $table->unsignedInteger('jumlah');
            $table->enum('status', ['pending', 'sukses', 'gagal', 'kadaluarsa'])->default('pending');
            $table->string('kode_transaksi_gateway')->nullable();
            $table->json('raw_response_gateway')->nullable();
            $table->timestamp('waktu_bayar')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
        Schema::dropIfExists('booking');
    }
};
