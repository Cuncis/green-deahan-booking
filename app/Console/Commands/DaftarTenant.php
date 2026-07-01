<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tenant:list')]
#[Description('Tampilkan tabel semua tenant yang terdaftar di platform')]
class DaftarTenant extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenants = Tenant::withCount('staf')->latest()->get();

        if ($tenants->isEmpty()) {
            $this->line('Belum ada tenant terdaftar.');

            return self::SUCCESS;
        }

        $this->table(
            ['Domain', 'Paket', 'Status', 'Tanggal Berakhir', 'Jumlah Staf'],
            $tenants->map(fn (Tenant $tenant) => [
                $tenant->domain,
                $tenant->paket,
                $tenant->status_aktif ? 'Aktif' : 'Nonaktif',
                $tenant->tanggal_berakhir?->format('d/m/Y') ?? '-',
                $tenant->staf_count,
            ]),
        );

        return self::SUCCESS;
    }
}
