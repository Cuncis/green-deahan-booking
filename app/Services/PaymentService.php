<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Pembayaran;
use InvalidArgumentException;
use Midtrans\Config;
use Midtrans\CoreApi;

/**
 * Integrasi Midtrans Core API (bukan Snap) supaya bisa langsung dapat
 * instruksi bayar (QR string, nomor VA, link redirect e-wallet) tanpa
 * customer diarahkan ke halaman pembayaran Midtrans.
 */
class PaymentService
{
    private const METODE_VALID = ['qris', 'va', 'ewallet'];

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
     * Buat transaksi di Midtrans untuk satu booking, simpan hasilnya ke
     * tabel pembayaran, lalu kembalikan instruksi yang siap ditampilkan
     * ke customer (beda bentuk instruksi tergantung metode-nya).
     *
     * @return array{metode: string, kode_transaksi_gateway: ?string, instruksi: array<string, mixed>}
     */
    public function createTransaction(Booking $booking, string $metode): array
    {
        if (! in_array($metode, self::METODE_VALID, true)) {
            throw new InvalidArgumentException("Metode pembayaran \"{$metode}\" tidak dikenal.");
        }

        $response = $this->chargeMidtrans($this->buildParams($booking, $metode));

        Pembayaran::create([
            'tenant_id' => $booking->tenant_id,
            'booking_id' => $booking->id,
            'metode' => $metode,
            'jumlah' => $booking->total_bayar,
            'status' => 'pending',
            'kode_transaksi_gateway' => $response['transaction_id'] ?? $booking->kode_booking,
            'raw_response_gateway' => $response,
        ]);

        return [
            'metode' => $metode,
            'kode_transaksi_gateway' => $response['transaction_id'] ?? null,
            'instruksi' => $this->ekstrakInstruksi($metode, $response),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildParams(Booking $booking, string $metode): array
    {
        $customer = $booking->customer;
        $lapangan = $booking->slot->lapangan;

        $params = [
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
        ];

        return array_merge($params, $this->paramsUntukMetode($metode));
    }

    /**
     * @return array<string, mixed>
     */
    private function paramsUntukMetode(string $metode): array
    {
        return match ($metode) {
            'qris' => ['payment_type' => 'qris'],
            'va' => [
                'payment_type' => 'bank_transfer',
                'bank_transfer' => ['bank' => 'bca'],
            ],
            'ewallet' => ['payment_type' => 'gopay'],
        };
    }

    /**
     * Panggilan sesungguhnya ke Midtrans, sengaja dipisah dari
     * createTransaction() supaya bisa di-partial-mock di test. SDK ini
     * pakai curl mentah (bukan Laravel Http client), jadi Http::fake()
     * tidak akan bisa mencegat panggilan ke CoreApi::charge().
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    protected function chargeMidtrans(array $params): array
    {
        $response = CoreApi::charge($params);

        return json_decode(json_encode($response), true) ?? [];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function ekstrakInstruksi(string $metode, array $response): array
    {
        return match ($metode) {
            'qris' => [
                'tipe' => 'qris',
                'qr_url' => $this->cariActionUrl($response, 'generate-qr-code'),
            ],
            'va' => [
                'tipe' => 'va',
                'bank' => $response['va_numbers'][0]['bank'] ?? null,
                'nomor_va' => $response['va_numbers'][0]['va_number'] ?? null,
            ],
            'ewallet' => [
                'tipe' => 'ewallet',
                'redirect_url' => $this->cariActionUrl($response, 'deeplink-redirect'),
                'qr_url' => $this->cariActionUrl($response, 'generate-qr-code'),
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function cariActionUrl(array $response, string $nama): ?string
    {
        foreach ($response['actions'] ?? [] as $action) {
            if (($action['name'] ?? null) === $nama) {
                return $action['url'] ?? null;
            }
        }

        return null;
    }
}
