<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Mail\PesananTokoDibayar;
use App\Mail\PesananTokoDiterima;
use App\Models\Order;
use App\Models\PengaturanSitus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * SEMENTARA, hapus setelah sandbox Mayar bisa dipakai lagi.
 *
 * Mode uji bayar toko: selama sandbox Mayar mati, superadmin bisa menguji
 * alur toko sampai selesai tanpa Mayar. Dinyalakan dari halaman Pesanan Toko
 * di superadmin. Saat aktif dan yang membuka adalah superadmin yang login:
 * checkout tidak memanggil Mayar, dan halaman pesanan menampilkan gelembung
 * "Tandai dibayar". Pengunjung biasa tidak terpengaruh sama sekali.
 */
class BypassBayarToko
{
    public const KUNCI = 'toko_bypass_bayar';

    public const CATATAN = 'Dibayar lewat mode uji (bypass Mayar), bukan pembayaran sungguhan.';

    public function aktif(): bool
    {
        return PengaturanSitus::query()->where('kunci', self::KUNCI)->value('nilai') === '1';
    }

    public function atur(bool $aktif): void
    {
        PengaturanSitus::updateOrCreate(['kunci' => self::KUNCI], ['nilai' => $aktif ? '1' : '0']);
    }

    public function bolehDipakai(?User $user): bool
    {
        return $user?->isSuperadmin() === true && $this->aktif();
    }

    /**
     * Tandai pesanan dibayar seperti ProsesPembayaranToko, tanpa cek ke Mayar.
     * Mengembalikan false kalau pesanan sudah tidak menunggu pembayaran.
     */
    public function tandaiDibayar(Order $order): bool
    {
        $baruDibayar = DB::transaction(function () use ($order) {
            $terkunci = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! in_array($terkunci->status, [OrderStatus::PendingPayment, OrderStatus::Expired], true)) {
                return false;
            }

            $terkunci->update([
                'status' => OrderStatus::Paid,
                'paid_at' => now(),
                'admin_notes' => trim(($terkunci->admin_notes ?? '')."\n".self::CATATAN),
            ]);

            return true;
        });

        if ($baruDibayar) {
            Mail::to(config('toko.email_admin'))->queue(new PesananTokoDibayar($order->fresh('items')));
            Mail::to($order->email)->queue(new PesananTokoDiterima($order->fresh('items')));
            $order->messages()->create([
                'channel' => 'email',
                'template' => 'diterima',
                'subject' => "Pembayaran diterima, pesanan {$order->code} segera kami proses",
                'body' => 'Email otomatis: konfirmasi pembayaran diterima, berisi rincian pesanan dan link status.',
            ]);
        }

        return $baruDibayar;
    }
}
