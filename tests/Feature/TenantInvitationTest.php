<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_undangan_baru_masih_valid(): void
    {
        $invitation = TenantInvitation::factory()->create();

        $this->assertTrue($invitation->masihValid());
        $this->assertFalse($invitation->sudahDipakai());
        $this->assertFalse($invitation->sudahKadaluarsa());
    }

    public function test_undangan_yang_sudah_dipakai_tidak_valid(): void
    {
        $invitation = TenantInvitation::factory()->sudahDipakai()->create();

        $this->assertFalse($invitation->masihValid());
        $this->assertTrue($invitation->sudahDipakai());
    }

    public function test_undangan_yang_kadaluarsa_tidak_valid(): void
    {
        $invitation = TenantInvitation::factory()->kadaluarsa()->create();

        $this->assertFalse($invitation->masihValid());
        $this->assertTrue($invitation->sudahKadaluarsa());
    }

    public function test_buat_untuk_membuat_undangan_role_owner_berlaku_7_hari_secara_default(): void
    {
        $tenant = Tenant::factory()->create();

        $invitation = TenantInvitation::buatUntuk($tenant, 'calon@owner.com');

        $this->assertSame($tenant->id, $invitation->tenant_id);
        $this->assertSame('calon@owner.com', $invitation->email);
        $this->assertSame('owner', $invitation->role);
        $this->assertTrue($invitation->masihValid());
        $this->assertEqualsWithDelta(now()->addDays(7)->timestamp, $invitation->expires_at->timestamp, 5);
    }

    public function test_buat_untuk_bisa_pakai_role_dan_masa_berlaku_custom(): void
    {
        $tenant = Tenant::factory()->create();

        $invitation = TenantInvitation::buatUntuk($tenant, 'calon@staf.com', 'manager', 14);

        $this->assertSame('manager', $invitation->role);
        $this->assertEqualsWithDelta(now()->addDays(14)->timestamp, $invitation->expires_at->timestamp, 5);
    }

    public function test_link_mengembalikan_url_invite_dengan_token(): void
    {
        $invitation = TenantInvitation::factory()->create();

        $this->assertSame(url('/invite/'.$invitation->token), $invitation->link());
    }
}
