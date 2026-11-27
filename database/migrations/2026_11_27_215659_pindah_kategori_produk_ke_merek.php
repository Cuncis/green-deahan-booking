<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Kategori toko berganti dari jenis barang ke kelompok merek. Produk lama
     * dipindah ke "Rank Product" supaya tetap tampil dan bisa disaring; admin
     * tinggal memilih ulang kategori yang tepat per produk.
     */
    public function up(): void
    {
        DB::table('products')
            ->whereIn('category', ['olahraga', 'rumput', 'lantai', 'perlengkapan', 'pencahayaan', 'perawatan'])
            ->update(['category' => 'rank-product']);
    }

    public function down(): void
    {
        // Kategori lama tidak bisa dipulihkan dari data baru.
    }
};
