<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Pesanan toko yang belum dibayar lewat batas waktu (config toko.jam_kedaluwarsa,
 * default 24 jam) diubah menjadi expired. Kalau pelanggan ternyata tetap
 * membayar sesudahnya, webhook masih bisa menandainya dibayar (lihat
 * ProsesPembayaranToko), karena uangnya sudah masuk.
 */
#[Signature('toko:kedaluwarsakan-pesanan')]
#[Description('Ubah pesanan toko yang belum dibayar lewat batas waktu menjadi expired')]
class KedaluwarsakanPesananToko extends Command
{
    public function handle(): int
    {
        $jumlah = Order::where('status', OrderStatus::PendingPayment)
            ->where('created_at', '<', now()->subHours((int) config('toko.jam_kedaluwarsa')))
            ->update(['status' => OrderStatus::Expired, 'updated_at' => now()]);

        $this->info("{$jumlah} pesanan ditandai kedaluwarsa.");

        return self::SUCCESS;
    }
}
