<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ongkir Biteship: kode pos asal per supplier, dan kurir serta layanan yang
 * dipilih pelanggan di checkout (ongkirnya sendiri tetap di shipping_cost).
 * Kolom orders.courier dan tracking_number tetap dipakai admin untuk resi
 * pengiriman supplier.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('origin_postal_code', 5)->nullable()->after('whatsapp');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('courier_code', 30)->nullable()->after('shipping_cost');
            $table->string('courier_service_code', 30)->nullable()->after('courier_code');
            $table->string('courier_service_name', 120)->nullable()->after('courier_service_code');
            $table->string('shipping_duration', 50)->nullable()->after('courier_service_name');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['courier_code', 'courier_service_code', 'courier_service_name', 'shipping_duration']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('origin_postal_code');
        });
    }
};
