<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Exceptions\MayarException;
use App\Mail\PesananTokoDibayar;
use App\Models\MayarWebhookLog;
use App\Models\Order;
use App\Services\MayarService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Konfirmasi pembayaran pesanan toko setelah webhook Mayar masuk.
 *
 * Isi webhook tidak dipercaya: status dan nominal diambil langsung dari
 * API Mayar (MayarService::getPayment()) memakai id pembayaran yang KITA
 * simpan saat checkout. Pesanan hanya ditandai dibayar kalau Mayar bilang
 * "paid" dan nominalnya sama persis dengan total pesanan.
 *
 * Idempotent: baris pesanan dikunci dan statusnya dicek ulang, jadi webhook
 * yang sama datang dua kali (atau job jalan dua kali) hanya diproses sekali.
 */
class ProsesPembayaranToko implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60, 300];

    public function __construct(public int $logId, public int $orderId) {}

    public function handle(MayarService $mayar): void
    {
        $log = MayarWebhookLog::findOrFail($this->logId);
        $order = Order::findOrFail($this->orderId);

        if (! in_array($order->status, [OrderStatus::PendingPayment, OrderStatus::Expired], true)) {
            $this->catat($log, 'duplikat', "Pesanan sudah berstatus {$order->status->value}, tidak diproses lagi.");

            return;
        }

        if (! $order->mayar_payment_id) {
            $this->catat($log, 'ditolak', 'Pesanan tidak punya id pembayaran Mayar.');

            return;
        }

        try {
            $pembayaran = $mayar->getPayment($order->mayar_payment_id);
        } catch (MayarException $galat) {
            $this->catat($log, 'gagal_cek', $galat->getMessage());

            throw $galat; // dicoba ulang oleh queue ($tries, $backoff)
        }

        $status = strtolower((string) ($pembayaran['status'] ?? ''));
        $nominal = (int) ($pembayaran['amount'] ?? 0);

        if ($status !== 'paid') {
            $this->catat($log, 'ditolak', "Mayar melaporkan status '{$status}', bukan paid.");

            return;
        }

        if ($nominal !== $order->total) {
            $this->catat($log, 'ditolak', "Nominal Mayar {$nominal} tidak sama dengan total pesanan {$order->total}.");

            return;
        }

        $baruDibayar = DB::transaction(function () {
            $order = Order::whereKey($this->orderId)->lockForUpdate()->firstOrFail();

            if (! in_array($order->status, [OrderStatus::PendingPayment, OrderStatus::Expired], true)) {
                return false;
            }

            $order->update(['status' => OrderStatus::Paid, 'paid_at' => now()]);

            return true;
        });

        if (! $baruDibayar) {
            $this->catat($log, 'duplikat', 'Pesanan sudah diproses oleh webhook lain.');

            return;
        }

        $this->catat($log, 'diproses', 'Pesanan ditandai dibayar.');

        Mail::to(config('toko.email_admin'))->queue(new PesananTokoDibayar($order->fresh('items')));
    }

    private function catat(MayarWebhookLog $log, string $hasil, string $pesan): void
    {
        $log->update(['result' => $hasil, 'message' => mb_substr($pesan, 0, 255), 'processed_at' => now()]);
    }
}
