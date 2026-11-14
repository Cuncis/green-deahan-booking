<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Data contoh toko online. SEMUA harga hanya CONTOH, ganti lewat
 * /superadmin/toko/produk sebelum dipakai sungguhan.
 *
 * - 5 perlengkapan olahraga: dijual (aktif) untuk uji coba alur belanja.
 * - 13 material lapangan dari desain referensi Fuzio: harga placeholder,
 *   sengaja DISEMBUNYIKAN (is_active = false) sampai harga dan modal asli
 *   diisi admin.
 *
 * Foto dibiarkan kosong supaya halaman toko memakai gambar placeholder.
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

        // [nama, kategori, satuan, jual, coret, modal, berat gram, badge, aktif, deskripsi]
        $produk = [
            ['Bola Padel (Tabung Isi 3)', 'olahraga', 'tabung', 95000, null, 75000, 250, null, true, 'Satu tabung berisi 3 bola padel bertekanan, cocok untuk latihan dan main santai.'],
            ['Shuttlecock (1 Slop Isi 12)', 'olahraga', 'slop', 110000, null, 90000, 180, null, true, 'Satu slop berisi 12 shuttlecock bulu untuk latihan rutin.'],
            ['Overgrip Padel dan Badminton (Isi 3)', 'olahraga', 'pcs', 45000, null, 30000, 60, null, true, 'Tiga overgrip untuk raket padel atau badminton, nyaman dan tidak licin.'],
            ['Bola Futsal', 'olahraga', 'pcs', 220000, null, 170000, 420, null, true, 'Bola futsal ukuran 4 dengan pantulan rendah, untuk lapangan indoor.'],
            ['Rompi Tim (Set Isi 10)', 'olahraga', 'set', 180000, null, 130000, 900, null, true, 'Sepuluh rompi tim untuk latihan, bahan ringan dan cepat kering.'],

            ['Rumput Sintetis Futsal 40mm', 'rumput', 'm²', 185000, 210000, 0, 0, 'Terlaris', false, 'Harga contoh, belum dijual.'],
            ['Rumput Sintetis Mini Soccer 50mm', 'rumput', 'm²', 225000, null, 0, 0, null, false, 'Harga contoh, belum dijual.'],
            ['Artificial Grass Padel 12mm', 'rumput', 'm²', 165000, null, 0, 0, 'Baru', false, 'Harga contoh, belum dijual.'],
            ['Lantai Interlock Futsal', 'lantai', 'm²', 275000, 300000, 0, 0, null, false, 'Harga contoh, belum dijual.'],
            ['Lantai Interlock Badminton', 'lantai', 'm²', 295000, null, 0, 0, null, false, 'Harga contoh, belum dijual.'],
            ['Gawang Futsal Standar', 'perlengkapan', 'pasang', 4500000, null, 0, 0, null, false, 'Harga contoh, belum dijual.'],
            ['Jaring Pengaman Lapangan', 'perlengkapan', 'm²', 18000, null, 0, 0, null, false, 'Harga contoh, belum dijual.'],
            ['Tiang & Net Badminton', 'perlengkapan', 'set', 2750000, null, 0, 0, null, false, 'Harga contoh, belum dijual.'],
            ['Net Padel', 'perlengkapan', 'set', 1900000, null, 0, 0, null, false, 'Harga contoh, belum dijual.'],
            ['Lampu Sorot LED 200W', 'pencahayaan', 'unit', 1250000, 1400000, 0, 0, 'Promo', false, 'Harga contoh, belum dijual.'],
            ['Pasir Silika', 'perawatan', 'sak', 65000, null, 0, 0, null, false, 'Harga contoh, belum dijual.'],
            ['Butiran Karet (Rubber Granule) 25kg', 'perawatan', 'sak', 140000, null, 0, 0, null, false, 'Harga contoh, belum dijual.'],
            ['Lem Rumput Sintetis 10kg', 'perawatan', 'pail', 450000, null, 0, 0, null, false, 'Harga contoh, belum dijual.'],
        ];

        foreach ($produk as [$nama, $kategori, $satuan, $jual, $coret, $modal, $berat, $badge, $aktif, $deskripsi]) {
            // firstOrCreate: harga yang sudah diubah admin tidak ditimpa.
            $product = Product::firstOrCreate(
                ['slug' => Str::slug($nama)],
                [
                    'name' => $nama,
                    'category' => $kategori,
                    'unit' => $satuan,
                    'badge' => $badge,
                    'description' => $deskripsi,
                    'image_path' => null,
                    'sell_price' => $jual,
                    'compare_price' => $coret,
                    'cost_price' => $modal,
                    'supplier_id' => $supplier->id,
                    'supplier_product_url' => null,
                    'weight_gram' => $berat,
                    'is_active' => $aktif,
                ],
            );

            // Produk contoh lama (sebelum ada kolom satuan) masih "pcs": isi satuannya.
            if (! $product->wasRecentlyCreated && $product->unit === 'pcs' && $satuan !== 'pcs') {
                $product->update(['unit' => $satuan]);
            }
        }
    }
}
