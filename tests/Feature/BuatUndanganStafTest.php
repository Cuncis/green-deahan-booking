<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuatUndanganStafTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_membuat_invitation_untuk_tenant_yang_valid(): void
    {
        $tenant = Tenant::where('domain', 'localhost')->firstOrFail();

        $this->artisan('tenant:invite', [
            'domain' => 'localhost',
            'email' => 'admin@klien.com',
            'role' => 'manager',
        ])->assertSuccessful();

        $this->assertDatabaseHas('tenant_invitations', [
            'tenant_id' => $tenant->id,
            'email' => 'admin@klien.com',
            'role' => 'manager',
        ]);

        $invitation = TenantInvitation::where('email', 'admin@klien.com')->first();
        $this->assertNotNull($invitation->token);
        $this->assertTrue($invitation->expires_at->isFuture());
    }

    public function test_command_gagal_kalau_domain_tidak_ditemukan(): void
    {
        $this->artisan('tenant:invite', [
            'domain' => 'tidak-ada.localhost',
            'email' => 'admin@klien.com',
            'role' => 'manager',
        ])->assertFailed();

        $this->assertDatabaseCount('tenant_invitations', 0);
    }

    public function test_command_gagal_kalau_role_tidak_dikenal(): void
    {
        $this->artisan('tenant:invite', [
            'domain' => 'localhost',
            'email' => 'admin@klien.com',
            'role' => 'superadmin',
        ])->assertFailed();

        $this->assertDatabaseCount('tenant_invitations', 0);
    }
}
