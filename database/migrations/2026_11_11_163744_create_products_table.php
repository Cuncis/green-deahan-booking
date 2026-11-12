<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Produk toko. cost_price dan supplier_product_url PRIVAT: hanya untuk
 * superadmin, tidak pernah dikirim ke halaman pelanggan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 170)->unique();
            $table->enum('category', ['padel', 'badminton', 'futsal', 'perawatan']);
            $table->text('description')->nullable();
            $table->string('image_path', 500)->nullable();
            $table->unsignedInteger('sell_price');
            $table->unsignedInteger('cost_price');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('supplier_product_url', 500)->nullable();
            $table->unsignedInteger('weight_gram')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
