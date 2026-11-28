<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\FotoProdukService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Banyak foto per produk toko: admin (/superadmin/toko/produk) dan halaman pelanggan.
 */
class FotoProdukBanyakTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->is_superadmin = true;
        $user->save();

        return $user;
    }

    /**
     * @param  array<string, mixed>  $tambahan
     * @return array<string, mixed>
     */
    private function data(array $tambahan = []): array
    {
        return array_merge([
            'name' => 'Jonas Knee Pad',
            'slug' => '',
            'category' => 'rank-product',
            'unit' => 'pcs',
            'sell_price' => 199800,
            'cost_price' => 175310,
            'weight_gram' => 200,
            'is_active' => '1',
        ], $tambahan);
    }

    private function jpg(string $nama): UploadedFile
    {
        return UploadedFile::fake()->image($nama);
    }

    public function test_tambah_produk_dengan_beberapa_foto_foto_pertama_jadi_utama(): void
    {
        Storage::fake('public');

        $this->actingAs($this->superadmin())->post('/superadmin/toko/produk', $this->data([
            'foto_baru' => [$this->jpg('a.jpg'), $this->jpg('b.jpg'), $this->jpg('c.jpg')],
        ]))->assertRedirect(route('superadmin.toko.produk'));

        $produk = Product::sole();
        $this->assertSame(3, $produk->images()->count());
        $this->assertSame([0, 1, 2], $produk->images->pluck('urutan')->all());
        $this->assertSame($produk->images->first()->path, $produk->image_path);
        $this->assertCount(3, Storage::disk('public')->allFiles('produk'));
    }

    public function test_ubah_urutan_hapus_dan_tambah_foto_saat_edit(): void
    {
        Storage::fake('public');
        $admin = $this->superadmin();
        $this->actingAs($admin)->post('/superadmin/toko/produk', $this->data(['foto_baru' => [$this->jpg('a.jpg'), $this->jpg('b.jpg'), $this->jpg('c.jpg')]]));
        $produk = Product::sole();
        [$a, $b, $c] = $produk->images->all();

        $this->actingAs($admin)->put("/superadmin/toko/produk/{$produk->id}", $this->data([
            'foto_diatur' => '1',
            'foto_ada' => [$c->id, $a->id],
            'foto_baru' => [$this->jpg('d.jpg')],
        ]))->assertRedirect(route('superadmin.toko.produk'));

        $produk->refresh();
        $this->assertSame([$c->path, $a->path], $produk->images->take(2)->pluck('path')->all());
        $this->assertSame(3, $produk->images->count());
        $this->assertSame($c->path, $produk->image_path);
        $this->assertDatabaseMissing('product_images', ['id' => $b->id]);
        $this->assertCount(3, Storage::disk('public')->allFiles('produk'));
    }

    /**
     * Lebar gambar tersimpan, untuk mengenali foto mana yang jadi utama (nama file di storage acak).
     */
    private function lebar(string $alamat): int
    {
        $path = Storage::disk('public')->path(str_replace(asset('storage').'/', '', $alamat));

        return getimagesize($path)[0];
    }

    public function test_tambah_produk_memilih_unggahan_ketiga_sebagai_utama(): void
    {
        Storage::fake('public');

        $this->actingAs($this->superadmin())->post('/superadmin/toko/produk', $this->data([
            'foto_baru' => [UploadedFile::fake()->image('a.jpg', 11, 11), UploadedFile::fake()->image('b.jpg', 12, 12), UploadedFile::fake()->image('c.jpg', 13, 13)],
            'foto_utama' => 'baru:2',
        ]))->assertRedirect(route('superadmin.toko.produk'));

        $produk = Product::sole();
        $this->assertSame([13, 11, 12], $produk->images->map(fn ($foto) => $this->lebar($foto->path))->all());
        $this->assertSame($produk->images->first()->path, $produk->image_path);
    }

    public function test_edit_memilih_foto_lama_atau_unggahan_baru_sebagai_utama(): void
    {
        Storage::fake('public');
        $admin = $this->superadmin();
        $this->actingAs($admin)->post('/superadmin/toko/produk', $this->data(['foto_baru' => [$this->jpg('a.jpg'), $this->jpg('b.jpg'), $this->jpg('c.jpg')]]));
        $produk = Product::sole();
        [$a, $b, $c] = $produk->images->all();

        $this->actingAs($admin)->put("/superadmin/toko/produk/{$produk->id}", $this->data([
            'foto_diatur' => '1',
            'foto_ada' => [$a->id, $b->id, $c->id],
            'foto_utama' => "ada:{$b->id}",
        ]))->assertRedirect();

        $produk->refresh();
        $this->assertSame([$b->id, $a->id, $c->id], $produk->images->pluck('id')->all());
        $this->assertSame($b->path, $produk->image_path);

        $this->actingAs($admin)->put("/superadmin/toko/produk/{$produk->id}", $this->data([
            'foto_diatur' => '1',
            'foto_ada' => [$b->id, $a->id, $c->id],
            'foto_baru' => [UploadedFile::fake()->image('d.jpg', 17, 17)],
            'foto_utama' => 'baru:0',
        ]))->assertRedirect();

        $produk->refresh();
        $this->assertSame(17, $this->lebar($produk->image_path));
        $this->assertSame([$b->id, $a->id, $c->id], $produk->images->skip(1)->pluck('id')->values()->all());
    }

    public function test_foto_impor_bisa_dipilih_sebagai_utama_di_atas_unggahan(): void
    {
        Storage::fake('public');
        $kecil = UploadedFile::fake()->image('x.png', 21, 21)->getContent();
        $besar = UploadedFile::fake()->image('y.png', 22, 22)->getContent();
        $satu = 'https://down-id.img.susercontent.com/file/satu';
        $dua = 'https://down-id.img.susercontent.com/file/dua';
        Http::fake([$satu => Http::response($kecil, 200, ['Content-Type' => 'image/png']), $dua => Http::response($besar, 200, ['Content-Type' => 'image/png'])]);

        $this->actingAs($this->superadmin())->post('/superadmin/toko/produk', $this->data([
            'gambar_urls' => [$satu, $dua],
            'foto_baru' => [UploadedFile::fake()->image('u.jpg', 23, 23)],
            'foto_utama' => 'impor:1',
        ]))->assertRedirect();

        $this->assertSame([22, 21, 23], Product::sole()->images->map(fn ($foto) => $this->lebar($foto->path))->all());
    }

    public function test_pilihan_foto_utama_tidak_valid_ditolak_dan_di_luar_jangkauan_diabaikan(): void
    {
        Storage::fake('public');
        $admin = $this->superadmin();

        $this->actingAs($admin)->post('/superadmin/toko/produk', $this->data(['foto_baru' => [$this->jpg('a.jpg')], 'foto_utama' => 'sembarang']))
            ->assertSessionHasErrors('foto_utama');

        $this->actingAs($admin)->post('/superadmin/toko/produk', $this->data([
            'foto_baru' => [UploadedFile::fake()->image('a.jpg', 31, 31), UploadedFile::fake()->image('b.jpg', 32, 32)],
            'foto_utama' => 'baru:9',
        ]))->assertRedirect(route('superadmin.toko.produk'));

        $this->assertSame([31, 32], Product::sole()->images->map(fn ($foto) => $this->lebar($foto->path))->all());
    }

    public function test_menghapus_semua_foto_dikenali_walau_daftar_kosong(): void
    {
        Storage::fake('public');
        $admin = $this->superadmin();
        $this->actingAs($admin)->post('/superadmin/toko/produk', $this->data(['foto_baru' => [$this->jpg('a.jpg'), $this->jpg('b.jpg')]]));
        $produk = Product::sole();

        $this->actingAs($admin)->put("/superadmin/toko/produk/{$produk->id}", $this->data(['foto_diatur' => '1']))->assertRedirect();

        $this->assertSame(0, $produk->images()->count());
        $this->assertNull($produk->fresh()->image_path);
        $this->assertCount(0, Storage::disk('public')->allFiles('produk'));
    }

    public function test_edit_tanpa_penanda_foto_diatur_tidak_menyentuh_foto_lama(): void
    {
        Storage::fake('public');
        $admin = $this->superadmin();
        $this->actingAs($admin)->post('/superadmin/toko/produk', $this->data(['foto_baru' => [$this->jpg('a.jpg'), $this->jpg('b.jpg')]]));
        $produk = Product::sole();

        $this->actingAs($admin)->put("/superadmin/toko/produk/{$produk->id}", $this->data(['name' => 'Nama Baru']))->assertRedirect();

        $this->assertSame(2, $produk->images()->count());
        $this->assertSame('Nama Baru', $produk->fresh()->name);
    }

    public function test_id_foto_milik_produk_lain_diabaikan_dan_tidak_dihapus(): void
    {
        Storage::fake('public');
        $lain = ProductImage::factory()->create(['path' => 'https://contoh.test/storage/produk/lain.jpg']);
        $produk = Product::factory()->create();
        $milik = ProductImage::factory()->create(['product_id' => $produk->id, 'path' => 'https://contoh.test/storage/produk/milik.jpg']);

        $this->actingAs($this->superadmin())->put("/superadmin/toko/produk/{$produk->id}", $this->data([
            'foto_diatur' => '1',
            'foto_ada' => [$lain->id, $milik->id],
        ]))->assertRedirect();

        $this->assertDatabaseHas('product_images', ['id' => $lain->id, 'product_id' => $lain->product_id]);
        $this->assertSame([$milik->id], $produk->images()->pluck('id')->all());
    }

    public function test_foto_lebih_dari_batas_ditolak(): void
    {
        Storage::fake('public');
        $foto = array_map(fn (int $i) => $this->jpg("f{$i}.jpg"), range(1, FotoProdukService::MAKS_FOTO + 1));

        $this->actingAs($this->superadmin())->post('/superadmin/toko/produk', $this->data(['foto_baru' => $foto]))
            ->assertSessionHasErrors();

        $this->assertSame(0, Product::count());
        $this->assertCount(0, Storage::disk('public')->allFiles('produk'));
    }

    public function test_file_bukan_gambar_ditolak(): void
    {
        Storage::fake('public');

        $this->actingAs($this->superadmin())->post('/superadmin/toko/produk', $this->data([
            'foto_baru' => [UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf')],
        ]))->assertSessionHasErrors('foto_baru.0');

        $this->assertSame(0, Product::count());
    }

    public function test_beberapa_foto_impor_diunduh_berurutan(): void
    {
        Storage::fake('public');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        $satu = 'https://down-id.img.susercontent.com/file/satu';
        $dua = 'https://down-id.img.susercontent.com/file/dua';
        Http::fake([$satu => Http::response($png, 200), $dua => Http::response($png, 200)]);

        $this->actingAs($this->superadmin())->post('/superadmin/toko/produk', $this->data(['gambar_urls' => [$satu, $dua]]))->assertRedirect();

        $this->assertSame(2, Product::sole()->images()->count());
        $this->assertCount(2, Storage::disk('public')->allFiles('produk'));
    }

    public function test_gagal_mengunduh_salah_satu_foto_impor_membatalkan_semuanya(): void
    {
        Storage::fake('public');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        $satu = 'https://down-id.img.susercontent.com/file/satu';
        $dua = 'https://down-id.img.susercontent.com/file/dua';
        Http::fake([$satu => Http::response($png, 200), $dua => Http::response('', 404)]);

        $this->actingAs($this->superadmin())->post('/superadmin/toko/produk', $this->data(['gambar_urls' => [$satu, $dua]]))->assertSessionHasErrors('foto');

        $this->assertSame(0, Product::count());
        $this->assertCount(0, Storage::disk('public')->allFiles('produk'));
    }

    public function test_alamat_foto_impor_di_luar_cdn_shopee_ditolak(): void
    {
        Http::fake();

        $this->actingAs($this->superadmin())->post('/superadmin/toko/produk', $this->data([
            'gambar_urls' => ['https://down-id.img.susercontent.com/file/ok', 'http://169.254.169.254/x'],
        ]))->assertSessionHasErrors('gambar_urls.1');

        Http::assertNothingSent();
        $this->assertSame(0, Product::count());
    }

    public function test_hapus_produk_menghapus_semua_file_dan_barisnya(): void
    {
        Storage::fake('public');
        $admin = $this->superadmin();
        $this->actingAs($admin)->post('/superadmin/toko/produk', $this->data(['foto_baru' => [$this->jpg('a.jpg'), $this->jpg('b.jpg')]]));
        $produk = Product::sole();

        $this->actingAs($admin)->delete("/superadmin/toko/produk/{$produk->id}")->assertRedirect();

        $this->assertSame(0, ProductImage::count());
        $this->assertCount(0, Storage::disk('public')->allFiles('produk'));
    }

    public function test_form_edit_mengirim_daftar_foto_dan_batasnya(): void
    {
        $produk = Product::factory()->create();
        $foto = ProductImage::factory()->create(['product_id' => $produk->id, 'path' => 'https://contoh.test/storage/produk/a.jpg']);

        $this->actingAs($this->superadmin())->get("/superadmin/toko/produk/{$produk->id}/edit")
            ->assertInertia(fn (Assert $page) => $page
                ->where('produk.fotos', [['id' => $foto->id, 'url' => 'https://contoh.test/storage/produk/a.jpg']])
                ->where('maksFoto', FotoProdukService::MAKS_FOTO)
            );
    }

    public function test_halaman_pelanggan_menerima_semua_foto_tanpa_data_privat(): void
    {
        $produk = Product::factory()->create(['slug' => 'knee-pad', 'image_path' => 'https://contoh.test/a.jpg']);
        ProductImage::factory()->create(['product_id' => $produk->id, 'path' => 'https://contoh.test/a.jpg', 'urutan' => 0]);
        ProductImage::factory()->create(['product_id' => $produk->id, 'path' => 'https://contoh.test/b.jpg', 'urutan' => 1]);

        $this->get('http://greendeahan.com/toko/knee-pad')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('produk.images', ['https://contoh.test/a.jpg', 'https://contoh.test/b.jpg'])
                ->where('produk.imageUrl', 'https://contoh.test/a.jpg')
                ->missing('produk.cost_price')
                ->missing('produk.supplier_product_url')
            );
    }

    public function test_produk_lama_tanpa_baris_foto_tetap_menampilkan_image_path(): void
    {
        $produk = Product::factory()->create(['image_path' => 'https://contoh.test/lama.jpg']);
        $kosong = Product::factory()->create(['image_path' => null]);

        $this->assertSame(['https://contoh.test/lama.jpg'], $produk->dataPublik()['images']);
        $this->assertSame([], $kosong->dataPublik()['images']);
    }
}
