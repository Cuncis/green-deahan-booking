<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantInvitation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tenant:invite {domain} {email} {role}')]
#[Description('Buat link undangan supaya seseorang bisa daftar jadi staf tenant tertentu')]
class BuatUndanganStaf extends Command
{
    private const ROLE_VALID = ['owner', 'manager', 'staff'];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $domain = $this->argument('domain');
        $email = $this->argument('email');
        $role = $this->argument('role');

        if (! in_array($role, self::ROLE_VALID, true)) {
            $this->error('Role "'.$role.'" tidak dikenal. Pilih salah satu: '.implode(', ', self::ROLE_VALID).'.');

            return self::FAILURE;
        }

        $tenant = Tenant::where('domain', $domain)->first();

        if (! $tenant) {
            $this->error("Tenant dengan domain \"{$domain}\" tidak ditemukan.");

            return self::FAILURE;
        }

        $invitation = TenantInvitation::buatUntuk($tenant, $email, $role);

        $this->info('Undangan berhasil dibuat untuk '.$email.' ('.$role.') di '.$tenant->nama_bisnis.'.');
        $this->line('Link undangan (berlaku 7 hari): '.$invitation->link());
        $this->line('Kirim link ini ke orangnya secara manual, misalnya lewat WhatsApp.');

        return self::SUCCESS;
    }
}
