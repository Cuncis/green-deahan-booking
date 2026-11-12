<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Data contoh toko online: 1 supplier dan 5 produk. Harga hanya CONTOH,
 * ganti lewat /superadmin/toko/produk sebelum dipakai sungguhan. Foto
 * dibiarkan kosong supaya halaman toko memakai gambar placeholder.
 *
 * php artisan db:seed --class=TokoSeeder
 */
class TokoSeeder extends Seeder
{
    public function run(): void
    {
        $supplier = Supplier::firstOrCreate(
            ['name' => 'Supplier Contoh'],
            ['platform' => 'direct', 'shop_url' => null, 'whatsapp' => null, 'notes' => 'Data contoh, ganti dengan supplier asli.'],
        );

        $produk = [
            ['Bola Padel (Tabung Isi 3)', 'padel', 95000, 75000, 250, 'Satu tabung berisi 3 bola padel bertekanan, cocok untuk latihan dan main santai.'],
            ['Shuttlecock (1 Slop Isi 12)', 'badminton', 110000, 90000, 180, 'Satu slop berisi 12 shuttlecock bulu untuk latihan rutin.'],
            ['Overgrip Padel dan Badminton (Isi 3)', 'perawatan', 45000, 30000, 60, 'Tiga overgrip untuk raket padel atau badminton, nyaman dan tidak licin.'],
            ['Bola Futsal', 'futsal', 220000, 170000, 420, 'Bola futsal ukuran 4 dengan pantulan rendah, untuk lapangan indoor.'],
            ['Rompi Tim (Set Isi 10)', 'futsal', 180000, 130000, 900, 'Sepuluh rompi tim untuk latihan, bahan ringan dan cepat kering.'],
        ];

        foreach ($produk as [$nama, $kategori, $jual, $modal, $berat, $deskripsi]) {
            Product::updateOrCreate(
                ['slug' => Str::slug($nama)],
                [
                    'name' => $nama,
                    'category' => $kategori,
                    'description' => $deskripsi,
                    'image_path' => null,
                    'sell_price' => $jual,
                    'cost_price' => $modal,
                    'supplier_id' => $supplier->id,
                    'supplier_product_url' => null,
                    'weight_gram' => $berat,
                    'is_active' => true,
                ],
            );
        }
    }
}
