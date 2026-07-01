<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantFitur;
use App\Models\TenantInvitation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tenant:activate {subdomain} {--paket=} {--perpanjang=365}')]
#[Description('Aktifkan tenant (dan perpanjang masa aktifnya), opsional ganti paket')]
class AktifkanTenant extends Command
{
    private const PAKET_VALID = ['basic', 'pro', 'premium'];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $subdomain = $this->argument('subdomain');
        $paket = $this->option('paket');
        $hariPerpanjang = (int) $this->option('perpanjang');

        if ($paket !== null && ! in_array($paket, self::PAKET_VALID, true)) {
            $this->error('Paket "'.$paket.'" tidak dikenal. Pilih salah satu: '.implode(', ', self::PAKET_VALID).'.');

            return self::FAILURE;
        }

        $tenant = Tenant::where('domain', $subdomain.'.greendeahan.com')->first();

        if (! $tenant) {
            $this->error('Tenant tidak ditemukan, buat dulu via panel superadmin.');

            return self::FAILURE;
        }

        if ($paket !== null && $paket !== $tenant->paket) {
            $tenant->update(['paket' => $paket]);

            TenantFitur::updateOrCreate(
                ['tenant_id' => $tenant->id],
                TenantFitur::presetUntukPaket($paket),
            );
        }

        // Perpanjang dari tanggal berakhir yang lama kalau masih aktif
        // (menambah sisa waktu), atau dari hari ini kalau sudah lewat
        // (supaya klien tidak dirugikan dihitung dari tanggal kadaluarsa).
        $dasarPerpanjangan = $tenant->tanggal_berakhir && $tenant->tanggal_berakhir->isFuture()
            ? $tenant->tanggal_berakhir->copy()
            : today();

        $tenant->update([
            'status_aktif' => true,
            'tanggal_berakhir' => $dasarPerpanjangan->addDays($hariPerpanjang),
        ]);

        $tenant->refresh();

        $this->info("Tenant \"{$tenant->nama_bisnis}\" ({$tenant->domain}) diaktifkan.");
        $this->line('Paket: '.$tenant->paket);
        $this->line('Aktif sampai: '.$tenant->tanggal_berakhir->format('d/m/Y'));

        if (! $tenant->email_admin) {
            $this->warn('Tenant ini belum punya email PIC, invitation tidak dibuat.');

            return self::SUCCESS;
        }

        $invitation = TenantInvitation::buatUntuk($tenant, $tenant->email_admin);

        $this->line('Link undangan owner (berlaku 7 hari): '.$invitation->link());

        return self::SUCCESS;
    }
}
