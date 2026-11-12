<?php

namespace App\Services;

use App\Exceptions\MayarException;
use App\Models\Order;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Pembungkus API Mayar v2 "Single Payment Request" untuk toko online.
 *
 * Dokumentasi: https://docs.mayar.id/api-reference-v2/reqpayment/create.md
 * dan https://docs.mayar.id/api-reference-v2/reqpayment/detail.md
 *
 * Pengaman sandbox: selama MAYAR_ENV bukan "production", service menolak
 * memanggil server produksi (api.mayar.id), jadi tidak ada uang asli yang
 * bisa diproses dari lingkungan uji coba.
 */
class MayarService
{
    private const HOST_PRODUKSI = 'api.mayar.id';

    /**
     * Buat payment request untuk pesanan.
     *
     * @return array{id: string, link: string}
     */
    public function createPayment(Order $order): array
    {
        $order->loadMissing('items');

        $respons = $this->kirim(fn (PendingRequest $http) => $http->post('/hl/v2/payments/create', [
            'name' => "Pesanan {$order->code}",
            'amount' => $order->total,
            'email' => $order->email,
            'mobile' => $order->phone,
            'description' => $order->items->map(fn ($item) => "{$item->qty}x {$item->product_name}")->implode(', ').', ongkir',
            // Domain yang sedang dipakai pelanggan (greendeahan.com, www, atau lokal).
            'redirectUrl' => url('/pesanan/'.$order->code),
            'expiredAt' => now()->addHours((int) config('toko.jam_kedaluwarsa'))->toIso8601String(),
            'extraData' => ['tipe' => 'toko', 'kode' => $order->code],
        ]));

        $id = $respons->json('data.id');
        $link = $respons->json('data.link');

        if (! is_string($id) || $id === '' || ! is_string($link) || $link === '') {
            throw new MayarException('Respons Mayar tidak berisi id atau link pembayaran.');
        }

        return ['id' => $id, 'link' => $link];
    }

    /**
     * Detail payment request, termasuk status (unpaid, paid, closed) dan amount.
     *
     * @return array<string, mixed>
     */
    public function getPayment(string $id): array
    {
        $respons = $this->kirim(fn (PendingRequest $http) => $http->get('/hl/v2/payments/'.rawurlencode($id)));
        $data = $respons->json('data');

        if (! is_array($data)) {
            throw new MayarException('Respons detail pembayaran Mayar tidak berisi data.');
        }

        return $data;
    }

    public function modeSandbox(): bool
    {
        return config('mayar.env') !== 'production';
    }

    /**
     * @param  callable(PendingRequest): Response  $panggil
     */
    private function kirim(callable $panggil): Response
    {
        $kunci = (string) config('mayar.api_key');
        $baseUrl = (string) config('mayar.base_url');

        if ($kunci === '') {
            throw new MayarException('MAYAR_API_KEY belum diisi di .env.');
        }

        if ($this->modeSandbox() && parse_url($baseUrl, PHP_URL_HOST) === self::HOST_PRODUKSI) {
            throw new MayarException('MAYAR_ENV masih sandbox tapi MAYAR_BASE_URL mengarah ke server produksi. Dibatalkan supaya tidak ada uang asli yang diproses.');
        }

        try {
            $respons = $panggil(
                Http::baseUrl($baseUrl)
                    ->withToken($kunci)
                    ->acceptJson()
                    ->asJson()
                    ->timeout((int) config('mayar.timeout'))
                    ->retry(2, 300, fn ($galat) => $galat instanceof ConnectionException, throw: false)
            );
        } catch (ConnectionException) {
            throw new MayarException('Tidak bisa terhubung ke Mayar, coba lagi sebentar lagi.');
        }

        if ($respons->failed()) {
            // Pesan dari Mayar boleh dicatat; API key ada di header, bukan di body.
            $pesan = $respons->json('messages') ?? $respons->json('message') ?? 'tanpa pesan';
            throw new MayarException("Mayar menolak permintaan (HTTP {$respons->status()}): ".(is_string($pesan) ? $pesan : json_encode($pesan)));
        }

        return $respons;
    }
}
