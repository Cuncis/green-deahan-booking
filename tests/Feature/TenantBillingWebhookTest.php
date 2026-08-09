<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian aktivasi otomatis tenant lewat webhook Mayar
 * (PembayaranController::prosesWebhookLangganan()), pengganti aktivasi
 * manual lewat `php artisan tenant:activate`. Verifikasi token webhook itu
 * sendiri ada di WebhookSignatureTest (tidak bergantung pada tipe payload).
 */
class TenantBillingWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function buatTenantMenunggu(array $override = []): Tenant
    {
        return Tenant::factory()->create(array_merge([
            'paket' => 'pro',
            'status_aktif' => false,
            'tanggal_mulai' => null,
            'tanggal_berakhir' => null,
            'kode_pendaftaran' => Tenant::generateKodePendaftaran(),
            'email_admin' => 'owner@arenabaru.test',
        ], $override));
    }

    /**
     * paymentMethod sengaja TIDAK disertakan di payload ini, untuk
     * membuktikan webhook langganan tenant tidak digatekan oleh metode
     * pembayaran (beda dari webhook booking), lihat
     * PembayaranController::parseMayarPayload().
     */
    private function payloadLangganan(Tenant $tenant, string $status): array
    {
        return [
            'event' => 'payment.received',
            'data' => [
                'id' => 'invoice-'.$tenant->id,
                'transactionId' => 'TRX-'.$tenant->id,
                'status' => $status,
                'amount' => Tenant::HARGA_PAKET[$tenant->paket],
                'extraData' => [
                    'noCustomer' => $tenant->kode_pendaftaran,
                    'tipe' => 'langganan_tenant',
                ],
            ],
        ];
    }

    private function postWebhook(array $payload)
    {
        return $this->postJson('/api/webhook/pembayaran?token=test-mayar-webhook-token', $payload);
    }

    public function test_webhook_langganan_sukses_mengaktifkan_tenant_dan_membuat_invitation(): void
    {
        $tenant = $this->buatTenantMenunggu();

        $response = $this->postWebhook($this->payloadLangganan($tenant, 'PAID'));

        $response->assertOk();
        $response->assertJson(['message' => 'Webhook diterima.']);

        $tenant->refresh();
        $this->assertTrue($tenant->status_aktif);
        $this->assertNotNull($tenant->dibayar_at);
        $this->assertNotNull($tenant->tanggal_mulai);
        $this->assertNotNull($tenant->tanggal_berakhir);
        $this->assertEqualsWithDelta(today()->addDays(365)->timestamp, $tenant->tanggal_berakhir->timestamp, 5);

        $this->assertDatabaseHas('tenant_invitations', [
            'tenant_id' => $tenant->id,
            'email' => 'owner@arenabaru.test',
            'role' => 'owner',
        ]);
    }

    public function test_webhook_langganan_diulang_tidak_memperpanjang_tanggal_berakhir_dua_kali(): void
    {
        $tenant = $this->buatTenantMenunggu();
        $payload = $this->payloadLangganan($tenant, 'PAID');

        $this->postWebhook($payload)->assertOk();
        $tanggalBerakhirPertama = $tenant->fresh()->tanggal_berakhir;

        $this->postWebhook($payload)->assertOk();
        $tanggalBerakhirKedua = $tenant->fresh()->tanggal_berakhir;

        $this->assertTrue($tanggalBerakhirPertama->equalTo($tanggalBerakhirKedua));
        $this->assertDatabaseCount('tenant_invitations', 1);
    }

    public function test_webhook_langganan_belum_dibayar_tidak_mengaktifkan_tenant(): void
    {
        $tenant = $this->buatTenantMenunggu();

        $response = $this->postWebhook($this->payloadLangganan($tenant, 'UNPAID'));

        $response->assertOk();

        $tenant->refresh();
        $this->assertFalse($tenant->status_aktif);
        $this->assertNull($tenant->dibayar_at);
        $this->assertDatabaseCount('tenant_invitations', 0);
    }

    public function test_webhook_langganan_kode_pendaftaran_tidak_ditemukan_mengembalikan_404(): void
    {
        $response = $this->postJson('/api/webhook/pembayaran?token=test-mayar-webhook-token', [
            'event' => 'payment.received',
            'data' => [
                'id' => 'invoice-x',
                'status' => 'PAID',
                'amount' => 1_500_000,
                'extraData' => [
                    'noCustomer' => 'TNT-TIDAK-ADA',
                    'tipe' => 'langganan_tenant',
                ],
            ],
        ]);

        $response->assertNotFound();
    }

    public function test_webhook_langganan_tanpa_email_admin_tetap_aktif_tanpa_invitation(): void
    {
        $tenant = $this->buatTenantMenunggu(['email_admin' => null]);

        $response = $this->postWebhook($this->payloadLangganan($tenant, 'PAID'));

        $response->assertOk();

        $tenant->refresh();
        $this->assertTrue($tenant->status_aktif);
        $this->assertDatabaseCount('tenant_invitations', 0);
    }
}
