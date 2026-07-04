<?php

namespace App\Services;

use App\Models\Booking;
use Midtrans\Config;
use Midtrans\Snap;

/**
 * Integrasi Midtrans Snap (bukan Core API), customer diarahkan ke halaman
 * pembayaran hosted Midtrans dan bebas pilih metode apapun yang aktif di
 * dashboard merchant (QRIS/VA/e-wallet/dst), bukan metode yang sudah
 * ditentukan lebih dulu dari sisi kita.
 */
class PaymentService
{
    public function __construct()
    {
        $this->setupMidtrans();
    }

    private function setupMidtrans(): void
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$clientKey = config('midtrans.client_key');
        Config::$isProduction = (bool) config('midtrans.is_production');
        Config::$isSanitized = (bool) config('midtrans.is_sanitized');
        Config::$is3ds = (bool) config('midtrans.is_3ds');
    }

    /**
     * Buat transaksi Snap untuk satu booking. Pembayaran::create() sengaja
     * TIDAK dipanggil di sini (beda dari integrasi Core API sebelumnya)
     * karena metode pembayaran yang sebenarnya dipakai baru diketahui
     * setelah customer memilihnya sendiri di halaman Midtrans, baris
     * pembayaran baru dibuat oleh PembayaranController::webhook().
     *
     * @return array{redirect_url: ?string}
     */
    public function createTransaction(Booking $booking, string $finishRedirectUrl): array
    {
        $response = $this->createSnapTransaction($this->buildParams($booking, $finishRedirectUrl));

        return [
            'redirect_url' => $response['redirect_url'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildParams(Booking $booking, string $finishRedirectUrl): array
    {
        $customer = $booking->customer;
        $lapangan = $booking->slot->lapangan;

        return [
            'transaction_details' => [
                'order_id' => $booking->kode_booking,
                'gross_amount' => $booking->total_bayar,
            ],
            'item_details' => [[
                'id' => (string) $lapangan->id,
                'price' => $booking->total_bayar,
                'quantity' => 1,
                'name' => 'Booking '.$lapangan->nama,
            ]],
            'customer_details' => [
                'first_name' => $customer->nama,
                'email' => $customer->email ?: 'customer@greendeahan.com',
                'phone' => $customer->no_telepon,
            ],
            'callbacks' => [
                'finish' => $finishRedirectUrl,
            ],
        ];
    }

    /**
     * Panggilan sesungguhnya ke Midtrans, sengaja dipisah dari
     * createTransaction() supaya bisa di-partial-mock di test. SDK ini
     * pakai curl mentah (bukan Laravel Http client), jadi Http::fake()
     * tidak akan bisa mencegat panggilan ke Snap::createTransaction().
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    protected function createSnapTransaction(array $params): array
    {
        $response = Snap::createTransaction($params);

        return json_decode(json_encode($response), true) ?? [];
    }
}
