<?php

namespace Tests\Feature;

use App\Models\Staf;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperadminTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->is_superadmin = true;
        $user->save();

        return $user;
    }

    public function test_superadmin_bisa_akses_dashboard_lewat_domain_yang_bukan_domain_tenant_manapun(): void
    {
        $superadmin = $this->superadmin();

        // Sengaja pakai host yang tidak match tenant manapun, untuk
        // membuktikan /superadmin memang independen dari IdentifikasiTenant.
        $response = $this->actingAs($superadmin)->get('http://platform-owner.test/superadmin');

        $response->assertOk();
        $response->assertSee('Semua Tenant');
    }

    public function test_superadmin_melihat_semua_tenant_lintas_paket(): void
    {
        $superadmin = $this->superadmin();
        Tenant::factory()->create(['domain' => 'tenant-a.test', 'nama_bisnis' => 'Tenant A']);
        Tenant::factory()->create(['domain' => 'tenant-b.test', 'nama_bisnis' => 'Tenant B']);

        $response = $this->actingAs($superadmin)->get('http://platform-owner.test/superadmin');

        $response->assertOk();
        $response->assertSee('Tenant A');
        $response->assertSee('Tenant B');
    }

    public function test_user_biasa_tidak_bisa_akses_superadmin(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('http://platform-owner.test/superadmin');

        $response->assertForbidden();
    }

    public function test_staf_tenant_tidak_otomatis_punya_akses_superadmin(): void
    {
        $tenant = Tenant::where('domain', 'localhost')->firstOrFail();
        $staf = User::factory()->create();
        Staf::create(['tenant_id' => $tenant->id, 'user_id' => $staf->id, 'role' => 'owner', 'status_aktif' => true]);

        $response = $this->actingAs($staf)->get('http://platform-owner.test/superadmin');

        $response->assertForbidden();
    }

    public function test_superadmin_tidak_otomatis_punya_akses_admin_tenant(): void
    {
        $superadmin = $this->superadmin();

        $response = $this->actingAs($superadmin)->get('/admin');

        $response->assertForbidden();
    }

    public function test_guest_diarahkan_ke_login(): void
    {
        $response = $this->get('http://platform-owner.test/superadmin');

        $response->assertRedirect('/login');
    }
}
