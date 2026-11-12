<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use Carbon\CarbonImmutable;

/**
 * Ringkasan toko harian untuk dashboard superadmin. "Hari" dihitung di zona
 * Asia/Jakarta berdasarkan waktu bayar (paid_at). Pesanan yang dibatalkan
 * setelah dibayar tidak dihitung.
 */
class RingkasanTokoService
{
    private const ZONA = 'Asia/Jakarta';

    /**
     * @return array{perluDiproses: int, hariIni: array{pesanan: int, omzet: int, untung: int}, harian: array<int, array{tanggal: string, label: string, pesanan: int, omzet: int, untung: int}>}
     */
    public function ringkasan(int $hari = 7): array
    {
        $awal = CarbonImmutable::now(self::ZONA)->startOfDay()->subDays($hari - 1);

        $dibayar = Order::with('items')
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', $awal->utc())
            ->whereNotIn('status', [OrderStatus::Cancelled, OrderStatus::PendingPayment, OrderStatus::Expired])
            ->get()
            ->groupBy(fn (Order $order) => $order->paid_at->timezone(self::ZONA)->toDateString());

        $harian = collect(range(0, $hari - 1))->map(function (int $i) use ($awal, $dibayar) {
            $tanggal = $awal->addDays($i);
            $pesanan = $dibayar->get($tanggal->toDateString(), collect());

            return [
                'tanggal' => $tanggal->toDateString(),
                'label' => $tanggal->locale('id')->translatedFormat('D, j M'),
                'pesanan' => $pesanan->count(),
                'omzet' => (int) $pesanan->sum('total'),
                'untung' => (int) $pesanan->sum(fn (Order $order) => $order->untung()),
            ];
        })->all();

        $hariIni = end($harian);

        return [
            'perluDiproses' => Order::where('status', OrderStatus::Paid)->count(),
            'hariIni' => ['pesanan' => $hariIni['pesanan'], 'omzet' => $hariIni['omzet'], 'untung' => $hariIni['untung']],
            'harian' => $harian,
        ];
    }
}
