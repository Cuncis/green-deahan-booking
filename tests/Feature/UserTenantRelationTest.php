<?php

namespace Tests\Feature;

use App\Models\Staf;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTenantRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_superadmin_mengikuti_kolom_is_superadmin(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($user->isSuperadmin());

        $user->is_superadmin = true;
        $user->save();

        $this->assertTrue($user->fresh()->isSuperadmin());
    }

    public function test_tenants_mengembalikan_tenant_tempat_user_jadi_staf(): void
    {
        $tenantA = Tenant::factory()->create(['domain' => 'tenant-a.test']);
        $tenantB = Tenant::factory()->create(['domain' => 'tenant-b.test']);
        $tenantLain = Tenant::factory()->create(['domain' => 'tenant-lain.test']);

        $user = User::factory()->create();
        Staf::create(['tenant_id' => $tenantA->id, 'user_id' => $user->id, 'role' => 'owner', 'status_aktif' => true]);
        Staf::create(['tenant_id' => $tenantB->id, 'user_id' => $user->id, 'role' => 'manager', 'status_aktif' => true]);

        $tenants = $user->tenants;

        $this->assertCount(2, $tenants);
        $this->assertTrue($tenants->contains('id', $tenantA->id));
        $this->assertTrue($tenants->contains('id', $tenantB->id));
        $this->assertFalse($tenants->contains('id', $tenantLain->id));
    }

    public function test_superadmin_tidak_punya_relasi_tenants(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->is_superadmin = true;
        $superadmin->save();

        $this->assertCount(0, $superadmin->tenants);
    }
}
