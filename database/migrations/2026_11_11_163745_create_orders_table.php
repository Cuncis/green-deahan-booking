<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pesanan toko. Semua uang dalam rupiah (integer). Kode pesanan acak
 * (GD-YYYYMMDD-XXXXXX) supaya halaman /pesanan/{code} tidak bisa ditebak.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('customer_name', 150);
            $table->string('phone', 20);
            $table->string('email', 150);
            $table->text('address');
            $table->string('city', 100);
            $table->string('province', 100);
            $table->string('postal_code', 10);
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('shipping_cost');
            $table->unsignedInteger('total');
            $table->string('status', 30)->default('pending_payment')->index();
            $table->string('mayar_payment_id', 100)->nullable()->unique();
            $table->string('mayar_payment_link', 500)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('supplier_order_ref', 150)->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->string('courier', 50)->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
