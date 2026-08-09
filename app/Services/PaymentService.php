<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Tenant;
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
            // https://docs.mayar.id/api-reference/invoice/create). tipe
            // membedakan webhook ini dari invoice langganan tenant (lihat
            // createLanggananTransaction()) supaya PembayaranController bisa
            // merutekan keduanya ke handler yang benar.
            'extraData' => [
                'noCustomer' => $booking->kode_booking,
                'tipe' => 'booking',
            ],
        ];
    }

    /**
     * Buat invoice Mayar untuk langganan tahunan tenant (bukan booking
     * customer). Dipakai TenantRegistrationController::store() supaya
     * pendaftaran tenant baru langsung dibayar online dan aktif otomatis
     * lewat PembayaranController::prosesWebhookLangganan(), tanpa aktivasi
     * manual lewat `php artisan tenant:activate`.
     *
     * @return array{redirect_url: ?string}
     */
    public function createLanggananTransaction(Tenant $tenant, int $jumlah, string $finishRedirectUrl): array
    {
        $response = $this->createInvoice($this->buildParamsLangganan($tenant, $jumlah, $finishRedirectUrl));

        return [
            'redirect_url' => $response['data']['link'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildParamsLangganan(Tenant $tenant, int $jumlah, string $finishRedirectUrl): array
    {
        return [
            'name' => $tenant->nama_bisnis,
            'email' => $tenant->email_admin ?: 'admin@greendeahan.com',
            'mobile' => $tenant->whatsapp_admin,
            'redirectUrl' => $finishRedirectUrl,
            'description' => 'Langganan paket '.$tenant->paket.' Green Deahan Sport',
            'expiredAt' => now()->addHours(2)->toIso8601String(),
            'items' => [[
                'quantity' => 1,
                'rate' => $jumlah,
                'description' => 'Langganan paket '.$tenant->paket,
            ]],
            'extraData' => [
                'noCustomer' => $tenant->kode_pendaftaran,
                'tipe' => 'langganan_tenant',
            ],
        ];
    }

    /**
     * Buat invoice Mayar untuk perpanjangan tahunan tenant yang sudah
     * pernah aktif. Dipakai KirimTagihanPerpanjangan (dijalankan scheduler
     * 14 hari sebelum tanggal_berakhir), aktivasinya lewat
     * PembayaranController::prosesWebhookPerpanjangan(). $kode adalah
     * TenantTagihanPerpanjangan::kode (bukan Tenant::kode_pendaftaran),
     * karena satu tenant bisa punya banyak tagihan perpanjangan sepanjang
     * hidupnya, beda dari kode_pendaftaran yang sekali pakai.
     *
     * @return array{redirect_url: ?string}
     */
    public function createPerpanjanganTransaction(Tenant $tenant, string $kode, int $jumlah, string $finishRedirectUrl): array
    {
        $response = $this->createInvoice($this->buildParamsPerpanjangan($tenant, $kode, $jumlah, $finishRedirectUrl));

        return [
            'redirect_url' => $response['data']['link'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildParamsPerpanjangan(Tenant $tenant, string $kode, int $jumlah, string $finishRedirectUrl): array
    {
        return [
            'name' => $tenant->nama_bisnis,
            'email' => $tenant->email_admin ?: 'admin@greendeahan.com',
            'mobile' => $tenant->whatsapp_admin,
            'redirectUrl' => $finishRedirectUrl,
            'description' => 'Perpanjangan paket '.$tenant->paket.' Green Deahan Sport',
            // Berlaku jauh lebih lama dari invoice booking/langganan baru
            // (2 jam) supaya link yang sama masih bisa dipakai bayar selama
            // jendela pengingat (14 hari) sampai masa tenggang (7 hari)
            // berjalan, lihat NonaktifkanTenantKadaluarsa.
            'expiredAt' => now()->addDays(30)->toIso8601String(),
            'items' => [[
                'quantity' => 1,
                'rate' => $jumlah,
                'description' => 'Perpanjangan paket '.$tenant->paket,
            ]],
            'extraData' => [
                'noCustomer' => $kode,
                'tipe' => 'perpanjangan_tenant',
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
