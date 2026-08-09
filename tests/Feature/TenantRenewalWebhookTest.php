<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantTagihanPerpanjangan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian aktivasi ulang tenant lewat webhook Mayar untuk invoice
 * perpanjangan (PembayaranController::prosesWebhookPerpanjangan()).
 * Verifikasi token webhook itu sendiri ada di WebhookSignatureTest, dan
 * pengujian jalur pendaftaran tenant baru (bukan perpanjangan) ada di
 * TenantBillingWebhookTest.
 */
class TenantRenewalWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function buatTagihan(array $tenantOverride = []): TenantTagihanPerpanjangan
    {
        $tenant = Tenant::factory()->create(array_merge([
            'paket' => 'pro',
            'status_aktif' => true,
            'tanggal_berakhir' => today()->addDays(10),
        ], $tenantOverride));

        return TenantTagihanPerpanjangan::create([
            'tenant_id' => $tenant->id,
            'kode' => TenantTagihanPerpanjangan::generateKode(),
            'jumlah' => Tenant::hitungHargaPerpanjangan('pro'),
            'link_pembayaran' => 'https://sandbox.mayar.club/invoice/fake',
            'status' => 'menunggu',
            'dikirim_pada' => now(),
        ]);
    }

    private function payloadPerpanjangan(TenantTagihanPerpanjangan $tagihan, string $status): array
    {
        return [
            'event' => 'payment.received',
            'data' => [
                'id' => 'invoice-'.$tagihan->id,
                'transactionId' => 'TRX-'.$tagihan->id,
                'status' => $status,
                'amount' => $tagihan->jumlah,
                'extraData' => [
                    'noCustomer' => $tagihan->kode,
                    'tipe' => 'perpanjangan_tenant',
                ],
            ],
        ];
    }

    private function postWebhook(array $payload)
    {
        return $this->postJson('/api/webhook/pembayaran?token=test-mayar-webhook-token', $payload);
    }

    public function test_webhook_sukses_menandai_tagihan_dibayar_dan_memperpanjang_tanggal_berakhir(): void
    {
        $tagihan = $this->buatTagihan();
        $tanggalBerakhirSebelum = $tagihan->tenant->tanggal_berakhir->copy();

        $response = $this->postWebhook($this->payloadPerpanjangan($tagihan, 'PAID'));

        $response->assertOk();
        $response->assertJson(['message' => 'Webhook diterima.']);

        $tagihan->refresh();
        $this->assertSame('dibayar', $tagihan->status);
        $this->assertNotNull($tagihan->dibayar_pada);

        $tenant = $tagihan->tenant->fresh();
        $this->assertTrue($tenant->status_aktif);
        $this->assertTrue($tenant->tanggal_berakhir->greaterThan($tanggalBerakhirSebelum));
        $this->assertEqualsWithDelta($tanggalBerakhirSebelum->addDays(365)->timestamp, $tenant->tanggal_berakhir->timestamp, 5);
    }

    public function test_webhook_sukses_mengaktifkan_ulang_tenant_yang_sudah_dinonaktifkan(): void
    {
        $tagihan = $this->buatTagihan([
            'status_aktif' => false,
            'tanggal_berakhir' => today()->subDays(9),
        ]);

        $this->postWebhook($this->payloadPerpanjangan($tagihan, 'PAID'))->assertOk();

        $this->assertTrue($tagihan->tenant->fresh()->status_aktif);
    }

    public function test_webhook_diulang_tidak_memperpanjang_dua_kali(): void
    {
        $tagihan = $this->buatTagihan();
        $payload = $this->payloadPerpanjangan($tagihan, 'PAID');

        $this->postWebhook($payload)->assertOk();
        $tanggalBerakhirPertama = $tagihan->tenant->fresh()->tanggal_berakhir;

        $this->postWebhook($payload)->assertOk();
        $tanggalBerakhirKedua = $tagihan->tenant->fresh()->tanggal_berakhir;

        $this->assertTrue($tanggalBerakhirPertama->equalTo($tanggalBerakhirKedua));
    }

    public function test_webhook_belum_dibayar_tidak_memperpanjang(): void
    {
        $tagihan = $this->buatTagihan();

        $this->postWebhook($this->payloadPerpanjangan($tagihan, 'UNPAID'))->assertOk();

        $tagihan->refresh();
        $this->assertSame('menunggu', $tagihan->status);
        $this->assertTrue($tagihan->tenant->fresh()->status_aktif);
    }

    public function test_webhook_kode_tidak_ditemukan_mengembalikan_404(): void
    {
        $response = $this->postJson('/api/webhook/pembayaran?token=test-mayar-webhook-token', [
            'event' => 'payment.received',
            'data' => [
                'id' => 'invoice-x',
                'status' => 'PAID',
                'amount' => 1_750_000,
                'extraData' => [
                    'noCustomer' => 'PRP-TIDAK-ADA',
                    'tipe' => 'perpanjangan_tenant',
                ],
            ],
        ]);

        $response->assertNotFound();
    }
}
