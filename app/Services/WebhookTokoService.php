<?php

namespace App\Services;

use App\Jobs\ProsesPembayaranToko;
use App\Models\MayarWebhookLog;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

/**
 * Webhook Mayar untuk pesanan toko. Mayar hanya punya satu URL webhook per
 * akun, jadi webhook toko masuk lewat PembayaranController (URL yang sama
 * dengan booking) dan diteruskan ke sini kalau pembayarannya milik pesanan
 * toko.
 *
 * Isi webhook TIDAK dipercaya: job ProsesPembayaranToko mengecek ulang
 * status dan nominal langsung ke API Mayar sebelum pesanan ditandai dibayar.
 */
class WebhookTokoService
{
    public const EVENT_DIBAYAR = 'payment.received';

    /**
     * Cari pesanan toko dari isi webhook: lewat id pembayaran Mayar yang
     * tersimpan di pesanan, atau lewat extraData yang kita kirim saat membuat
     * payment request.
     *
     * @param  array<string, mixed>  $payload
     */
    public function cariPesanan(array $payload): ?Order
    {
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        $kandidatId = collect(['id', 'paymentRequestId', 'paymentLinkId', 'productId', 'paymentId'])
            ->map(fn (string $kunci) => $data[$kunci] ?? null)
            ->filter(fn ($nilai) => is_string($nilai) && $nilai !== '')
            ->values()
            ->all();

        if ($kandidatId !== []) {
            $order = Order::whereIn('mayar_payment_id', $kandidatId)->first();

            if ($order) {
                return $order;
            }
        }

        $extra = is_array($data['extraData'] ?? null) ? $data['extraData'] : [];

        if (($extra['tipe'] ?? null) === 'toko' && is_string($extra['kode'] ?? null)) {
            return Order::where('code', $extra['kode'])->first();
        }

        return null;
    }

    /**
     * Balas Mayar secepatnya; pengecekan ke API Mayar dan update pesanan
     * berjalan di queue.
     */
    public function terima(MayarWebhookLog $log, Order $order): JsonResponse
    {
        $log->update(['order_id' => $order->id]);

        if ($log->event !== self::EVENT_DIBAYAR) {
            $log->update(['result' => 'diabaikan', 'message' => 'Event selain payment.received tidak diproses.', 'processed_at' => now()]);

            return response()->json(['message' => 'Event diabaikan.']);
        }

        ProsesPembayaranToko::dispatch($log->id, $order->id);

        return response()->json(['message' => 'Webhook diterima.']);
    }
}
