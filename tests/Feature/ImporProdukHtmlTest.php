<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ImporProdukHtmlService;
use Database\Seeders\SupplierOutletSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Impor produk dari HTML halaman Shopee di /superadmin/toko/produk/tambah.
 */
class ImporProdukHtmlTest extends TestCase
{
    use RefreshDatabase;

    private const GAMBAR = 'https://down-id.img.susercontent.com/file/id-11134207-81ztf-muk8x6i76a6d14';

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->is_superadmin = true;
        $user->save();

        return $user;
    }

    private function html(): string
    {
        return file_get_contents(base_path('tests/Fixtures/shopee-produk.html'));
    }

    private function png(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
    }

    public function test_parser_mengambil_data_penting_saja(): void
    {
        $hasil = (new ImporProdukHtmlService)->baca($this->html());

        $this->assertSame('Jonas Knee Pad / Knee Busa Classic V2 - Black', $hasil['nama']);
        $this->assertNull($hasil['kategori']);
        $this->assertSame('https://shopee.co.id/product/119935350/7422104174', $hasil['linkSupplier']);
        $this->assertSame('RANK Sports Official', $hasil['namaSupplier']);
        $this->assertSame(self::GAMBAR, $hasil['gambarUrl']);
        $this->assertSame([
            self::GAMBAR,
            'https://down-id.img.susercontent.com/file/id-11134207-81ztf-fotokedua',
            'https://down-id.img.susercontent.com/file/id-11134207-81ztf-fotoketiga',
        ], $hasil['gambarUrls']);
        $this->assertSame(175310, $hasil['hargaShopee']);
        $this->assertSame(199800, $hasil['hargaRetail']);
    }

    public function test_deskripsi_dibersihkan_dari_bagian_khas_toko_shopee(): void
    {
        $deskripsi = (new ImporProdukHtmlService)->baca($this->html())['deskripsi'];

        $this->assertStringContainsString('Pelindung Lutut / Deker Lutut yang familiar dipakai untuk keeper futsal', $deskripsi);
        $this->assertStringContainsString('aman dari benturan lantai', $deskripsi);
        $this->assertStringContainsString("\n\n", $deskripsi);

        foreach (['Rp', 'RETAIL', 'ORIGINAL 100%', 'Note', 'RANK', '#LevelUp', 'LevelUp', 'HAPPY', 'Stok terbatas', "\u{00A0}"] as $dilarang) {
            $this->assertStringNotContainsString($dilarang, $deskripsi, "Deskripsi masih memuat {$dilarang}");
        }

        // Blok "Note" dipertahankan sebagai catatan toko kita, poin yang terpotong digabung lagi.
        $this->assertStringContainsString("Catatan:\n- Produk di Green Deahan Sport 100% original, jaminan uang kembali jika produk tidak original.\n- Untuk produk yang rusak, mohon sertakan video unboxing saat komplain.", $deskripsi);
    }

    public function test_catatan_penjual_dipertahankan_dan_penutup_promosi_dibuang(): void
    {
        $html = '<h1>Ortuseight Sepatu Futsal Zenith IN</h1><section><h2>Deskripsi Produk</h2><div><div><div>'
            .'<p>ORIGINAL 100%</p><p> </p><p>HARGA RETAIL Rp.329.000,-  </p><p> </p><p>Detail Size :</p><p>37  : 24.1 cm</p><p>38  : 24.5 cm</p><p> </p>'
            .'<p>Note : </p><p>- Produk di RANK Sports 100% original, jaminan uang kembali jika produk tidak original.</p>'
            .'<p>- Jika ragu ingin memilih size, dapat konsultasi ke admin mengenai detail size.</p>'
            .'<p>- Penukaran produk jika pengiriman TIDAK SESUAI DENGAN PESANAN. </p><p> </p>'
            .'<p>Stok terbatas jangan sampai kehabisan !! #LevelUpYourGame</p><p> </p><p>HAPPY SHOPPING SOB :)</p>'
            .'</div></div></div></section>';

        $this->assertSame(
            "Detail Size :\n37 : 24.1 cm\n38 : 24.5 cm\n\nCatatan:\n"
            ."- Produk di Green Deahan Sport 100% original, jaminan uang kembali jika produk tidak original.\n"
            ."- Jika ragu ingin memilih size, dapat konsultasi ke admin mengenai detail size.\n"
            .'- Penukaran produk jika pengiriman TIDAK SESUAI DENGAN PESANAN.',
            (new ImporProdukHtmlService)->baca($html)['deskripsi'],
        );
    }

    public function test_spesifikasi_dan_dikirim_dari_dibaca_dari_halaman_shopee(): void
    {
        $html = '<h1>Ortuseight Kaos Kaki Helios</h1><div><h2>Spesifikasi Produk</h2><div>'
            .'<div><h3>Kategori</h3><div><a>Shopee</a> &gt; <a>Kaos Kaki Bola</a></div></div>'
            .'<div><h3>Stok</h3><div>TERSEDIA</div></div>'
            .'<div><h3>Merek</h3><a>ORTUSEIGHT</a></div>'
            .'<div><h3>Asal Produk</h3><div>Indonesia</div></div>'
            .'<div><h3>Bahan</h3><div>Nilon</div></div>'
            .'<div><h3>Dikirim Dari</h3><div>KOTA METRO</div></div>'
            .'</div></div>';

        $hasil = (new ImporProdukHtmlService)->baca($html);

        $this->assertSame([
            ['nama' => 'Merek', 'nilai' => 'ORTUSEIGHT'],
            ['nama' => 'Asal Produk', 'nilai' => 'Indonesia'],
            ['nama' => 'Bahan', 'nilai' => 'Nilon'],
        ], $hasil['spesifikasi']);
        $this->assertSame('KOTA METRO', $hasil['dikirimDari']);
    }

    public function test_tanpa_bagian_spesifikasi_hasilnya_kosong(): void
    {
        $hasil = (new ImporProdukHtmlService)->baca('<h1>Hanya Judul</h1>');

        $this->assertSame([], $hasil['spesifikasi']);
        $this->assertNull($hasil['dikirimDari']);
    }

    public function test_spesifikasi_dibatasi_jumlah_dan_panjangnya(): void
    {
        $baris = '';

        for ($nomor = 1; $nomor <= 25; $nomor++) {
            $baris .= "<div><h3>Atribut {$nomor}</h3><div>".str_repeat('x', 200).'</div></div>';
        }

        $hasil = (new ImporProdukHtmlService)->baca("<h1>Produk</h1><div><h2>Spesifikasi Produk</h2><div>{$baris}</div></div>");

        $this->assertCount(20, $hasil['spesifikasi']);
        $this->assertSame(150, mb_strlen($hasil['spesifikasi'][0]['nilai']));
    }

    public function test_klaim_original_dan_harga_tanpa_rp_dibuang_dan_baris_bahan_tetap_terpisah(): void
    {
        $html = '<h1>Kaos Kaki</h1><div><h2>Deskripsi Produk</h2><div><p>100% Original Product (Tidak Original Uang Kembali Gan)</p><p>
</p><p>harga bandrol 40.000</p><p>
</p><p>MATERIAL </p><p>84% Nylon</p><p>11% Spandex</p><p>5% Rubber</p><p>
</p><p>Dilengkapi Ort-Grip pada telapak agar tidak licin</p><p>
</p><p>HAPPY SHOPPING..</p><p>#kaoskaki #ortuseight</p></div></div>';

        $deskripsi = (new ImporProdukHtmlService)->baca($html)['deskripsi'];

        $this->assertSame("MATERIAL\n84% Nylon\n11% Spandex\n5% Rubber\n\nDilengkapi Ort-Grip pada telapak agar tidak licin", $deskripsi);
    }

    public function test_baris_dengan_angka_biasa_tidak_ikut_terbuang(): void
    {
        $html = '<h1>Overgrip</h1><div><h2>Deskripsi Produk</h2><div><p>Isi 3 pcs per pak, tebal 0.6 mm.</p><p>Harga per pak sudah termasuk 3 pcs.</p></div></div>';

        $deskripsi = (new ImporProdukHtmlService)->baca($html)['deskripsi'];

        $this->assertStringContainsString('Isi 3 pcs per pak, tebal 0.6 mm.', $deskripsi);
        $this->assertStringContainsString('Harga per pak sudah termasuk 3 pcs.', $deskripsi);
    }

    public function test_daftar_outlet_penjual_tidak_ikut_ke_deskripsi(): void
    {
        $html = '<h1>Overgrip Raket</h1><div><h2>Deskripsi Produk</h2><div><p>Overgrip empuk anti licin untuk raket padel.<br><br>Isi 3 pcs per pak.</p>'
            .'<p><strong>98 Outlet tersebar di Indonesia<br></strong><br><strong>DKI JAKARTA :</strong></p>'
            .'<p><strong>📍</strong><a href="https://maps.app.goo.gl/abc">RANK Sports Ciledug, Jakarta Selatan</a><br>📍<a href="https://maps.app.goo.gl/def">RANK Sports Condet, Jakarta Selatan</a></p></div></div>';

        $deskripsi = (new ImporProdukHtmlService)->baca($html)['deskripsi'];

        $this->assertStringContainsString('Overgrip empuk anti licin', $deskripsi);
        $this->assertStringContainsString('Isi 3 pcs per pak', $deskripsi);

        foreach (['Outlet', 'RANK Sports', 'DKI JAKARTA', '📍', 'maps.app'] as $dilarang) {
            $this->assertStringNotContainsString($dilarang, $deskripsi);
        }
    }

    public function test_ulasan_dan_nama_pembeli_tidak_ikut_terbaca(): void
    {
        $json = json_encode((new ImporProdukHtmlService)->baca($this->html()));

        $this->assertStringNotContainsString('fitraalfandi6', $json);
        $this->assertStringNotContainsString('<', $json);
        $this->assertStringNotContainsString('sangat nyaman', $json);
    }

    public function test_kategori_ditebak_dari_merek_di_nama_produk(): void
    {
        $service = new ImporProdukHtmlService;

        $this->assertSame('ortuseight', $service->baca('<h1>Ortuseight Sepatu Futsal Zenith IN</h1>')['kategori']);
        $this->assertSame('nike', $service->baca('<h1>Sepatu Bola NIKE Mercurial</h1>')['kategori']);
        $this->assertNull($service->baca('<h1>Sepatu Futsal Tanpa Merek Dikenal</h1>')['kategori']);
    }

    public function test_nama_toko_penjual_diganti_nama_toko_kita_tapi_merek_barang_tetap(): void
    {
        $this->assertSame('Produk di Green Deahan Sport 100% original', ImporProdukHtmlService::gantiNamaToko('Produk di RANK Sports 100% original'));
        $this->assertSame('Dijual oleh Green Deahan Sport', ImporProdukHtmlService::gantiNamaToko('Dijual oleh rank sport'));
        $this->assertSame('Green Deahan Sport', ImporProdukHtmlService::gantiNamaToko('RANK Sports Official'));
        $this->assertSame('Untuk Sobat Green Deahan', ImporProdukHtmlService::gantiNamaToko('Untuk #SobatRANK #LevelUpYourGame'));
        $this->assertSame('RANK Jersey Futsal Pro', ImporProdukHtmlService::gantiNamaToko('RANK Jersey Futsal Pro'));
    }

    public function test_impor_mengganti_rank_sports_di_nama_deskripsi_dan_spesifikasi(): void
    {
        $html = <<<'HTML'
            <h1>Kaos Kaki Futsal Eksklusif RANK Sports</h1>
            <section><h2>Spesifikasi Produk</h2><div><div><h3>Merek</h3><div>ORTUSEIGHT</div></div><div><h3>Penjual</h3><div>RANK Sports Official</div></div></div></section>
            <section><h2>Deskripsi Produk</h2><div><p>Kaos kaki nyaman dari RANK Sports untuk latihan.</p><p></p><p>Bahan katun.</p></div></section>
            HTML;

        $hasil = (new ImporProdukHtmlService)->baca($html);

        $this->assertSame('Kaos Kaki Futsal Eksklusif Green Deahan Sport', $hasil['nama']);
        $this->assertStringContainsString('Kaos kaki nyaman dari Green Deahan Sport untuk latihan.', (string) $hasil['deskripsi']);
        $this->assertStringNotContainsStringIgnoringCase('rank', (string) $hasil['deskripsi']);
        $this->assertContains(['nama' => 'Penjual', 'nilai' => 'Green Deahan Sport'], $hasil['spesifikasi']);
        $this->assertContains(['nama' => 'Merek', 'nilai' => 'ORTUSEIGHT'], $hasil['spesifikasi']);
    }

    public function test_bagian_yang_tidak_ada_dikembalikan_null_bukan_galat(): void
    {
        $hasil = (new ImporProdukHtmlService)->baca('<h1>Hanya Judul</h1>');

        $this->assertSame('Hanya Judul', $hasil['nama']);
        $this->assertNull($hasil['deskripsi']);
        $this->assertNull($hasil['kategori']);
        $this->assertNull($hasil['linkSupplier']);
        $this->assertNull($hasil['namaSupplier']);
        $this->assertNull($hasil['gambarUrl']);
        $this->assertSame([], $hasil['gambarUrls']);
        $this->assertNull($hasil['hargaShopee']);
        $this->assertNull($hasil['hargaRetail']);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function alamatGambar(): array
    {
        return [
            'cdn shopee' => [self::GAMBAR, true],
            'tanpa subdomain' => ['https://susercontent.com/file/abc', true],
            'http biasa' => ['http://down-id.img.susercontent.com/file/abc', false],
            'host lain' => ['https://contoh.com/file/abc', false],
            'host menyerupai' => ['https://susercontent.com.penyerang.test/file/abc', false],
            'dengan kredensial' => ['https://user:pass@down-id.img.susercontent.com/file/abc', false],
            'port khusus' => ['https://down-id.img.susercontent.com:8443/file/abc', false],
            'bukan folder file' => ['https://down-id.img.susercontent.com/admin', false],
            'alamat lokal' => ['https://127.0.0.1/file/abc', false],
            'kosong' => ['', false],
        ];
    }

    #[DataProvider('alamatGambar')]
    public function test_hanya_cdn_shopee_yang_diizinkan_untuk_gambar(string $url, bool $diizinkan): void
    {
        $this->assertSame($diizinkan, ImporProdukHtmlService::gambarDiizinkan($url));
    }

    public function test_hanya_superadmin_yang_bisa_impor(): void
    {
        $this->post('/superadmin/toko/produk/impor', ['html' => $this->html()])->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->post('/superadmin/toko/produk/impor', ['html' => $this->html()])->assertForbidden();
    }

    public function test_impor_mengisi_form_tanpa_membuat_produk(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'rank sports official']);
        $admin = $this->superadmin();

        $this->actingAs($admin)->post('/superadmin/toko/produk/impor', ['html' => $this->html()])
            ->assertRedirect(route('superadmin.toko.produk.create'));

        $this->assertSame(0, Product::count());

        $this->actingAs($admin)->withSession(['impor' => [
            ...(new ImporProdukHtmlService)->baca($this->html()),
            'supplierId' => $supplier->id,
        ]])->get(route('superadmin.toko.produk.create'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('superadmin/Toko/Produk/Form')
                ->where('impor.nama', 'Jonas Knee Pad / Knee Busa Classic V2 - Black')
                ->where('impor.kategori', null)
                ->where('impor.supplierId', $supplier->id)
                ->where('impor.hargaShopee', 175310)
                ->where('impor.hargaRetail', 199800)
                ->where('imporUrl', route('superadmin.toko.produk.impor'))
            );
    }

    public function test_impor_mencocokkan_supplier_tanpa_membedakan_huruf_besar_kecil(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'rank sports official']);

        $this->actingAs($this->superadmin())->post('/superadmin/toko/produk/impor', ['html' => $this->html()])
            ->assertSessionHas('impor.supplierId', $supplier->id);
    }

    public function test_impor_tanpa_supplier_yang_cocok_tidak_membuat_supplier_baru(): void
    {
        $this->actingAs($this->superadmin())->post('/superadmin/toko/produk/impor', ['html' => $this->html()])
            ->assertSessionHas('impor.nama');

        $this->assertNull(session('impor')['supplierId']);
        $this->assertSame(0, Supplier::count());
    }

    public function test_impor_menolak_isian_kosong_dan_html_tanpa_nama_produk(): void
    {
        $admin = $this->superadmin();

        $this->actingAs($admin)->post('/superadmin/toko/produk/impor', ['html' => ''])->assertSessionHasErrors('html');
        $this->actingAs($admin)->post('/superadmin/toko/produk/impor', ['html' => '<p>bukan halaman produk</p>'])->assertSessionHasErrors('html');
        $this->actingAs($admin)->post('/superadmin/toko/produk/impor', ['html' => str_repeat('a', 3000001)])->assertSessionHasErrors('html');
    }

    public function test_simpan_produk_impor_mengunduh_foto_dari_cdn_shopee(): void
    {
        Storage::fake('public');
        Http::fake([self::GAMBAR => Http::response($this->png(), 200, ['Content-Type' => 'image/png'])]);

        $this->actingAs($this->superadmin())->post('/superadmin/toko/produk', [
            'name' => 'Jonas Knee Pad',
            'category' => 'rank-product',
            'unit' => 'pcs',
            'sell_price' => 199000,
            'cost_price' => 150000,
            'weight_gram' => 200,
            'is_active' => '0',
            'gambar_url' => self::GAMBAR,
        ])->assertRedirect(route('superadmin.toko.produk'));

        $produk = Product::sole();
        $this->assertNotNull($produk->image_path);
        $this->assertFalse($produk->is_active);
        $this->assertStringNotContainsString('susercontent', $produk->image_path);
        $this->assertCount(1, Storage::disk('public')->allFiles('produk'));
        Http::assertSentCount(1);
    }

    public function test_foto_unggahan_dan_foto_impor_disimpan_bersama_dengan_unggahan_di_belakang(): void
    {
        Storage::fake('public');
        Http::fake([self::GAMBAR => Http::response($this->png(), 200)]);

        $this->actingAs($this->superadmin())->post('/superadmin/toko/produk', [
            'name' => 'Jonas Knee Pad',
            'category' => 'rank-product',
            'unit' => 'pcs',
            'sell_price' => 199000,
            'cost_price' => 150000,
            'weight_gram' => 200,
            'is_active' => '1',
            'gambar_url' => self::GAMBAR,
            'foto' => UploadedFile::fake()->image('sendiri.jpg'),
        ])->assertRedirect(route('superadmin.toko.produk'));

        $this->assertCount(2, Storage::disk('public')->allFiles('produk'));
        $this->assertSame(2, Product::sole()->images()->count());
    }

    public function test_alamat_foto_di_luar_cdn_shopee_ditolak_tanpa_mengunduh(): void
    {
        Http::fake();

        $this->actingAs($this->superadmin())->post('/superadmin/toko/produk', [
            'name' => 'Jonas Knee Pad',
            'category' => 'rank-product',
            'unit' => 'pcs',
            'sell_price' => 199000,
            'cost_price' => 150000,
            'weight_gram' => 200,
            'is_active' => '1',
            'gambar_url' => 'http://169.254.169.254/latest/meta-data',
        ])->assertSessionHasErrors('gambar_url');

        Http::assertNothingSent();
        $this->assertSame(0, Product::count());
    }

    public function test_unduhan_foto_gagal_atau_bukan_gambar_tidak_membuat_produk(): void
    {
        Storage::fake('public');
        $data = [
            'name' => 'Jonas Knee Pad',
            'category' => 'rank-product',
            'unit' => 'pcs',
            'sell_price' => 199000,
            'cost_price' => 150000,
            'weight_gram' => 200,
            'is_active' => '1',
            'gambar_url' => self::GAMBAR,
        ];
        $admin = $this->superadmin();

        Http::fake([self::GAMBAR => Http::response('', 404)]);
        $this->actingAs($admin)->post('/superadmin/toko/produk', $data)->assertSessionHasErrors('foto');

        Http::fake([self::GAMBAR => Http::response('<?php echo 1;', 200)]);
        $this->actingAs($admin)->post('/superadmin/toko/produk', $data)->assertSessionHasErrors('foto');

        Http::fake([self::GAMBAR => Http::response('', 302, ['Location' => 'http://127.0.0.1/rahasia.png'])]);
        $this->actingAs($admin)->post('/superadmin/toko/produk', $data)->assertSessionHasErrors('foto');

        $this->assertSame(0, Product::count());
        $this->assertCount(0, Storage::disk('public')->allFiles('produk'));
    }

    public function test_html_mentah_dan_alamat_foto_impor_tidak_tersimpan_di_produk(): void
    {
        Storage::fake('public');
        Http::fake([self::GAMBAR => Http::response($this->png(), 200)]);

        $this->actingAs($this->superadmin())->post('/superadmin/toko/produk', [
            'name' => 'Jonas Knee Pad',
            'category' => 'rank-product',
            'unit' => 'pcs',
            'sell_price' => 199000,
            'cost_price' => 150000,
            'weight_gram' => 200,
            'is_active' => '1',
            'gambar_url' => self::GAMBAR,
            'html' => $this->html(),
        ])->assertRedirect(route('superadmin.toko.produk'));

        $this->assertStringNotContainsString('fitraalfandi6', json_encode(Product::sole()->getAttributes()));
        $this->assertArrayNotHasKey('gambar_url', Product::sole()->getAttributes());
    }

    public function test_slug_kosong_dibuat_dari_nama_dan_dibuat_unik(): void
    {
        $admin = $this->superadmin();
        $data = [
            'name' => 'Jonas Knee Pad / Knee Busa Classic V2 - Black',
            'slug' => '',
            'category' => 'rank-product',
            'unit' => 'pcs',
            'sell_price' => 199800,
            'cost_price' => 175310,
            'weight_gram' => 200,
            'is_active' => '0',
        ];

        $this->actingAs($admin)->post('/superadmin/toko/produk', $data)->assertRedirect(route('superadmin.toko.produk'));
        $this->actingAs($admin)->post('/superadmin/toko/produk', $data)->assertRedirect(route('superadmin.toko.produk'));

        $this->assertSame(
            ['jonas-knee-pad-knee-busa-classic-v2-black', 'jonas-knee-pad-knee-busa-classic-v2-black-2'],
            Product::orderBy('id')->pluck('slug')->all(),
        );
    }

    public function test_impor_memilih_outlet_dari_kota_dikirim_dari_bila_tunggal(): void
    {
        $this->seed(SupplierOutletSeeder::class);

        $this->assertSame(92, Supplier::count());

        $metro = Supplier::where('name', 'RANK Sports Kota Metro, Lampung')->first();
        $this->assertSame('34111', $metro->origin_postal_code);
        $this->assertSame($metro->id, Supplier::cocokDariImpor('Toko Lain', 'KOTA METRO')->id);
        $this->assertSame(Supplier::where('name', 'RANK Sports Bekasi, Kota Bekasi')->first()->id, Supplier::cocokDariImpor(null, 'Kota Bekasi')->id);
    }

    public function test_impor_memilih_outlet_pertama_bila_kota_banyak_outlet_dan_null_bila_tak_dikenal(): void
    {
        $this->seed(SupplierOutletSeeder::class);

        $this->assertSame('RANK Sports Green Lake City, Jakarta Barat', Supplier::cocokDariImpor(null, 'KOTA ADM. JAKARTA BARAT')->name);
        $this->assertSame('RANK Sports Condet, Jakarta Selatan', Supplier::cocokDariImpor(null, 'KOTA JAKARTA TIMUR')->name);
        $this->assertNull(Supplier::cocokDariImpor(null, 'Kota Antah Berantah'));
        $this->assertNull(Supplier::cocokDariImpor(null, null));
    }

    public function test_seeder_outlet_aman_dijalankan_ulang_dan_tidak_menimpa_whatsapp(): void
    {
        $seeder = new SupplierOutletSeeder;
        $seeder->run();
        Supplier::where('name', 'RANK Sports Kota Metro, Lampung')->update(['whatsapp' => '08123456789']);
        $seeder->run();

        $this->assertSame(92, Supplier::count());
        $this->assertSame('shopee', Supplier::where('name', 'RANK Sports Tegal, Jawa Tengah')->value('platform'));
        $this->assertSame('6281279997779', Supplier::where('name', 'RANK Sports Tegal, Jawa Tengah')->value('whatsapp'));
        $this->assertSame('08123456789', Supplier::where('name', 'RANK Sports Kota Metro, Lampung')->value('whatsapp'));
    }
}
