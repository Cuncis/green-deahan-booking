<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Varian produk (warna, ukuran, dan sebagainya): satu pilihan per baris,
 * dengan harga opsional yang menimpa harga produk. products.variant_label
 * adalah judul pilihannya ("Warna"). order_items menyimpan nama varian
 * sebagai salinan, jadi pesanan lama tetap utuh kalau varian dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->unsignedInteger('sell_price')->nullable();
            $table->unsignedInteger('cost_price')->nullable();
            $table->boolean('is_available')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('variant_label', 30)->nullable()->after('specs');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('variant_id')->nullable()->after('product_id')->constrained('product_variants')->nullOnDelete();
            $table->string('variant_name', 80)->nullable()->after('product_name');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('variant_id');
            $table->dropColumn('variant_name');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('variant_label');
        });

        Schema::dropIfExists('product_variants');
    }
};
