<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tenant:deactivate {subdomain}')]
#[Description('Nonaktifkan tenant, dengan konfirmasi sebelum eksekusi')]
class NonaktifkanTenant extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $subdomain = $this->argument('subdomain');

        $tenant = Tenant::where('domain', $subdomain.'.greendeahan.com')->first();

        if (! $tenant) {
            $this->error('Tenant tidak ditemukan, buat dulu via panel superadmin.');

            return self::FAILURE;
        }

        $this->line("Tenant: {$tenant->nama_bisnis} ({$tenant->domain}), paket {$tenant->paket}.");

        if (! $this->confirm('Yakin ingin menonaktifkan tenant ini? Halaman booking dan dashboard admin mereka akan berhenti bisa diakses.')) {
            $this->line('Dibatalkan, tidak ada perubahan.');

            return self::SUCCESS;
        }

        $tenant->update(['status_aktif' => false]);

        $this->info("Tenant \"{$tenant->nama_bisnis}\" berhasil dinonaktifkan.");

        return self::SUCCESS;
    }
}
