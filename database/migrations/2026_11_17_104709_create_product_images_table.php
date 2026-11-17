<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Banyak foto per produk toko. products.image_path tetap ada sebagai foto
 * utama (foto pertama) supaya daftar produk, keranjang, dan meta SEO tidak
 * perlu berubah. Foto lama disalin jadi baris pertama masing-masing produk.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path', 500);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'urutan']);
        });

        DB::table('products')->whereNotNull('image_path')->orderBy('id')->each(function (object $produk) {
            DB::table('product_images')->insert([
                'product_id' => $produk->id,
                'path' => $produk->image_path,
                'urutan' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
