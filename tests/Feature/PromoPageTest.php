<?php

namespace Tests\Feature;

use App\Models\Staf;
use App\Models\Tenant;
use App\Models\TenantFitur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromoPageTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(): Tenant
    {
        return Tenant::where('domain', 'localhost')->firstOrFail();
    }

    private function staf(Tenant $tenant): User
    {
        $user = User::factory()->create();

        Staf::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'status_aktif' => true,
        ]);

        return $user;
    }

    public function test_halaman_promo_404_kalau_fitur_kode_promo_tidak_aktif(): void
    {
        $tenant = $this->tenant();
        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('basic'),
        ));
        $user = $this->staf($tenant);

        $response = $this->actingAs($user)->get(route('admin.promo'));

        $response->assertNotFound();
    }

    public function test_halaman_promo_bisa_diakses_tenant_pro(): void
    {
        $tenant = $this->tenant();
        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('pro'),
        ));
        $user = $this->staf($tenant);

        $response = $this->actingAs($user)->get(route('admin.promo'));

        $response->assertOk();
        $response->assertSee('Kode Promo');
        $response->assertSee('Tambah Kode Baru');
    }
}
