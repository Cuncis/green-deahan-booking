<?php

namespace Tests\Feature;

use App\Livewire\StafManager;
use App\Models\Staf;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StafManagerTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(): Tenant
    {
        $tenant = Tenant::where('domain', 'localhost')->firstOrFail();
        app()->instance('tenant', $tenant);

        return $tenant;
    }

    public function test_daftar_staf_hanya_milik_tenant_sendiri(): void
    {
        $tenant = $this->tenant();
        $tenantLain = Tenant::factory()->create(['domain' => 'lain.test']);

        $userSendiri = User::factory()->create(['name' => 'Budi Staf']);
        $userLain = User::factory()->create(['name' => 'Orang Lain']);

        Staf::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $userSendiri->id]);
        Staf::factory()->create(['tenant_id' => $tenantLain->id, 'user_id' => $userLain->id]);

        Livewire::test(StafManager::class)
            ->assertSee('Budi Staf')
            ->assertDontSee('Orang Lain');
    }

    public function test_tambah_staf_berhasil_untuk_user_terdaftar(): void
    {
        $tenant = $this->tenant();
        $user = User::factory()->create(['email' => 'calon-staf@example.com']);

        Livewire::test(StafManager::class)
            ->call('bukaForm')
            ->set('email', 'calon-staf@example.com')
            ->set('role', 'manager')
            ->call('tambah')
            ->assertSet('tampilkanForm', false);

        $this->assertDatabaseHas('staf', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'manager',
        ]);
    }

    public function test_tambah_staf_gagal_kalau_email_belum_terdaftar(): void
    {
        $this->tenant();

        Livewire::test(StafManager::class)
            ->call('bukaForm')
            ->set('email', 'belum-ada@example.com')
            ->set('role', 'staff')
            ->call('tambah')
            ->assertSet('pesanError', 'Belum ada akun terdaftar dengan email ini, minta orangnya daftar dulu.');

        $this->assertDatabaseCount('staf', 0);
    }

    public function test_ubah_role_staf_berhasil(): void
    {
        $tenant = $this->tenant();
        $user = User::factory()->create();
        $staf = Staf::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'role' => 'staff']);

        Livewire::test(StafManager::class)
            ->call('ubahRole', $staf->id, 'owner');

        $this->assertDatabaseHas('staf', [
            'id' => $staf->id,
            'role' => 'owner',
        ]);
    }
}
