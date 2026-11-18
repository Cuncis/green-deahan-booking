<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Asal pengiriman dropship: kode pos asal per produk (menimpa kode pos
 * supplier), dan outlet/cabang yang benar-benar mengirim, dicatat admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('origin_postal_code', 5)->nullable()->after('weight_gram');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipped_from', 120)->nullable()->after('tracking_number');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('shipped_from');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('origin_postal_code');
        });
    }
};
