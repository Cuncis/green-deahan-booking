<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Http;

/**
 * Integrasi Mayar (invoice/create), customer diarahkan ke halaman
 * pembayaran hosted Mayar (data.link) dan bebas pilih metode apapun yang
 * aktif di akun merchant (QRIS/VA/e-wallet/dst), bukan metode yang sudah
 * ditentukan lebih dulu dari sisi kita.
 */
class PaymentService
{
    /**
     * Buat invoice Mayar untuk satu booking. Pembayaran::create() sengaja
     * TIDAK dipanggil di sini karena metode pembayaran yang sebenarnya
     * dipakai baru diketahui setelah customer memilihnya sendiri di halaman
     * Mayar, baris pembayaran baru dibuat oleh PembayaranController::webhook().
     *
     * @return array{redirect_url: ?string}
     */
    public function createTransaction(Booking $booking, string $finishRedirectUrl): array
    {
        $response = $this->createInvoice($this->buildParams($booking, $finishRedirectUrl));

        return [
            'redirect_url' => $response['data']['link'] ?? null,
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
            'name' => $customer->nama,
            'email' => $customer->email ?: 'customer@greendeahan.com',
            'mobile' => $customer->no_telepon,
            'redirectUrl' => $finishRedirectUrl,
            'description' => 'Booking '.$lapangan->nama,
            'expiredAt' => now()->addHours(2)->toIso8601String(),
            'items' => [[
                'quantity' => 1,
                'rate' => $booking->total_bayar,
                'description' => 'Booking '.$lapangan->nama,
            ]],
            // noCustomer dipakai PembayaranController::webhook() untuk
            // mencocokkan notifikasi Mayar kembali ke booking ini (Mayar
            // meng-echo extraData apa adanya di invoice/detail, lihat
            // https://docs.mayar.id/api-reference/invoice/create).
            'extraData' => [
                'noCustomer' => $booking->kode_booking,
            ],
        ];
    }

    /**
     * Panggilan sesungguhnya ke Mayar, sengaja dipisah dari
     * createTransaction() supaya bisa di-partial-mock di test tanpa
     * panggilan network sungguhan ke sandbox Mayar.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    protected function createInvoice(array $params): array
    {
        $response = Http::baseUrl($this->baseUrl())
            ->withToken((string) config('services.mayar.api_key'))
            ->acceptJson()
            ->post('/hl/v1/invoice/create', $params)
            ->throw();

        return $response->json() ?? [];
    }

    /**
     * Sandbox Mayar ada di domain terpisah (mayar.club), bukan
     * subdomain/path dari domain production (mayar.id).
     */
    private function baseUrl(): string
    {
        return config('services.mayar.is_production')
            ? 'https://api.mayar.id'
            : 'https://api.mayar.club';
    }
}
