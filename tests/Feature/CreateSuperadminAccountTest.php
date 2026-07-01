<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSuperadminAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_membuat_superadmin_tanpa_tenant_dan_tanpa_staf(): void
    {
        // TestCase::setUp() sudah bikin 1 tenant "localhost" (dipakai
        // hampir semua test lain), jadi baseline-nya 1, bukan 0. Yang
        // penting dibuktikan di sini: jumlahnya TIDAK bertambah.
        $jumlahTenantSebelum = Tenant::count();

        $this->artisan('superadmin:create')
            ->expectsQuestion('Nama lengkap', 'Platform Owner')
            ->expectsQuestion('Email', 'owner@greendeahan.com')
            ->expectsQuestion('Password', 'password-aman')
            ->expectsOutputToContain('Superadmin berhasil dibuat. Login di: greendeahan.com/superadmin')
            ->assertExitCode(0);

        $user = User::where('email', 'owner@greendeahan.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->is_superadmin);
        $this->assertNotNull($user->email_verified_at);

        $this->assertSame($jumlahTenantSebelum, Tenant::count());
        $this->assertDatabaseCount('staf', 0);
    }

    public function test_ditolak_kalau_superadmin_sudah_ada(): void
    {
        $existing = User::factory()->create();
        $existing->is_superadmin = true;
        $existing->save();

        $this->artisan('superadmin:create')
            ->expectsOutputToContain('Superadmin sudah ada. Hanya boleh ada satu superadmin.')
            ->assertExitCode(1);

        $this->assertSame(1, User::where('is_superadmin', true)->count());
    }

    public function test_ditolak_kalau_email_sudah_dipakai_user_lain(): void
    {
        User::factory()->create(['email' => 'sudah.ada@example.com']);

        $this->artisan('superadmin:create')
            ->expectsQuestion('Nama lengkap', 'Calon Superadmin')
            ->expectsQuestion('Email', 'sudah.ada@example.com')
            ->expectsQuestion('Password', 'password-aman')
            ->expectsOutputToContain('Email ini sudah dipakai user lain')
            ->assertExitCode(1);

        $this->assertSame(0, User::where('is_superadmin', true)->count());
    }
}
