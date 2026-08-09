<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TenantRegistrationControllerTest extends TestCase
{
    use RefreshDatabase;

    private const MAYAR_REDIRECT_URL = 'https://sandbox.mayar.club/invoice/fake-langganan-invoice-id';

    /**
     * store() sekarang selalu mencoba bikin invoice Mayar (lihat
     * PaymentService::createLanggananTransaction()), jadi createInvoice()
     * di-mock di semua test di sini supaya tidak ada panggilan network
     * sungguhan ke sandbox Mayar. Lihat PaymentServiceTest untuk pengujian
     * PaymentService itu sendiri, dan BookingControllerTest untuk pola yang
     * sama di jalur booking.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->partialMock(PaymentService::class, function ($mock) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('createInvoice')->andReturn([
                    'data' => [
                        'id' => 'fake-langganan-invoice-id',
                        'link' => self::MAYAR_REDIRECT_URL,
                    ],
                ]);
        });
    }

    private function dataPendaftaran(array $override = []): array
    {
        return array_merge([
            'nama_bisnis' => 'Arena Baru Sport',
            'subdomain' => 'arena-baru-sport',
            'nama_pic' => 'Budi PIC',
            'email_pic' => 'budi@arenabarusport.com',
            'whatsapp_pic' => '081234567890',
            'paket' => 'pro',
        ], $override);
    }

    public function test_halaman_harga_bisa_diakses(): void
    {
        $response = $this->get(route('pricing'));

        $response->assertOk();
        $response->assertSee('Basic');
        $response->assertSee('Pro');
        $response->assertSee('Premium');
    }

    public function test_halaman_daftar_prefill_paket_dari_query_string(): void
    {
        $response = $this->get(route('daftar.show', ['paket' => 'premium']));

        $response->assertOk();
        $response->assertViewHas('paketTerpilih', 'premium');
    }

    public function test_pendaftaran_berhasil_membuat_tenant_fitur_cabang_dan_kirim_email(): void
    {
        Mail::fake();

        $response = $this->post(route('daftar.store'), $this->dataPendaftaran());

        $response->assertRedirect(self::MAYAR_REDIRECT_URL);

        $tenant = Tenant::where('domain', 'arena-baru-sport.greendeahan.com')->first();
        $this->assertNotNull($tenant);
        $this->assertSame('pro', $tenant->paket);
        $this->assertFalse($tenant->status_aktif);
        $this->assertNotEmpty($tenant->kode_pendaftaran);
        $this->assertSame('budi@arenabarusport.com', $tenant->email_admin);
        $this->assertSame('081234567890', $tenant->whatsapp_admin);
        $this->assertTrue($tenant->punyaFitur('kode_promo'));

        $this->assertDatabaseHas('cabang', [
            'tenant_id' => $tenant->id,
            'nama_cabang' => 'Cabang Utama',
        ]);
    }

    public function test_pendaftaran_mengirim_jumlah_invoice_sesuai_paket_dan_addon_custom_domain(): void
    {
        Mail::fake();

        $capturedParams = null;
        $this->partialMock(PaymentService::class, function ($mock) use (&$capturedParams) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('createInvoice')
                ->withArgs(function ($params) use (&$capturedParams) {
                    $capturedParams = $params;

                    return true;
                })
                ->andReturn(['data' => ['id' => 'x', 'link' => self::MAYAR_REDIRECT_URL]]);
        });

        $this->post(route('daftar.store'), $this->dataPendaftaran([
            'paket' => 'pro',
            'custom_domain_diminta' => 'arenabarusport.com',
        ]));

        $this->assertSame(
            Tenant::HARGA_PAKET['pro'] + Tenant::HARGA_ADDON_CUSTOM_DOMAIN,
            $capturedParams['items'][0]['rate'],
        );
    }

    public function test_pendaftaran_gagal_kalau_subdomain_sudah_dipakai(): void
    {
        Tenant::factory()->create(['domain' => 'arena-baru-sport.greendeahan.com']);

        $response = $this->post(route('daftar.store'), $this->dataPendaftaran());

        $response->assertSessionHasErrors('subdomain');
    }

    public function test_pendaftaran_gagal_kalau_subdomain_format_tidak_valid(): void
    {
        $response = $this->post(route('daftar.store'), $this->dataPendaftaran(['subdomain' => 'Arena Baru!']));

        $response->assertSessionHasErrors('subdomain');
    }

    public function test_pendaftaran_gagal_kalau_subdomain_kurang_dari_3_karakter(): void
    {
        $response = $this->post(route('daftar.store'), $this->dataPendaftaran(['subdomain' => 'ab']));

        $response->assertSessionHasErrors('subdomain');
    }

    public function test_pendaftaran_gagal_kalau_email_pic_sudah_dipakai_user_lain(): void
    {
        $user = User::factory()->create(['email' => 'budi@arenabarusport.com']);

        $response = $this->post(route('daftar.store'), $this->dataPendaftaran());

        $response->assertSessionHasErrors('email_pic');
        $this->assertNotNull($user);
    }

    public function test_pendaftaran_gagal_kalau_paket_tidak_dikenal(): void
    {
        $response = $this->post(route('daftar.store'), $this->dataPendaftaran(['paket' => 'gratis']));

        $response->assertSessionHasErrors('paket');
    }

    public function test_pendaftaran_bisa_diakses_dari_host_yang_bukan_tenant_manapun(): void
    {
        $response = $this->withHeaders(['Host' => 'greendeahan.com'])
            ->post('/daftar', $this->dataPendaftaran(['subdomain' => 'dari-host-asing']));

        $response->assertRedirect(self::MAYAR_REDIRECT_URL);
        $this->assertDatabaseHas('cabang', ['nama_cabang' => 'Cabang Utama']);
        $this->assertNotNull(Cabang::whereHas('tenant', fn ($q) => $q->where('domain', 'dari-host-asing.greendeahan.com'))->first());
    }

    /**
     * Tenant hasil pendaftaran mandiri berstatus nonaktif sampai invoice
     * Mayar-nya dibayar (lihat TenantRegistrationController@store dan
     * PembayaranController::prosesWebhookLangganan()), jadi domain-nya
     * sendiri harus belum bisa diakses sama sekali (IdentifikasiTenant
     * menolak tenant nonaktif) sampai webhook itu datang.
     */
    public function test_tenant_baru_status_nonaktif_tidak_bisa_diakses(): void
    {
        Mail::fake();

        $this->post(route('daftar.store'), $this->dataPendaftaran());

        $response = $this->get('http://arena-baru-sport.greendeahan.com/');

        $response->assertNotFound();
    }

    public function test_pendaftaran_pro_bisa_minta_custom_domain(): void
    {
        Mail::fake();

        $response = $this->post(route('daftar.store'), $this->dataPendaftaran([
            'paket' => 'pro',
            'custom_domain_diminta' => 'arenabarusport.com',
        ]));

        $response->assertRedirect(self::MAYAR_REDIRECT_URL);

        $tenant = Tenant::where('domain', 'arena-baru-sport.greendeahan.com')->firstOrFail();
        $this->assertSame('arenabarusport.com', $tenant->custom_domain_diminta);
        $this->assertNull($tenant->custom_domain);
    }

    public function test_pendaftaran_basic_tidak_bisa_minta_custom_domain(): void
    {
        $response = $this->post(route('daftar.store'), $this->dataPendaftaran([
            'paket' => 'basic',
            'custom_domain_diminta' => 'arenabarusport.com',
        ]));

        $response->assertSessionHasErrors('custom_domain_diminta');
        $this->assertDatabaseMissing('tenants', ['domain' => 'arena-baru-sport.greendeahan.com']);
    }

    public function test_pendaftaran_gagal_kalau_custom_domain_diminta_format_tidak_valid(): void
    {
        $response = $this->post(route('daftar.store'), $this->dataPendaftaran([
            'paket' => 'pro',
            'custom_domain_diminta' => 'bukan domain valid!!',
        ]));

        $response->assertSessionHasErrors('custom_domain_diminta');
    }

    public function test_pendaftaran_gagal_kalau_custom_domain_diminta_sudah_dipakai(): void
    {
        Tenant::factory()->create(['custom_domain' => 'sudahdipakai.com']);

        $response = $this->post(route('daftar.store'), $this->dataPendaftaran([
            'paket' => 'pro',
            'custom_domain_diminta' => 'sudahdipakai.com',
        ]));

        $response->assertSessionHasErrors('custom_domain_diminta');
    }

    public function test_pendaftaran_gagal_kalau_custom_domain_diminta_adalah_subdomain_greendeahan(): void
    {
        $response = $this->post(route('daftar.store'), $this->dataPendaftaran([
            'paket' => 'pro',
            'custom_domain_diminta' => 'lain.greendeahan.com',
        ]));

        $response->assertSessionHasErrors('custom_domain_diminta');
    }

    public function test_custom_domain_diminta_opsional_untuk_paket_pro(): void
    {
        Mail::fake();

        $response = $this->post(route('daftar.store'), $this->dataPendaftaran(['paket' => 'pro']));

        $response->assertRedirect(self::MAYAR_REDIRECT_URL);
        $response->assertSessionHasNoErrors();
    }

    /**
     * Kalau pembuatan invoice Mayar gagal (network error, dst), tenant tetap
     * tersimpan (tidak boleh hilang cuma karena gateway pembayaran gagal),
     * dan customer diberi tahu lewat halaman sukses versi fallback,
     * bukan error 500.
     */
    public function test_pendaftaran_tetap_tersimpan_walau_pembuatan_invoice_mayar_gagal(): void
    {
        Mail::fake();

        $this->partialMock(PaymentService::class, function ($mock) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('createInvoice')
                ->andThrow(new \RuntimeException('Mayar API gagal.'));
        });

        $response = $this->post(route('daftar.store'), $this->dataPendaftaran());

        $response->assertOk();
        $response->assertSee('Pendaftaran Tersimpan');

        $tenant = Tenant::where('domain', 'arena-baru-sport.greendeahan.com')->first();
        $this->assertNotNull($tenant);
        $this->assertFalse($tenant->status_aktif);
    }
}
