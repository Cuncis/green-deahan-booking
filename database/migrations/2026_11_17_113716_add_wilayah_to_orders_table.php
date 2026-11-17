<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kecamatan, kelurahan, dan kode wilayah kelurahan pada pesanan toko.
 * Pesanan lama tetap null: alamatnya sudah lengkap di kolom address.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('district', 100)->nullable()->after('city');
            $table->string('village', 100)->nullable()->after('district');
            $table->string('wilayah_kode', 13)->nullable()->after('postal_code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['district', 'village', 'wilayah_kode']);
        });
    }
};
