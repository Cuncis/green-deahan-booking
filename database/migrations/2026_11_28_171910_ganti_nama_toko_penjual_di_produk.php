<?php

use App\Services\ImporProdukHtmlService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Produk yang diimpor sebelum ada penggantian nama toko penjual masih bisa
     * menyebut "RANK Sports" di nama, deskripsi, atau spesifikasinya. Ganti
     * dengan nama toko kita, aturan yang sama dengan impor HTML.
     */
    public function up(): void
    {
        DB::table('products')
            ->where(fn ($q) => $q->where('name', 'like', '%rank%')->orWhere('description', 'like', '%rank%')->orWhere('specs', 'like', '%rank%'))
            ->orderBy('id')
            ->each(function (object $produk) {
                $specs = json_decode((string) $produk->specs, true);
                $specs = is_array($specs)
                    ? array_map(fn ($baris) => is_array($baris) && isset($baris['nilai']) ? [...$baris, 'nilai' => ImporProdukHtmlService::gantiNamaToko((string) $baris['nilai'])] : $baris, $specs)
                    : null;

                DB::table('products')->where('id', $produk->id)->update([
                    'name' => ImporProdukHtmlService::gantiNamaToko((string) $produk->name),
                    'description' => $produk->description === null ? null : ImporProdukHtmlService::gantiNamaToko((string) $produk->description),
                    'specs' => $specs === null ? $produk->specs : json_encode($specs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
            });
    }

    public function down(): void
    {
        // Teks lama tidak disimpan, jadi tidak bisa dikembalikan.
    }
};
