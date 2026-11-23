<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Exceptions\MayarException;
use App\Mail\PesananTokoDibatalkan;
use App\Mail\PesananTokoDikirim;
use App\Mail\PesanPesananToko;
use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\MayarService;
use App\Services\PesanPesananTokoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Admin toko di /superadmin/toko dan MayarService.
 */
class TokoAdminTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->is_superadmin = true;
        $user->save();

        return $user;
    }

    private function pesananDibayar(array $override = []): Order
    {
        $order = Order::factory()->create(array_merge(['status' => OrderStatus::Paid, 'paid_at' => now(), 'subtotal' => 190000, 'shipping_cost' => 15000, 'total' => 205000], $override));
        $order->items()->create(['product_id' => null, 'product_name' => 'Bola Padel', 'unit_price' => 95000, 'unit_cost' => 75000, 'qty' => 2]);

        return $order;
    }

    public function test_hanya_superadmin_yang_bisa_membuka_admin_toko(): void
    {
        $this->get('/superadmin/toko/pesanan')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/superadmin/toko/pesanan')->assertForbidden();
        $this->actingAs(User::factory()->create())->get('/superadmin/toko/produk')->assertForbidden();
        $this->actingAs(User::factory()->create())->post('/superadmin/toko/supplier', ['name' => 'X', 'platform' => 'direct'])->assertForbidden();
    }

    public function test_tambah_produk_dengan_foto_dan_data_privat_hanya_di_admin(): void
    {
        Storage::fake('public');
        $supplier = Supplier::factory()->create();

        $this->actingAs($this->superadmin())->post('/superadmin/toko/produk', [
            'name' => 'Bola Padel Isi 3',
            'slug' => '',
            'category' => 'olahraga',
            'unit' => 'tabung',
            'badge' => 'Baru',
            'description' => 'Tabung isi 3.',
            'sell_price' => 95000,
            'compare_price' => 110000,
            'cost_price' => 75000,
            'supplier_id' => $supplier->id,
            'supplier_product_url' => 'https://shopee.co.id/produk-supplier',
            'weight_gram' => 250,
            'is_active' => '1',
            'foto' => UploadedFile::fake()->image('bola.jpg'),
        ])->assertRedirect(route('superadmin.toko.produk'));

        $produk = Product::sole();
        $this->assertSame('bola-padel-isi-3', $produk->slug);
        $this->assertSame(['tabung', 'Baru', 110000], [$produk->unit, $produk->badge, $produk->compare_price]);
        $this->assertNotNull($produk->image_path);

        $this->actingAs($this->superadmin())->get('/superadmin/toko/produk')
            ->assertInertia(fn (Assert $page) => $page
                ->component('superadmin/Toko/Produk/Index')
                ->where('produk.0.hargaModal', 75000)
                ->where('produk.0.margin', 20000)
                ->where('produk.0.linkSupplier', 'https://shopee.co.id/produk-supplier')
            );
    }

    public function test_spesifikasi_produk_disimpan_baris_kosong_dibuang_dan_bisa_dikosongkan(): void
    {
        $admin = $this->superadmin();
        $dasar = ['name' => 'Kaos Kaki', 'slug' => 'kaos-kaki', 'category' => 'olahraga', 'unit' => 'pcs', 'sell_price' => 45000, 'cost_price' => 30000, 'weight_gram' => 120, 'is_active' => '1'];

        $this->actingAs($admin)->post('/superadmin/toko/produk', [...$dasar, 'specs' => [
            ['nama' => ' Merek ', 'nilai' => 'Ortuseight'],
            ['nama' => '', 'nilai' => ''],
            ['nama' => 'Bahan', 'nilai' => 'Nilon'],
        ]])->assertSessionHasNoErrors();

        $produk = Product::sole();
        $this->assertSame([['nama' => 'Merek', 'nilai' => 'Ortuseight'], ['nama' => 'Bahan', 'nilai' => 'Nilon']], $produk->specs);

        $this->actingAs($admin)->get("/superadmin/toko/produk/{$produk->id}/edit")->assertInertia(fn (Assert $page) => $page->has('produk.specs', 2)->where('produk.specs.1.nama', 'Bahan'));

        $this->get('http://greendeahan.com/toko/kaos-kaki')->assertInertia(fn (Assert $page) => $page->where('produk.specs.0', ['nama' => 'Merek', 'nilai' => 'Ortuseight']));

        $this->actingAs($admin)->put("/superadmin/toko/produk/{$produk->id}", $dasar)->assertSessionHasNoErrors();
        $this->assertSame([], $produk->fresh()->specs);
    }

    public function test_spesifikasi_wajib_lengkap_dan_dibatasi(): void
    {
        $admin = $this->superadmin();
        $dasar = ['name' => 'Kaos Kaki', 'slug' => 'kaos-kaki', 'category' => 'olahraga', 'unit' => 'pcs', 'sell_price' => 45000, 'cost_price' => 30000, 'weight_gram' => 120, 'is_active' => '1'];

        $this->actingAs($admin)->post('/superadmin/toko/produk', [...$dasar, 'specs' => [['nama' => 'Merek', 'nilai' => '']]])->assertSessionHasErrors('specs.0.nilai');
        $this->actingAs($admin)->post('/superadmin/toko/produk', [...$dasar, 'specs' => [['nama' => '', 'nilai' => 'Nilon']]])->assertSessionHasErrors('specs.0.nama');
        $this->actingAs($admin)->post('/superadmin/toko/produk', [...$dasar, 'specs' => [['nama' => str_repeat('a', 61), 'nilai' => 'x']]])->assertSessionHasErrors('specs.0.nama');
        $this->actingAs($admin)->post('/superadmin/toko/produk', [...$dasar, 'specs' => [['nama' => 'a', 'nilai' => str_repeat('x', 151)]]])->assertSessionHasErrors('specs.0.nilai');
        $this->actingAs($admin)->post('/superadmin/toko/produk', [...$dasar, 'specs' => array_map(fn (int $nomor) => ['nama' => "Atribut {$nomor}", 'nilai' => 'x'], range(1, 21))])->assertSessionHasErrors('specs');

        $this->assertSame(0, Product::count());
    }

    public function test_kode_pos_asal_produk_opsional_harus_5_angka(): void
    {
        $admin = $this->superadmin();
        $dasar = ['name' => 'Deker', 'slug' => 'deker', 'category' => 'olahraga', 'unit' => 'pcs', 'sell_price' => 50000, 'cost_price' => 30000, 'weight_gram' => 200, 'is_active' => '1'];

        $this->actingAs($admin)->post('/superadmin/toko/produk', [...$dasar, 'origin_postal_code' => '6011'])->assertSessionHasErrors('origin_postal_code');
        $this->actingAs($admin)->post('/superadmin/toko/produk', [...$dasar, 'origin_postal_code' => 'abcde'])->assertSessionHasErrors('origin_postal_code');
        $this->assertSame(0, Product::count());

        $this->actingAs($admin)->post('/superadmin/toko/produk', [...$dasar, 'origin_postal_code' => '60265'])->assertSessionHasNoErrors();
        $produk = Product::sole();
        $this->assertSame('60265', $produk->origin_postal_code);

        $this->actingAs($admin)->get("/superadmin/toko/produk/{$produk->id}/edit")->assertInertia(fn (Assert $page) => $page->where('produk.origin_postal_code', '60265'));

        $this->actingAs($admin)->put("/superadmin/toko/produk/{$produk->id}", [...$dasar, 'origin_postal_code' => ''])->assertSessionHasNoErrors();
        $this->assertNull($produk->fresh()->origin_postal_code);
    }

    public function test_admin_mencatat_cabang_pengirim_saat_pesanan_dikirim(): void
    {
        Mail::fake();
        $order = $this->pesananDibayar();
        $admin = $this->superadmin();
        $url = "/superadmin/toko/pesanan/{$order->id}/status";

        $this->actingAs($admin)->post($url, ['status' => 'ordered_to_supplier', 'supplier_order_ref' => 'SHP-001'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post($url, ['status' => 'shipped', 'pengiriman' => [['courier' => 'JNE', 'tracking_number' => 'JNE123', 'shipped_from' => 'RANK Sports Bekasi']]])->assertSessionHasNoErrors();

        $this->assertSame('RANK Sports Bekasi', $order->shipments()->sole()->shipped_from);
        $this->actingAs($admin)->get("/superadmin/toko/pesanan/{$order->id}")->assertInertia(fn (Assert $page) => $page->where('pesanan.pengiriman.0.dikirimDari', 'RANK Sports Bekasi'));
    }

    public function test_pesanan_dikirim_dalam_beberapa_paket_dengan_resi_masing_masing(): void
    {
        Mail::fake();
        $order = $this->pesananDibayar();
        $admin = $this->superadmin();
        $url = "/superadmin/toko/pesanan/{$order->id}/status";
        $this->actingAs($admin)->post($url, ['status' => 'ordered_to_supplier', 'supplier_order_ref' => 'SHP-001']);

        $this->actingAs($admin)->post($url, ['status' => 'shipped', 'pengiriman' => [
            ['courier' => 'JNE', 'tracking_number' => 'JNE123', 'shipped_from' => 'RANK Sports Bekasi'],
            ['courier' => 'J&T', 'tracking_number' => 'JT456', 'shipped_from' => ''],
        ]])->assertSessionHasNoErrors();

        $this->assertSame(['JNE123', 'JT456'], $order->shipments()->pluck('tracking_number')->all());
        Mail::assertQueued(PesananTokoDikirim::class, 1);
        Mail::assertQueued(PesananTokoDikirim::class, fn (PesananTokoDikirim $mail) => ! $mail->tambahan && count($mail->order->daftarPengiriman()) === 2);
        $this->actingAs($admin)->get("/superadmin/toko/pesanan/{$order->id}")->assertInertia(fn (Assert $page) => $page->has('pesanan.pengiriman', 2)->where('pesanan.pengiriman.1.kurir', 'J&T'));
    }

    public function test_setiap_paket_wajib_punya_kurir_dan_resi_dan_paket_gagal_tidak_mengubah_status(): void
    {
        $order = $this->pesananDibayar();
        $admin = $this->superadmin();
        $url = "/superadmin/toko/pesanan/{$order->id}/status";
        $this->actingAs($admin)->post($url, ['status' => 'ordered_to_supplier', 'supplier_order_ref' => 'SHP-001']);

        $this->actingAs($admin)->post($url, ['status' => 'shipped', 'pengiriman' => [
            ['courier' => 'JNE', 'tracking_number' => 'JNE123'],
            ['courier' => '', 'tracking_number' => 'JT456'],
        ]])->assertSessionHasErrors('pengiriman.1.courier');

        $this->assertSame(OrderStatus::OrderedToSupplier, $order->fresh()->status);
        $this->assertSame(0, $order->shipments()->count());
    }

    public function test_paket_tambahan_dicatat_setelah_dikirim_dan_pelanggan_diberi_tahu(): void
    {
        Mail::fake();
        $order = $this->pesananDibayar();
        $admin = $this->superadmin();
        $url = "/superadmin/toko/pesanan/{$order->id}";
        $this->actingAs($admin)->post("{$url}/status", ['status' => 'ordered_to_supplier', 'supplier_order_ref' => 'SHP-001']);

        $this->actingAs($admin)->post("{$url}/resi", ['courier' => 'JNE', 'tracking_number' => 'X'])->assertSessionHas('error');
        $this->assertSame(0, $order->shipments()->count());

        $this->actingAs($admin)->post("{$url}/status", ['status' => 'shipped', 'pengiriman' => [['courier' => 'JNE', 'tracking_number' => 'JNE123']]]);
        $this->actingAs($admin)->post("{$url}/resi", ['courier' => 'SiCepat', 'tracking_number' => 'SC789', 'shipped_from' => 'RANK Sports Medan'])->assertSessionHas('success');
        $this->actingAs($admin)->post("{$url}/resi", ['courier' => '', 'tracking_number' => ''])->assertSessionHasErrors(['courier', 'tracking_number']);

        $this->assertSame(['JNE123', 'SC789'], $order->shipments()->pluck('tracking_number')->all());
        Mail::assertQueued(PesananTokoDikirim::class, 2);
        Mail::assertQueued(PesananTokoDikirim::class, fn (PesananTokoDikirim $mail) => $mail->tambahan);
    }

    public function test_hapus_resi_menjaga_minimal_satu_dan_hanya_milik_pesanan_itu(): void
    {
        Mail::fake();
        $order = Order::factory()->create(['status' => OrderStatus::Shipped]);
        $pertama = $order->shipments()->create(['courier' => 'JNE', 'tracking_number' => 'JNE123']);
        $kedua = $order->shipments()->create(['courier' => 'J&T', 'tracking_number' => 'JT456']);
        $lain = Order::factory()->create(['status' => OrderStatus::Shipped])->shipments()->create(['courier' => 'JNE', 'tracking_number' => 'LAIN1']);
        $admin = $this->superadmin();

        $this->actingAs($admin)->delete("/superadmin/toko/pesanan/{$order->id}/resi/{$lain->id}")->assertNotFound();
        $this->actingAs($admin)->delete("/superadmin/toko/pesanan/{$order->id}/resi/{$kedua->id}")->assertSessionHas('success');
        $this->actingAs($admin)->delete("/superadmin/toko/pesanan/{$order->id}/resi/{$pertama->id}")->assertSessionHas('error');

        $this->assertSame(['JNE123'], $order->shipments()->pluck('tracking_number')->all());
        $this->assertSame(1, $lain->order->shipments()->count());
    }

    public function test_email_memuat_semua_paket_dan_menjelaskan_paket_bisa_tiba_berbeda(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Shipped]);
        $order->shipments()->create(['courier' => 'JNE', 'tracking_number' => 'JNE123', 'shipped_from' => 'RANK Sports Bekasi']);
        $order->shipments()->create(['courier' => 'J&T', 'tracking_number' => 'JT456']);

        $mail = new PesananTokoDikirim($order->fresh());
        $mail->assertSeeInHtml('JNE123');
        $mail->assertSeeInHtml('JT456');
        $mail->assertSeeInHtml('RANK Sports Bekasi');
        $mail->assertSeeInHtml('2 paket');
        $this->assertSame("Pesanan {$order->code} sudah dikirim", $mail->envelope()->subject);
        $this->assertSame("Ada paket baru untuk pesanan {$order->code}", (new PesananTokoDikirim($order->fresh(), tambahan: true))->envelope()->subject);
    }

    public function test_pembatalan_pesanan_dibayar_wajib_beralasan_memberi_tahu_pelanggan_dan_menandai_perlu_refund(): void
    {
        Mail::fake();
        $order = $this->pesananDibayar();
        $admin = $this->superadmin();
        $url = "/superadmin/toko/pesanan/{$order->id}/status";

        $this->actingAs($admin)->post($url, ['status' => 'cancelled'])->assertSessionHasErrors('cancel_reason');
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        Mail::assertNothingQueued();

        $this->actingAs($admin)->post($url, ['status' => 'cancelled', 'cancel_reason' => 'Stok supplier habis'])->assertSessionHas('success');

        $order = $order->fresh();
        $this->assertSame(OrderStatus::Cancelled, $order->status);
        $this->assertSame('Stok supplier habis', $order->cancel_reason);
        $this->assertNotNull($order->cancelled_at);
        $this->assertSame('diproses', $order->statusRefund());
        Mail::assertQueued(PesananTokoDibatalkan::class, fn (PesananTokoDibatalkan $mail) => $mail->hasTo($order->email) && $mail->order->cancel_reason === 'Stok supplier habis');
    }

    public function test_email_pembatalan_memuat_alasan_dan_nominal_refund(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Cancelled, 'paid_at' => now(), 'total' => 115000, 'cancel_reason' => 'Stok supplier habis']);

        $mail = new PesananTokoDibatalkan($order);

        $mail->assertSeeInHtml('Stok supplier habis');
        $mail->assertSeeInHtml('Rp115.000');
        $mail->assertSeeInHtml($order->code);
    }

    public function test_pembatalan_pesanan_belum_dibayar_tidak_butuh_refund_dan_tanpa_email(): void
    {
        Mail::fake();
        $order = Order::factory()->create(['status' => OrderStatus::PendingPayment]);

        $this->actingAs($this->superadmin())->post("/superadmin/toko/pesanan/{$order->id}/status", ['status' => 'cancelled', 'cancel_reason' => 'Pelanggan tidak jadi'])->assertSessionHasNoErrors();

        $this->assertNull($order->fresh()->statusRefund());
        Mail::assertNothingQueued();
    }

    public function test_refund_ditandai_selesai_sekali_dan_hanya_untuk_pesanan_menunggu_refund(): void
    {
        $perlu = Order::factory()->create(['status' => OrderStatus::Cancelled, 'paid_at' => now(), 'cancelled_at' => now(), 'cancel_reason' => 'Stok habis']);
        $belumBayar = Order::factory()->create(['status' => OrderStatus::Cancelled, 'cancelled_at' => now(), 'cancel_reason' => 'Tidak jadi']);
        $admin = $this->superadmin();

        $this->actingAs($admin)->post("/superadmin/toko/pesanan/{$belumBayar->id}/refund")->assertSessionHas('error');
        $this->assertNull($belumBayar->fresh()->refunded_at);

        $this->actingAs($admin)->post("/superadmin/toko/pesanan/{$perlu->id}/refund")->assertSessionHas('success');
        $this->assertSame('selesai', $perlu->fresh()->statusRefund());

        $this->actingAs($admin)->post("/superadmin/toko/pesanan/{$perlu->id}/refund")->assertSessionHas('error');
        $this->post("/superadmin/toko/pesanan/{$belumBayar->id}/refund")->assertRedirect();
    }

    public function test_daftar_pesanan_punya_filter_perlu_refund_dengan_jumlahnya(): void
    {
        $perlu = Order::factory()->create(['status' => OrderStatus::Cancelled, 'paid_at' => now(), 'cancel_reason' => 'Stok habis']);
        Order::factory()->create(['status' => OrderStatus::Cancelled, 'paid_at' => now(), 'refunded_at' => now(), 'cancel_reason' => 'Stok habis']);
        Order::factory()->create(['status' => OrderStatus::Cancelled, 'cancel_reason' => 'Tidak jadi']);

        $this->actingAs($this->superadmin())->get('/superadmin/toko/pesanan?status=perlu_refund')
            ->assertInertia(fn (Assert $page) => $page
                ->where('filterStatus', 'perlu_refund')
                ->has('pesanan.data', 1)
                ->where('pesanan.data.0.kode', $perlu->code)
                ->where('pesanan.data.0.perluRefund', true)
                ->where('pilihanStatus', fn ($pilihan) => collect($pilihan)->firstWhere('nilai', 'perlu_refund')['jumlah'] === 1)
            );
    }

    public function test_halaman_pelanggan_menampilkan_alasan_dan_status_refund(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Cancelled, 'paid_at' => now(), 'cancel_reason' => 'Stok supplier habis']);

        $this->get("http://greendeahan.com/pesanan/{$order->code}")->assertInertia(fn (Assert $page) => $page
            ->where('pesanan.alasanBatal', 'Stok supplier habis')
            ->where('pesanan.statusRefund', 'diproses')
        );

        $order->update(['refunded_at' => now()]);

        $this->get("http://greendeahan.com/pesanan/{$order->code}")->assertInertia(fn (Assert $page) => $page->where('pesanan.statusRefund', 'selesai'));
    }

    public function test_admin_diberi_tanda_untuk_pesanan_dengan_ongkir_tetap(): void
    {
        $darurat = $this->pesananDibayar(['courier_code' => 'darurat', 'courier_service_code' => 'tetap', 'courier_service_name' => 'Ongkir tetap (kurir dipilih toko)']);
        $biasa = $this->pesananDibayar(['courier_code' => 'jne', 'courier_service_name' => 'JNE Reguler']);
        $admin = $this->superadmin();

        $this->actingAs($admin)->get("/superadmin/toko/pesanan/{$darurat->id}")->assertInertia(fn (Assert $page) => $page->where('pesanan.ongkirDarurat', true)->where('pesanan.layanan', 'Ongkir tetap (kurir dipilih toko)'));
        $this->actingAs($admin)->get("/superadmin/toko/pesanan/{$biasa->id}")->assertInertia(fn (Assert $page) => $page->where('pesanan.ongkirDarurat', false));
    }

    public function test_template_pesan_terisi_dari_data_pesanan(): void
    {
        $order = $this->pesananDibayar(['customer_name' => 'Budi Santoso', 'phone' => '081234567890']);
        $order->shipments()->create(['courier' => 'JNE', 'tracking_number' => 'JNE123', 'shipped_from' => 'RANK Sports Bekasi']);
        $order->shipments()->create(['courier' => 'J&T', 'tracking_number' => 'JT456']);

        $this->actingAs($this->superadmin())->get("/superadmin/toko/pesanan/{$order->id}")->assertInertia(fn (Assert $page) => $page
            ->where('pesanan.waNomor', '6281234567890')
            ->has('pesanan.templatePesan', 6)
            ->where('pesanan.templatePesan.2.kunci', 'dikirim')
            ->where('pesanan.templatePesan.2.isi', fn (string $isi) => str_contains($isi, 'Halo Budi Santoso,')
                && str_contains($isi, 'Paket 1: JNE JNE123 (dari RANK Sports Bekasi)')
                && str_contains($isi, 'Paket 2: J&T JT456')
                && str_contains($isi, '2 paket')
                && str_contains($isi, route('toko.pesanan', $order->code)))
            ->where('pesanan.templatePesan.0.subjek', "Pesanan {$order->code} sudah kami terima")
        );
    }

    public function test_template_dikirim_tanpa_resi_mengingatkan_admin_dan_dibatalkan_memuat_alasan(): void
    {
        $order = $this->pesananDibayar(['cancel_reason' => 'Stok supplier habis', 'total' => 205000]);

        $this->actingAs($this->superadmin())->get("/superadmin/toko/pesanan/{$order->id}")->assertInertia(fn (Assert $page) => $page
            ->where('pesanan.templatePesan.2.isi', fn (string $isi) => str_contains($isi, 'Nomor resi belum diisi'))
            ->where('pesanan.templatePesan.4.isi', fn (string $isi) => str_contains($isi, 'Stok supplier habis') && str_contains($isi, 'Rp205.000'))
        );
    }

    #[DataProvider('nomorTelepon')]
    public function test_nomor_whatsapp_diubah_ke_format_internasional(string $telepon, ?string $diharapkan): void
    {
        $this->assertSame($diharapkan, PesanPesananTokoService::nomorWhatsApp($telepon));
    }

    /**
     * @return array<string, array{0: string, 1: ?string}>
     */
    public static function nomorTelepon(): array
    {
        return [
            'nol di depan' => ['081234567890', '6281234567890'],
            'sudah 62' => ['6281234567890', '6281234567890'],
            'tanda plus dan spasi' => ['+62 812-3456-7890', '6281234567890'],
            'terlalu pendek' => ['0812', null],
            'bukan nomor' => ['abc', null],
        ];
    }

    public function test_kirim_email_manual_mengirim_ke_pelanggan_dan_dicatat_di_riwayat(): void
    {
        Mail::fake();
        $order = $this->pesananDibayar(['email' => 'budi@contoh.com']);
        $admin = $this->superadmin();

        $this->actingAs($admin)->post("/superadmin/toko/pesanan/{$order->id}/pesan", [
            'channel' => 'email',
            'template' => 'diproses',
            'subject' => 'Pesanan sedang diproses',
            'body' => "Halo Budi,\n\nPesananmu sedang kami proses.",
        ])->assertSessionHas('success');

        Mail::assertQueued(PesanPesananToko::class, fn (PesanPesananToko $mail) => $mail->hasTo('budi@contoh.com') && $mail->subjek === 'Pesanan sedang diproses' && str_contains($mail->isi, 'sedang kami proses'));
        $catatan = $order->messages()->sole();
        $this->assertSame(['email', 'diproses', 'Pesanan sedang diproses', $admin->id], [$catatan->channel, $catatan->template, $catatan->subject, $catatan->user_id]);

        $this->actingAs($admin)->get("/superadmin/toko/pesanan/{$order->id}")->assertInertia(fn (Assert $page) => $page
            ->has('pesanan.riwayatPesan', 1)
            ->where('pesanan.riwayatPesan.0.saluran', 'email')
            ->where('pesanan.riwayatPesan.0.template', 'Sedang diproses')
        );
    }

    public function test_pesan_whatsapp_hanya_dicatat_tanpa_mengirim_email(): void
    {
        Mail::fake();
        $order = $this->pesananDibayar();

        $this->actingAs($this->superadmin())->post("/superadmin/toko/pesanan/{$order->id}/pesan", ['channel' => 'whatsapp', 'template' => 'selesai', 'body' => 'Halo, pesananmu selesai.'])->assertSessionHas('success');

        Mail::assertNothingQueued();
        $this->assertSame(['whatsapp', null], [$order->messages()->sole()->channel, $order->messages()->sole()->subject]);
    }

    public function test_pesan_manual_divalidasi_dan_hanya_untuk_superadmin(): void
    {
        Mail::fake();
        $order = $this->pesananDibayar();
        $url = "/superadmin/toko/pesanan/{$order->id}/pesan";
        $admin = $this->superadmin();

        $this->actingAs($admin)->post($url, ['channel' => 'email', 'template' => 'diproses', 'body' => 'Isi'])->assertSessionHasErrors('subject');
        $this->actingAs($admin)->post($url, ['channel' => 'sms', 'template' => 'diproses', 'body' => 'Isi'])->assertSessionHasErrors('channel');
        $this->actingAs($admin)->post($url, ['channel' => 'whatsapp', 'template' => 'tidak-ada', 'body' => 'Isi'])->assertSessionHasErrors('template');
        $this->actingAs($admin)->post($url, ['channel' => 'whatsapp', 'template' => 'diproses', 'body' => ''])->assertSessionHasErrors('body');
        $this->actingAs($admin)->post($url, ['channel' => 'whatsapp', 'template' => 'diproses', 'body' => str_repeat('a', 2001)])->assertSessionHasErrors('body');
        $this->actingAs(User::factory()->create())->post($url, ['channel' => 'whatsapp', 'template' => 'diproses', 'body' => 'Isi'])->assertForbidden();

        Mail::assertNothingQueued();
        $this->assertSame(0, $order->messages()->count());
    }

    public function test_hanya_superadmin_yang_bisa_menambah_atau_menghapus_resi(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::Shipped]);
        $paket = $order->shipments()->create(['courier' => 'JNE', 'tracking_number' => 'JNE123']);

        $this->post("/superadmin/toko/pesanan/{$order->id}/resi", ['courier' => 'JNE', 'tracking_number' => 'Z'])->assertRedirect();
        $this->delete("/superadmin/toko/pesanan/{$order->id}/resi/{$paket->id}")->assertRedirect();

        $this->assertSame(1, $order->shipments()->count());
    }

    public function test_ubah_dan_hapus_produk_tidak_merusak_pesanan_lama(): void
    {
        $produk = Product::factory()->create(['name' => 'Lama', 'slug' => 'lama']);
        $order = Order::factory()->create();
        $order->items()->create(['product_id' => $produk->id, 'product_name' => 'Lama', 'unit_price' => 100000, 'unit_cost' => 70000, 'qty' => 1]);
        $admin = $this->superadmin();

        $this->actingAs($admin)->put("/superadmin/toko/produk/{$produk->id}", [
            'name' => 'Baru', 'slug' => 'lama', 'category' => 'perlengkapan', 'unit' => 'set', 'sell_price' => 120000, 'cost_price' => 80000, 'weight_gram' => 0, 'is_active' => '0',
        ])->assertRedirect(route('superadmin.toko.produk'));
        $this->assertSame(['Baru', 120000, false], [$produk->fresh()->name, $produk->fresh()->sell_price, $produk->fresh()->is_active]);

        $this->actingAs($admin)->delete("/superadmin/toko/produk/{$produk->id}")->assertRedirect();
        $this->assertModelMissing($produk);
        $this->assertSame(['Lama', 100000, null], [$order->items()->first()->product_name, $order->items()->first()->unit_price, $order->items()->first()->product_id]);
    }

    public function test_validasi_satuan_dan_harga_coret(): void
    {
        $this->actingAs($this->superadmin())->post('/superadmin/toko/produk', [
            'name' => 'Rumput', 'category' => 'rumput', 'unit' => 'karung-ajaib', 'sell_price' => 185000, 'compare_price' => 100000, 'cost_price' => 0, 'weight_gram' => 0, 'is_active' => '1',
        ])->assertSessionHasErrors(['unit', 'compare_price']);

        $this->assertSame(0, Product::count());
    }

    public function test_crud_supplier(): void
    {
        $admin = $this->superadmin();

        $this->actingAs($admin)->post('/superadmin/toko/supplier', ['name' => 'Toko Bola', 'platform' => 'shopee', 'shop_url' => 'https://shopee.co.id/tokobola'])->assertSessionHasNoErrors();
        $supplier = Supplier::sole();
        $this->actingAs($admin)->put("/superadmin/toko/supplier/{$supplier->id}", ['name' => 'Toko Bola Baru', 'platform' => 'tokopedia'])->assertSessionHasNoErrors();
        $this->assertSame('tokopedia', $supplier->fresh()->platform);
        $this->actingAs($admin)->post('/superadmin/toko/supplier', ['name' => 'X', 'platform' => 'lazada'])->assertSessionHasErrors('platform');
        $this->actingAs($admin)->delete("/superadmin/toko/supplier/{$supplier->id}");
        $this->assertModelMissing($supplier);
    }

    public function test_daftar_pesanan_default_menampilkan_yang_perlu_dibeli(): void
    {
        $dibayar = $this->pesananDibayar();
        Order::factory()->create(['status' => OrderStatus::PendingPayment]);
        Order::factory()->create(['status' => OrderStatus::Completed]);

        $this->actingAs($this->superadmin())->get('/superadmin/toko/pesanan')
            ->assertInertia(fn (Assert $page) => $page
                ->component('superadmin/Toko/Pesanan/Index')
                ->where('filterStatus', 'paid')
                ->where('pesanan.total', 1)
                ->where('pesanan.data.0.kode', $dibayar->code)
                ->where('pesanan.data.0.untung', 205000 - 15000 - 150000)
            );

        $this->actingAs($this->superadmin())->get('/superadmin/toko/pesanan?status=semua')
            ->assertInertia(fn (Assert $page) => $page->where('filterStatus', 'semua')->where('pesanan.total', 3));
    }

    public function test_alur_status_pesanan_sampai_selesai_dan_email_resi(): void
    {
        Mail::fake();
        $order = $this->pesananDibayar();
        $admin = $this->superadmin();
        $url = "/superadmin/toko/pesanan/{$order->id}/status";

        $this->actingAs($admin)->get("/superadmin/toko/pesanan/{$order->id}")->assertInertia(fn (Assert $page) => $page
            ->component('superadmin/Toko/Pesanan/Show')
            ->where('pesanan.untung', 40000)
            ->where('pesanan.langkahBerikutnya.0.nilai', 'ordered_to_supplier')
        );

        $this->actingAs($admin)->post($url, ['status' => 'completed'])->assertSessionHasErrors('status');
        $this->actingAs($admin)->post($url, ['status' => 'ordered_to_supplier'])->assertSessionHasErrors('supplier_order_ref');
        $this->actingAs($admin)->post($url, ['status' => 'ordered_to_supplier', 'supplier_order_ref' => 'SHP-001'])->assertSessionHasNoErrors();
        $this->assertSame(OrderStatus::OrderedToSupplier, $order->fresh()->status);

        $this->actingAs($admin)->post($url, ['status' => 'shipped'])->assertSessionHasErrors('pengiriman');
        $this->actingAs($admin)->post($url, ['status' => 'shipped', 'pengiriman' => [['courier' => 'JNE']]])->assertSessionHasErrors('pengiriman.0.tracking_number');
        Mail::assertNothingQueued();
        $this->actingAs($admin)->post($url, ['status' => 'shipped', 'pengiriman' => [['courier' => 'JNE', 'tracking_number' => 'JNE123']]])->assertSessionHasNoErrors();
        Mail::assertQueued(PesananTokoDikirim::class, fn (PesananTokoDikirim $mail) => $mail->hasTo($order->email) && $mail->order->shipments->pluck('tracking_number')->all() === ['JNE123']);

        $this->actingAs($admin)->post($url, ['status' => 'completed'])->assertSessionHasNoErrors();
        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
        $this->actingAs($admin)->post($url, ['status' => 'cancelled'])->assertSessionHasErrors('status');
    }

    public function test_dashboard_menampilkan_ringkasan_toko_harian(): void
    {
        $this->pesananDibayar();
        $this->pesananDibayar(['status' => OrderStatus::Shipped]);
        Order::factory()->create(['status' => OrderStatus::PendingPayment]);

        $this->actingAs($this->superadmin())->get('/superadmin')->assertInertia(fn (Assert $page) => $page
            ->where('toko.perluDiproses', 1)
            ->where('toko.hariIni.pesanan', 2)
            ->where('toko.hariIni.omzet', 410000)
            ->where('toko.hariIni.untung', 80000)
            ->has('toko.harian', 7)
        );
    }

    public function test_mayar_service_menolak_server_produksi_saat_mode_sandbox(): void
    {
        Http::fake();
        config(['mayar.env' => 'sandbox', 'mayar.base_url' => 'https://api.mayar.id', 'mayar.api_key' => 'kunci-uji']);

        try {
            app(MayarService::class)->getPayment('pay-1');
            $this->fail('Seharusnya ditolak.');
        } catch (MayarException $galat) {
            $this->assertStringContainsString('produksi', $galat->getMessage());
        }

        Http::assertNothingSent();
    }

    public function test_mayar_service_error_tidak_membocorkan_api_key(): void
    {
        Http::fake(['*' => Http::response(['statusCode' => 401, 'messages' => 'unauthorized'], 401)]);
        config(['mayar.env' => 'sandbox', 'mayar.base_url' => 'https://api.mayar.io', 'mayar.api_key' => 'kunci-sangat-rahasia']);

        try {
            app(MayarService::class)->getPayment('pay-1');
            $this->fail('Seharusnya gagal.');
        } catch (MayarException $galat) {
            $this->assertStringContainsString('401', $galat->getMessage());
            $this->assertStringNotContainsString('kunci-sangat-rahasia', $galat->getMessage());
        }

        config(['mayar.api_key' => '']);
        $this->expectException(MayarException::class);
        app(MayarService::class)->getPayment('pay-1');
    }
}
