<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Mail\PesananTokoDibayar;
use App\Models\MayarWebhookLog;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Webhook Mayar untuk pesanan toko. Isi webhook tidak dipercaya: status dan
 * nominal dicek ulang ke API Mayar (dipalsukan dengan Http::fake()).
 */
class WebhookTokoTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/webhook/pembayaran?token=test-mayar-webhook-token';

    protected function setUp(): void
    {
        parent::setUp();

        config(['mayar.env' => 'sandbox', 'mayar.base_url' => 'https://api.mayar.club', 'mayar.api_key' => 'kunci-uji', 'mayar.webhook_token' => 'test-mayar-webhook-token', 'toko.email_admin' => 'admin@greendeahan.test']);
        Mail::fake();
    }

    private function pesanan(array $override = []): Order
    {
        return Order::factory()->create(array_merge(['total' => 110000, 'mayar_payment_id' => 'pay-uuid-123'], $override));
    }

    private function fakeDetailMayar(string $status, int $amount): void
    {
        Http::fake([
            'api.mayar.club/hl/v2/payments/pay-uuid-123' => Http::response([
                'statusCode' => 200,
                'messages' => 'success',
                'data' => ['id' => 'pay-uuid-123', 'status' => $status, 'amount' => $amount],
            ]),
        ]);
    }

    /**
     * Isi webhook bisa saja dipalsukan: nominal dan status di sini sengaja
     * "meyakinkan", yang menentukan tetap jawaban API Mayar.
     *
     * @return array<string, mixed>
     */
    private function webhook(string $event = 'payment.received', array $data = []): array
    {
        return ['event' => $event, 'data' => array_merge(['id' => 'pay-uuid-123', 'status' => 'SUCCESS', 'amount' => 110000], $data)];
    }

    public function test_webhook_valid_menandai_pesanan_dibayar_dan_mengirim_email_admin(): void
    {
        $this->fakeDetailMayar('paid', 110000);
        $order = $this->pesanan();

        $this->postJson(self::URL, $this->webhook())->assertOk();

        $order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertNotNull($order->paid_at);
        Mail::assertQueued(PesananTokoDibayar::class, fn (PesananTokoDibayar $mail) => $mail->hasTo('admin@greendeahan.test') && $mail->order->is($order));
        $this->assertDatabaseHas('mayar_webhook_logs', ['order_id' => $order->id, 'event' => 'payment.received', 'result' => 'diproses']);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.mayar.club/hl/v2/payments/pay-uuid-123');
    }

    public function test_webhook_ganda_hanya_diproses_sekali(): void
    {
        $this->fakeDetailMayar('paid', 110000);
        $order = $this->pesanan();

        $this->postJson(self::URL, $this->webhook())->assertOk();
        $dibayarPertama = $order->fresh()->paid_at;
        $this->travel(5)->minutes();
        $this->postJson(self::URL, $this->webhook())->assertOk();

        $this->assertEquals($dibayarPertama, $order->fresh()->paid_at);
        Mail::assertQueued(PesananTokoDibayar::class, 1);
        $this->assertSame(['diproses', 'duplikat'], MayarWebhookLog::orderBy('id')->pluck('result')->all());
    }

    public function test_webhook_dengan_nominal_berbeda_ditolak(): void
    {
        $this->fakeDetailMayar('paid', 1000);
        $order = $this->pesanan();

        $this->postJson(self::URL, $this->webhook(data: ['amount' => 110000]))->assertOk();

        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
        Mail::assertNothingQueued();
        $log = MayarWebhookLog::sole();
        $this->assertSame('ditolak', $log->result);
        $this->assertStringContainsString('1000', $log->message);
    }

    public function test_webhook_palsu_yang_bilang_lunas_ditolak_kalau_mayar_bilang_belum_bayar(): void
    {
        $this->fakeDetailMayar('unpaid', 110000);
        $order = $this->pesanan();

        $this->postJson(self::URL, $this->webhook())->assertOk();

        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
        $this->assertSame('ditolak', MayarWebhookLog::sole()->result);
    }

    public function test_webhook_tanpa_token_valid_ditolak_dan_tidak_dicatat(): void
    {
        Http::fake();
        $order = $this->pesanan();

        $this->postJson('/api/webhook/pembayaran?token=salah', $this->webhook())->assertUnauthorized();
        $this->postJson('/api/webhook/pembayaran', $this->webhook())->assertUnauthorized();

        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
        $this->assertSame(0, MayarWebhookLog::count());
        Http::assertNothingSent();
    }

    public function test_event_selain_payment_received_diabaikan_dengan_200(): void
    {
        Http::fake();
        $order = $this->pesanan();

        $this->postJson(self::URL, $this->webhook('payment.reminder'))->assertOk();

        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
        $this->assertSame('diabaikan', MayarWebhookLog::sole()->result);
        Http::assertNothingSent();
    }

    public function test_pesanan_ditemukan_lewat_extra_data_walau_id_webhook_berbeda(): void
    {
        $this->fakeDetailMayar('paid', 110000);
        $order = $this->pesanan();

        $this->postJson(self::URL, $this->webhook(data: ['id' => 'trx-lain', 'extraData' => ['tipe' => 'toko', 'kode' => $order->code]]))->assertOk();

        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
    }

    public function test_pesanan_kedaluwarsa_yang_ternyata_dibayar_tetap_ditandai_dibayar(): void
    {
        $this->fakeDetailMayar('paid', 110000);
        $order = $this->pesanan(['status' => OrderStatus::Expired]);

        $this->postJson(self::URL, $this->webhook())->assertOk();

        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
    }

    public function test_pesanan_yang_sudah_diproses_admin_tidak_mundur_ke_dibayar(): void
    {
        $this->fakeDetailMayar('paid', 110000);
        $order = $this->pesanan(['status' => OrderStatus::Shipped, 'paid_at' => now()->subDay()]);

        $this->postJson(self::URL, $this->webhook())->assertOk();

        $this->assertSame(OrderStatus::Shipped, $order->fresh()->status);
        $this->assertSame('duplikat', MayarWebhookLog::sole()->result);
        Http::assertNothingSent();
    }

    public function test_token_webhook_tidak_ikut_tersimpan_di_log(): void
    {
        $this->fakeDetailMayar('paid', 110000);
        $this->pesanan();

        $this->postJson(self::URL, $this->webhook())->assertOk();

        $this->assertStringNotContainsString('test-mayar-webhook-token', json_encode(MayarWebhookLog::sole()->payload));
    }
}
