<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Wilayah;
use App\Services\FotoUploadService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Data contoh untuk melihat tampilan toko: supplier, produk berfoto
 * placeholder, dan pesanan di setiap status (termasuk pesanan multi paket,
 * ongkir tetap, perlu refund, dan riwayat kabar ke pelanggan).
 *
 * PERINGATAN: menghapus SEMUA data toko (pesanan, produk, foto, supplier)
 * sebelum mengisi ulang, lalu menolak jalan di production.
 *
 * php artisan db:seed --class=TokoDemoSeeder
 */
class TokoDemoSeeder extends Seeder
{
    /** @var array<string, string> warna foto placeholder per kategori */
    private const WARNA = [
        'rank-product' => '#006400',
        'ortuseight' => '#2E7D32',
        'adidas' => '#455A64',
        'saucony' => '#F9A825',
        'nike' => '#37474F',
        'reusch' => '#6D4C41',
    ];

    /** @var array<string, array<int, array{nama: string, nilai: string}>> spesifikasi contoh per produk */
    private const SPESIFIKASI = [
        'bola-padel' => [['nama' => 'Merek', 'nilai' => 'Contoh Padel'], ['nama' => 'Isi', 'nilai' => '3 bola per tabung'], ['nama' => 'Bahan', 'nilai' => 'Karet dan felt'], ['nama' => 'Asal produk', 'nilai' => 'Indonesia']],
        'overgrip' => [['nama' => 'Merek', 'nilai' => 'Contoh Grip'], ['nama' => 'Isi', 'nilai' => '6 overgrip'], ['nama' => 'Bahan', 'nilai' => 'Polyurethane']],
        'deker' => [['nama' => 'Merek', 'nilai' => 'Contoh Sport'], ['nama' => 'Bahan', 'nilai' => 'Plastik ABS dan busa EVA'], ['nama' => 'Asal produk', 'nilai' => 'Indonesia']],
        'kaos-kaki' => [['nama' => 'Merek', 'nilai' => 'Contoh Sport'], ['nama' => 'Panjang kaos kaki', 'nilai' => 'Setinggi betis'], ['nama' => 'Jenis kaos kaki', 'nilai' => 'Kaos kaki olahraga'], ['nama' => 'Bahan', 'nilai' => 'Nilon 84%, spandex 11%, karet 5%'], ['nama' => 'Asal produk', 'nilai' => 'Indonesia']],
        'rumput-futsal' => [['nama' => 'Tinggi serat', 'nilai' => '40 mm'], ['nama' => 'Bahan', 'nilai' => 'Polyethylene'], ['nama' => 'Lebar gulungan', 'nilai' => '2 meter']],
        'lampu' => [['nama' => 'Daya', 'nilai' => '200 watt'], ['nama' => 'Tahan cuaca', 'nilai' => 'IP65'], ['nama' => 'Garansi', 'nilai' => '1 tahun']],
    ];

    /** @var array<string, array{label: string, varian: array<int, array{0: string, 1: int|null, 2: int|null, 3: bool}>}> varian contoh per produk: nama, harga jual, harga modal, tersedia */
    private const VARIAN = [
        'kaos-kaki' => ['label' => 'Warna', 'varian' => [['HITAM', null, null, true], ['PUTIH', null, null, true], ['ABU-ABU', null, null, false]]],
        'deker' => ['label' => 'Ukuran', 'varian' => [['S', null, null, true], ['M', null, null, true], ['L', 79000, 52000, true]]],
        'knee-pad' => ['label' => 'Ukuran', 'varian' => [['M', null, null, true], ['L', null, null, true]]],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('TokoDemoSeeder menghapus data toko, jadi tidak boleh dijalankan di production.');

            return;
        }

        $this->hapusDataToko();

        DB::transaction(function () {
            $supplier = $this->supplier();
            $produk = $this->produk($supplier);
            $this->pesanan($produk);
            $this->promo();
        });

        $this->command?->info('Data contoh toko dibuat: '.Supplier::count().' supplier, '.Product::count().' produk, '.Order::count().' pesanan.');

        foreach (Order::orderBy('id')->get(['code', 'status']) as $order) {
            $this->command?->line("  {$order->code}  {$order->status->label()}");
        }
    }

    private function hapusDataToko(): void
    {
        $foto = app(FotoUploadService::class);

        foreach (DB::table('products')->pluck('image_path')->merge(DB::table('product_images')->pluck('path'))->filter()->unique() as $url) {
            $foto->hapus($url);
        }

        DB::transaction(function () {
            foreach (['order_messages', 'order_shipments', 'order_items', 'orders', 'product_images', 'products', 'suppliers', 'promo_codes'] as $tabel) {
                DB::table($tabel)->delete();
            }
        });
    }

    /**
     * Kode promo contoh: satu persen dengan batas, satu nominal dengan minimal belanja, satu yang sudah berakhir.
     */
    private function promo(): void
    {
        PromoCode::create(['code' => 'HEMAT10', 'description' => 'Data contoh: diskon 10 persen.', 'type' => PromoCode::PERCENT, 'value' => 10, 'max_discount' => 50000]);
        PromoCode::create(['code' => 'POTONG25', 'description' => 'Data contoh: potongan Rp25.000 untuk belanja minimal Rp200.000.', 'type' => PromoCode::FIXED, 'value' => 25000, 'min_subtotal' => 200000, 'quota' => 50]);
        PromoCode::create(['code' => 'AKHIRTAHUN', 'description' => 'Data contoh: promo yang sudah berakhir.', 'type' => PromoCode::PERCENT, 'value' => 15, 'ends_on' => now()->subDays(10)->toDateString()]);
    }

    /**
     * @return array<string, Supplier>
     */
    private function supplier(): array
    {
        return [
            'a' => Supplier::create(['name' => 'Sport Hub Bekasi', 'platform' => 'shopee', 'shop_url' => 'https://shopee.co.id/contoh-sport-hub', 'whatsapp' => '081200000001', 'origin_postal_code' => '17113', 'notes' => 'Data contoh: aksesori olahraga, dikirim dari Bekasi.']),
            'b' => Supplier::create(['name' => 'Gudang Rumput Surabaya', 'platform' => 'direct', 'shop_url' => null, 'whatsapp' => '081200000002', 'origin_postal_code' => '60111', 'notes' => 'Data contoh: rumput, interlock, material perawatan.']),
            'c' => Supplier::create(['name' => 'Lampu dan Net Jakarta', 'platform' => 'tokopedia', 'shop_url' => 'https://tokopedia.com/contoh-lampu-net', 'whatsapp' => '081200000003', 'origin_postal_code' => '11470', 'notes' => 'Data contoh: lampu sorot, net, gawang.']),
        ];
    }

    /**
     * @param  array<string, Supplier>  $supplier
     * @return array<string, Product>
     */
    private function produk(array $supplier): array
    {
        // [kunci, nama, kategori, satuan, jual, coret, modal, berat gram, badge, aktif, supplier, jumlah foto, deskripsi]
        $daftar = [
            ['bola-padel', 'Bola Padel Premium (Tabung Isi 3)', 'rank-product', 'tabung', 119000, 135000, 92000, 260, 'Terlaris', true, 'a', 3, "Satu tabung berisi 3 bola padel bertekanan, pantulan stabil untuk latihan dan pertandingan.\n\nCocok untuk lapangan padel indoor maupun outdoor."],
            ['overgrip', 'Overgrip Raket Padel dan Badminton (Isi 6)', 'rank-product', 'pak', 45000, null, 28000, 90, null, true, 'a', 2, 'Enam overgrip anti licin dan menyerap keringat, mudah dipasang di raket padel maupun badminton.'],
            ['deker', 'Deker Futsal Pelindung Tulang Kering', 'rank-product', 'pcs', 69000, 85000, 45000, 220, 'Promo', true, 'a', 2, 'Pelindung tulang kering ringan dengan bantalan busa, nyaman dipakai selama pertandingan.'],
            ['kaos-kaki', 'Kaos Kaki Bola Anti Selip', 'rank-product', 'pcs', 39000, null, 24000, 120, 'Baru', true, 'a', 1, 'Kaos kaki panjang dengan grip anti selip di telapak, bahan cepat kering.'],
            ['knee-pad', 'Knee Pad Busa Classic', 'rank-product', 'pcs', 89000, null, 60000, 180, null, true, 'a', 1, 'Pelindung lutut berbahan busa tebal untuk kiper futsal dan pemain padel.'],
            ['botol', 'Botol Minum Olahraga 1 Liter', 'rank-product', 'pcs', 55000, null, 33000, 250, null, true, 'a', 1, 'Botol 1 liter bebas BPA dengan penanda volume dan tutup anti bocor.'],
            ['rumput-futsal', 'Rumput Sintetis Futsal 40mm', 'ortuseight', 'm²', 185000, 210000, 150000, 4500, 'Terlaris', true, 'b', 3, "Rumput sintetis tinggi 40mm dengan serat padat, tahan gesekan untuk lapangan futsal.\n\nHarga per meter persegi."],
            ['rumput-padel', 'Rumput Sintetis Padel 12mm', 'ortuseight', 'm²', 165000, null, 130000, 3200, 'Baru', true, 'b', 1, 'Rumput sintetis 12mm dengan permukaan rata dan kecepatan bola yang konsisten untuk lapangan padel.'],
            ['interlock', 'Lantai Interlock Futsal', 'adidas', 'm²', 275000, 300000, 220000, 6000, null, true, 'b', 2, 'Lantai modular interlock tahan benturan, mudah dipasang tanpa lem.'],
            ['lampu', 'Lampu Sorot LED 200W', 'saucony', 'unit', 1250000, 1400000, 1000000, 2800, 'Promo', true, 'c', 2, 'Lampu sorot LED 200W tahan cuaca, terang merata untuk lapangan outdoor.'],
            ['net-padel', 'Net Padel Standar', 'nike', 'set', 1900000, null, 1550000, 2200, null, true, 'c', 1, 'Net padel standar lengkap dengan tali penegang.'],
            ['pasir', 'Pasir Silika (Sak)', 'reusch', 'sak', 65000, null, 48000, 25000, null, true, 'b', 1, 'Pasir silika untuk pengisi rumput sintetis, per sak 25 kg.'],
            ['gawang', 'Gawang Futsal Standar', 'nike', 'pasang', 4500000, null, 3700000, 18000, null, false, 'c', 0, 'Produk nonaktif contoh: tidak tampil di toko.'],
        ];

        $hasil = [];

        foreach ($daftar as [$kunci, $nama, $kategori, $satuan, $jual, $coret, $modal, $berat, $badge, $aktif, $kodeSupplier, $jumlahFoto, $deskripsi]) {
            $product = Product::create([
                'name' => $nama,
                'slug' => Str::slug($nama),
                'category' => $kategori,
                'unit' => $satuan,
                'badge' => $badge,
                'description' => $deskripsi,
                'specs' => self::SPESIFIKASI[$kunci] ?? [],
                'sell_price' => $jual,
                'compare_price' => $coret,
                'cost_price' => $modal,
                'supplier_id' => $supplier[$kodeSupplier]->id,
                'supplier_product_url' => $supplier[$kodeSupplier]->shop_url,
                'weight_gram' => $berat,
                'is_active' => $aktif,
            ]);

            $alamat = [];

            for ($nomor = 1; $nomor <= $jumlahFoto; $nomor++) {
                $alamat[] = $this->buatFoto($kunci, $nama, $kategori, $nomor);
            }

            foreach ($alamat as $urutan => $path) {
                $product->images()->create(['path' => $path, 'urutan' => $urutan]);
            }

            $product->forceFill(['image_path' => $alamat[0] ?? null])->save();

            if (isset(self::VARIAN[$kunci])) {
                $product->update(['variant_label' => self::VARIAN[$kunci]['label']]);

                foreach (self::VARIAN[$kunci]['varian'] as $urutan => [$namaVarian, $jual, $modalVarian, $tersedia]) {
                    $product->variants()->create(['name' => $namaVarian, 'sell_price' => $jual, 'cost_price' => $modalVarian, 'is_available' => $tersedia, 'sort_order' => $urutan]);
                }
            }

            $hasil[$kunci] = $product;
        }

        return $hasil;
    }

    /**
     * Foto placeholder SVG (tidak butuh internet atau ekstensi gambar).
     * Alamatnya relatif supaya jalan di host lokal mana pun.
     */
    private function buatFoto(string $kunci, string $nama, string $kategori, int $nomor): string
    {
        $warna = self::WARNA[$kategori] ?? '#006400';
        $baris = array_slice(explode("\n", wordwrap($nama, 18, "\n", true)), 0, 4);
        $teks = '';

        foreach ($baris as $indeks => $isi) {
            $y = 360 + $indeks * 64;
            $teks .= '<text x="400" y="'.$y.'" text-anchor="middle" font-family="Arial, sans-serif" font-size="52" font-weight="700" fill="#ffffff">'.htmlspecialchars($isi, ENT_XML1).'</text>';
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="'.$warna.'"/><stop offset="1" stop-color="#101010"/></linearGradient></defs>'
            .'<rect width="800" height="800" fill="url(#g)"/>'.$teks
            .'<text x="400" y="700" text-anchor="middle" font-family="Arial, sans-serif" font-size="30" fill="#ffffff" opacity="0.7">Foto contoh '.$nomor.'</text></svg>';

        $path = "produk/demo-{$kunci}-{$nomor}.svg";
        Storage::disk('public')->put($path, $svg);

        return "/storage/{$path}";
    }

    /**
     * @param  array<string, Product>  $produk
     */
    private function pesanan(array $produk): void
    {
        $admin = User::where('is_superadmin', true)->first();
        $jne = ['jne', 'reg', 'JNE Reguler', '2 - 3 hari'];
        $sicepat = ['sicepat', 'reg', 'SiCepat Reguler', '1 - 2 hari'];

        // 1. Menunggu pembayaran.
        $this->buat('Budi Santoso', '32.75', OrderStatus::PendingPayment, 2, [['bola-padel', 2]], 12500, $jne, $produk);

        // 2. Dibayar, dua supplier berbeda (dua paket).
        $this->buat('Siti Rahmawati', '35.78', OrderStatus::Paid, 1, [['overgrip', 2], ['rumput-futsal', 10]], 107500, $jne, $produk);

        // 3. Dibayar, ongkir tetap karena Biteship gagal saat checkout.
        $this->buat('Agus Prasetyo', '31.74', OrderStatus::Paid, 5, [['deker', 1, 'L'], ['knee-pad', 1, 'M']], 15000, ['darurat', 'tetap', 'Ongkir tetap (kurir dipilih toko)', ''], $produk);

        // 4. Diproses ke supplier.
        $diproses = $this->buat('Dewi Lestari', '32.73', OrderStatus::OrderedToSupplier, 27, [['bola-padel', 1], ['kaos-kaki', 2, 'HITAM']], 17500, $sicepat, $produk, ['supplier_order_ref' => 'SHP-2411-7731']);
        $diproses->messages()->create(['user_id' => $admin?->id, 'channel' => 'whatsapp', 'template' => 'diproses', 'body' => "Halo {$diproses->customer_name}, pesanan {$diproses->code} sedang kami proses. Kami kabari lagi begitu barangnya dikirim."]);

        // 5. Dikirim dalam dua paket dari dua lokasi.
        $dikirim = $this->buat('Rizky Ramadhan', '51.71', OrderStatus::Shipped, 72, [['overgrip', 3], ['net-padel', 1]], 135000, $jne, $produk, ['supplier_order_ref' => 'SHP-2411-7612, TKP-88121']);
        $dikirim->shipments()->create(['courier' => 'JNE', 'tracking_number' => 'JNE0012345678', 'shipped_from' => 'Sport Hub Bekasi']);
        $dikirim->shipments()->create(['courier' => 'J&T', 'tracking_number' => 'JT7001234567', 'shipped_from' => 'Gudang Jakarta']);

        // 6. Dikirim satu paket.
        $satuPaket = $this->buat('Maya Anggraini', '12.71', OrderStatus::Shipped, 96, [['knee-pad', 2]], 38000, $sicepat, $produk, ['supplier_order_ref' => 'SHP-2411-7544']);
        $satuPaket->shipments()->create(['courier' => 'SiCepat', 'tracking_number' => '000123456789', 'shipped_from' => 'Sport Hub Bekasi']);

        // 7. Selesai, dengan riwayat kabar WhatsApp dan email.
        $selesai = $this->buat('Fajar Nugroho', '32.75', OrderStatus::Completed, 216, [['bola-padel', 3]], 12500, $jne, $produk, ['supplier_order_ref' => 'SHP-2410-6920']);
        $selesai->shipments()->create(['courier' => 'JNE', 'tracking_number' => 'JNE0099887766', 'shipped_from' => 'Sport Hub Bekasi']);
        $selesai->messages()->create(['user_id' => $admin?->id, 'channel' => 'whatsapp', 'template' => 'dikirim', 'body' => "Halo {$selesai->customer_name}, pesanan {$selesai->code} sudah dikirim.\n\nResi: JNE JNE0099887766"]);
        $selesai->messages()->create(['user_id' => $admin?->id, 'channel' => 'email', 'template' => 'selesai', 'subject' => "Pesanan {$selesai->code} sudah selesai", 'body' => "Halo {$selesai->customer_name}, pesanan {$selesai->code} sudah selesai. Terima kasih sudah belanja di Green Deahan Sport."]);

        // 8. Dibatalkan setelah dibayar, refund belum diproses (muncul di filter "Perlu refund").
        $this->buat('Putri Wulandari', '35.78', OrderStatus::Cancelled, 50, [['rumput-futsal', 20]], 190000, $jne, $produk, ['cancel_reason' => 'Stok supplier habis', 'cancelled' => 48]);

        // 9. Dibatalkan dan refund sudah selesai.
        $this->buat('Hendra Kusuma', '31.74', OrderStatus::Cancelled, 144, [['botol', 2]], 12500, $jne, $produk, ['cancel_reason' => 'Alamat tidak terjangkau kurir', 'cancelled' => 140, 'refunded' => 120]);

        // 10. Kedaluwarsa (tidak dibayar).
        $this->buat('Lina Marlina', '32.73', OrderStatus::Expired, 80, [['lampu', 1]], 45000, $jne, $produk);
    }

    /**
     * @param  array<int, array{0: string, 1: int, 2?: string}>  $baris  kunci produk, jumlah, nama varian (opsional)
     * @param  array{0: string, 1: string, 2: string, 3: string}  $kurir
     * @param  array<string, Product>  $produk
     * @param  array<string, mixed>  $tambahan
     */
    private function buat(string $nama, string $kodeKota, OrderStatus $status, int $jamLalu, array $baris, int $ongkir, array $kurir, array $produk, array $tambahan = []): Order
    {
        $dibuat = Carbon::now()->subHours($jamLalu);
        $subtotal = array_sum(array_map(fn (array $item) => $this->harga($produk[$item[0]], $item[2] ?? null)[0] * $item[1], $baris));
        $sudahDibayar = in_array($status, [OrderStatus::Paid, OrderStatus::OrderedToSupplier, OrderStatus::Shipped, OrderStatus::Completed], true)
            || ($status === OrderStatus::Cancelled && isset($tambahan['cancel_reason']));
        $slug = Str::slug($nama, '.');

        $order = Order::create([
            'code' => Order::buatKode(),
            'customer_name' => $nama,
            'phone' => '0812'.str_pad((string) (abs(crc32($nama)) % 100000000), 8, '0', STR_PAD_LEFT),
            'email' => "{$slug}@example.com",
            'address' => 'Jl. Contoh No. '.(abs(crc32($nama)) % 90 + 10).', RT 02/RW 05',
            ...$this->wilayah($kodeKota),
            'subtotal' => $subtotal,
            'shipping_cost' => $ongkir,
            'courier_code' => $kurir[0],
            'courier_service_code' => $kurir[1],
            'courier_service_name' => $kurir[2],
            'shipping_duration' => $kurir[3] ?: null,
            'total' => $subtotal + $ongkir,
            'status' => $status,
            'mayar_payment_id' => (string) Str::uuid(),
            'mayar_payment_link' => 'https://testingmayar.myr.id/pl/'.Str::lower(Str::random(6)),
            'paid_at' => $sudahDibayar ? $dibuat->copy()->addMinutes(10) : null,
            'supplier_order_ref' => $tambahan['supplier_order_ref'] ?? null,
            'cancel_reason' => $tambahan['cancel_reason'] ?? null,
            'cancelled_at' => isset($tambahan['cancelled']) ? Carbon::now()->subHours($tambahan['cancelled']) : null,
            'refunded_at' => isset($tambahan['refunded']) ? Carbon::now()->subHours($tambahan['refunded']) : null,
        ]);

        foreach ($baris as $item) {
            [$kunci, $qty] = $item;
            $namaVarian = $item[2] ?? null;
            [$jual, $modal] = $this->harga($produk[$kunci], $namaVarian);

            $order->items()->create([
                'product_id' => $produk[$kunci]->id,
                'variant_id' => $namaVarian ? $produk[$kunci]->variants->firstWhere('name', $namaVarian)?->id : null,
                'product_name' => $produk[$kunci]->name,
                'variant_name' => $namaVarian,
                'unit_price' => $jual,
                'unit_cost' => $modal,
                'qty' => $qty,
            ]);
        }

        $order->forceFill(['created_at' => $dibuat, 'updated_at' => $dibuat])->saveQuietly();

        return $order;
    }

    /**
     * Harga jual dan modal satu baris pesanan: harga varian kalau ada, kalau tidak harga produk.
     *
     * @return array{0: int, 1: int}
     */
    private function harga(Product $product, ?string $namaVarian): array
    {
        $varian = $namaVarian ? $product->variants->firstWhere('name', $namaVarian) : null;

        return [$varian?->sell_price ?? $product->sell_price, $varian?->cost_price ?? $product->cost_price];
    }

    /**
     * Alamat dari satu kelurahan sungguhan (berkode pos) di kota itu, supaya
     * kode wilayah dan kode pos cocok dengan data checkout.
     *
     * @return array<string, string>
     */
    private function wilayah(string $kodeKota): array
    {
        $kelurahan = Wilayah::query()->where('level', Wilayah::LEVEL_KELURAHAN)->where('kode', 'like', "{$kodeKota}.%")->whereNotNull('kodepos')->orderBy('kode')->first();

        if ($kelurahan === null) {
            return ['province' => 'DKI Jakarta', 'city' => 'Jakarta Selatan', 'district' => 'Tebet', 'village' => 'Tebet Barat', 'postal_code' => '12810', 'wilayah_kode' => '31.74.01.1001'];
        }

        [$provinsi, $kota, $kecamatan] = [Str::before($kelurahan->kode, '.'), $kodeKota, Str::beforeLast($kelurahan->kode, '.')];
        $nama = Wilayah::whereIn('kode', [$provinsi, $kota, $kecamatan])->pluck('nama', 'kode');

        return [
            'province' => $nama[$provinsi] ?? '',
            'city' => $nama[$kota] ?? '',
            'district' => $nama[$kecamatan] ?? '',
            'village' => $kelurahan->nama,
            'postal_code' => $kelurahan->kodepos,
            'wilayah_kode' => $kelurahan->kode,
        ];
    }
}
