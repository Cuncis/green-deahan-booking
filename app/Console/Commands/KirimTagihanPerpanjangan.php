<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantTagihanPerpanjangan;
use App\Services\PaymentService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Jalan tiap hari (lihat routes/console.php), cari tenant aktif yang
 * tanggal_berakhir-nya 0-14 hari lagi dan belum punya tagihan perpanjangan
 * yang masih menunggu, lalu buatkan invoice Mayar dan email-kan ke owner-nya.
 * Pengaman rentang tanggal (bukan cocok persis 14 hari) supaya kalau
 * scheduler sempat tidak jalan sehari (deploy, downtime), tenant yang
 * kelewat tetap tertagih di run berikutnya, bukan hilang begitu saja.
 */
#[Signature('tenant:kirim-tagihan-perpanjangan')]
#[Description('Kirim tagihan perpanjangan ke tenant yang masa aktifnya akan habis dalam 14 hari')]
class KirimTagihanPerpanjangan extends Command
{
    private const HARI_SEBELUM_JATUH_TEMPO = 14;

    public function handle(PaymentService $paymentService): int
    {
        $tenants = Tenant::where('status_aktif', true)
            ->whereNotNull('tanggal_berakhir')
            ->where('tanggal_berakhir', '>=', today())
            ->where('tanggal_berakhir', '<=', today()->addDays(self::HARI_SEBELUM_JATUH_TEMPO))
            ->whereDoesntHave('tagihanPerpanjangan', fn ($q) => $q->where('status', 'menunggu'))
            ->get();

        $terkirim = 0;

        foreach ($tenants as $tenant) {
            if ($this->kirimTagihan($tenant, $paymentService)) {
                $terkirim++;
            }
        }

        $this->info("Tagihan perpanjangan terkirim ke {$terkirim} dari {$tenants->count()} tenant yang jatuh tempo.");

        return self::SUCCESS;
    }

    private function kirimTagihan(Tenant $tenant, PaymentService $paymentService): bool
    {
        $kode = TenantTagihanPerpanjangan::generateKode();
        $jumlah = Tenant::hitungHargaPerpanjangan($tenant->paket);
        $finishRedirectUrl = 'https://'.($tenant->custom_domain ?: $tenant->domain).'/admin';

        try {
            $response = $paymentService->createPerpanjanganTransaction($tenant, $kode, $jumlah, $finishRedirectUrl);
        } catch (\Throwable $e) {
            report($e);

            return false;
        }

        if (! $response['redirect_url']) {
            return false;
        }

        $tagihan = TenantTagihanPerpanjangan::create([
            'tenant_id' => $tenant->id,
            'kode' => $kode,
            'jumlah' => $jumlah,
            'link_pembayaran' => $response['redirect_url'],
            'dikirim_pada' => now(),
        ]);

        $tagihan->kirimTagihan();

        return true;
    }
}
