<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Katalog toko diperluas ke material lapangan: kategori jadi teks bebas
 * (daftarnya di Product::KATEGORI), plus satuan jual (m², set, sak, pcs),
 * badge opsional, dan harga coret opsional.
 *
 * Produk lama (padel/badminton/futsal/perawatan = perlengkapan olahraga)
 * dipindah ke kategori "olahraga", karena "perawatan" sekarang berarti
 * material perawatan lapangan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('category', 30)->change();
            $table->string('unit', 20)->default('pcs')->after('category');
            $table->string('badge', 30)->nullable()->after('unit');
            $table->unsignedInteger('compare_price')->nullable()->after('sell_price');
        });

        DB::table('products')->whereIn('category', ['padel', 'badminton', 'futsal', 'perawatan'])->update(['category' => 'olahraga']);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['unit', 'badge', 'compare_price']);
        });
    }
};
