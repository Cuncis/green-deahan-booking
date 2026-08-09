<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Services\TenantActivationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tenant:activate {subdomain} {--paket=} {--perpanjang=365}')]
#[Description('Aktifkan tenant (dan perpanjang masa aktifnya), opsional ganti paket')]
class AktifkanTenant extends Command
{
    private const PAKET_VALID = ['basic', 'pro', 'premium'];

    public function __construct(private readonly TenantActivationService $tenantActivationService)
    {
        parent::__construct();
    }

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

        $tenant = $this->tenantActivationService->aktifkan($tenant, $paket, $hariPerpanjang);

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
