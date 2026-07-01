<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Setup awal untuk bikin satu-satunya akun superadmin platform. Superadmin
 * sengaja independen dari tenant manapun (tidak buat Tenant, tidak buat
 * baris staf), beda dengan owner tenant biasa yang dibuat lewat
 * tenant:invite. Hanya boleh ada satu superadmin, lihat guard di handle().
 */
#[Signature('superadmin:create')]
#[Description('Buat akun superadmin platform (setup awal, hanya sekali)')]
class CreateSuperadminAccount extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (User::where('is_superadmin', true)->exists()) {
            $this->error('Superadmin sudah ada. Hanya boleh ada satu superadmin.');

            return self::FAILURE;
        }

        $nama = $this->ask('Nama lengkap');
        $email = $this->ask('Email');
        $password = $this->secret('Password');

        $validator = Validator::make(
            ['nama' => $nama, 'email' => $email, 'password' => $password],
            [
                'nama' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:8'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $pesan) {
                $this->error($pesan);
            }

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error('Email ini sudah dipakai user lain. Gunakan email lain.');

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $nama,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        // email_verified_at & is_superadmin bukan kolom fillable, jadi tidak
        // bisa ikut di User::create(). Set langsung lewat properti lalu
        // save() supaya tidak kena mass-assignment guard.
        $user->email_verified_at = now();
        $user->is_superadmin = true;
        $user->save();

        $this->info('Superadmin berhasil dibuat. Login di: greendeahan.com/superadmin');

        return self::SUCCESS;
    }
}
