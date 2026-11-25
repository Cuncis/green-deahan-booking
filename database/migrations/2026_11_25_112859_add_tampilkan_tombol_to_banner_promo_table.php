<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tombol di bawah gambar banner opsional: banner tetap bisa diklik lewat gambarnya.
     */
    public function up(): void
    {
        Schema::table('banner_promo', function (Blueprint $table) {
            $table->boolean('tampilkan_tombol')->default(true)->after('label_tombol');
        });
    }

    public function down(): void
    {
        Schema::table('banner_promo', function (Blueprint $table) {
            $table->dropColumn('tampilkan_tombol');
        });
    }
};
