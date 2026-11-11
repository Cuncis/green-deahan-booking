<?php

namespace Tests\Feature;

use App\Models\Staf;
use App\Models\Tenant;
use App\Models\TenantFitur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StafManagerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tenant "localhost" dengan paket Premium (fitur manajemen_staf), plus
     * owner yang sedang login.
     */
    private function tenant(string $paket = 'premium'): Tenant
    {
        $tenant = Tenant::where('domain', 'localhost')->firstOrFail();
        TenantFitur::create(array_merge(['tenant_id' => $tenant->id], TenantFitur::presetUntukPaket($paket)));

        $owner = User::factory()->create(['name' => 'Pemilik Lapangan']);
        Staf::create(['tenant_id' => $tenant->id, 'user_id' => $owner->id, 'role' => 'owner', 'status_aktif' => true]);
        $this->actingAs($owner);

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

        $this->get('/admin')->assertInertia(fn (Assert $page) => $page->where('staf', function ($staf) {
            $nama = collect($staf)->pluck('nama');

            return $nama->contains('Budi Staf') && ! $nama->contains('Orang Lain');
        }));
    }

    public function test_tambah_staf_berhasil_untuk_user_terdaftar(): void
    {
        $tenant = $this->tenant();
        $user = User::factory()->create(['email' => 'calon-staf@example.com']);

        $this->from('/admin')
            ->post(route('admin.staf.store'), ['email' => 'calon-staf@example.com', 'role' => 'manager'])
            ->assertRedirect('/admin')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('staf', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'manager',
        ]);
    }

    public function test_tambah_staf_gagal_kalau_email_belum_terdaftar(): void
    {
        $tenant = $this->tenant();

        $this->from('/admin')
            ->post(route('admin.staf.store'), ['email' => 'belum-ada@example.com', 'role' => 'staff'])
            ->assertSessionHasErrors(['email' => 'Belum ada akun terdaftar dengan email ini, minta orangnya daftar dulu.']);

        // Hanya owner yang login yang tercatat sebagai staf.
        $this->assertSame(1, Staf::where('tenant_id', $tenant->id)->count());
    }

    public function test_ubah_role_staf_berhasil(): void
    {
        $tenant = $this->tenant();
        $user = User::factory()->create();
        $staf = Staf::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'role' => 'staff']);

        $this->from('/admin')->put(route('admin.staf.role', $staf->id), ['role' => 'owner'])->assertRedirect('/admin');

        $this->assertDatabaseHas('staf', [
            'id' => $staf->id,
            'role' => 'owner',
        ]);
    }

    public function test_tambah_staf_ditolak_kalau_sudah_terdaftar(): void
    {
        $tenant = $this->tenant();
        $user = User::factory()->create(['email' => 'sudah@example.com']);
        Staf::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $user->id]);

        $this->from('/admin')
            ->post(route('admin.staf.store'), ['email' => 'sudah@example.com', 'role' => 'staff'])
            ->assertSessionHasErrors(['email' => 'Orang ini sudah terdaftar sebagai staf.']);
    }

    public function test_ubah_role_staf_tenant_lain_ditolak(): void
    {
        $this->tenant();
        $stafLain = Staf::factory()->create(['tenant_id' => Tenant::factory()->create()->id, 'role' => 'staff']);

        $this->put(route('admin.staf.role', $stafLain->id), ['role' => 'owner'])->assertNotFound();

        $this->assertSame('staff', $stafLain->fresh()->role);
    }

    public function test_kelola_staf_ditolak_untuk_paket_tanpa_manajemen_staf(): void
    {
        $this->tenant('pro');
        User::factory()->create(['email' => 'calon@example.com']);

        $this->post(route('admin.staf.store'), ['email' => 'calon@example.com', 'role' => 'staff'])->assertNotFound();
        $this->get('/admin')->assertInertia(fn (Assert $page) => $page->where('staf', null));
    }
}
