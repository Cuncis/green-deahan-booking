<?php

namespace Tests\Feature\Auth;

use App\Models\Staf;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(): Tenant
    {
        return Tenant::where('domain', 'localhost')->firstOrFail();
    }

    public function test_halaman_undangan_valid_menampilkan_email_dan_role(): void
    {
        $tenant = $this->tenant();
        $invitation = TenantInvitation::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'calon.staf@example.com',
            'role' => 'manager',
        ]);

        $response = $this->get('/invite/'.$invitation->token);

        $response->assertOk();
        $response->assertSee('calon.staf@example.com');
        $response->assertSee('Manager');
    }

    public function test_halaman_undangan_yang_sudah_dipakai_404(): void
    {
        $invitation = TenantInvitation::factory()->sudahDipakai()->create(['tenant_id' => $this->tenant()->id]);

        $response = $this->get('/invite/'.$invitation->token);

        $response->assertNotFound();
    }

    public function test_halaman_undangan_yang_kadaluarsa_404(): void
    {
        $invitation = TenantInvitation::factory()->kadaluarsa()->create(['tenant_id' => $this->tenant()->id]);

        $response = $this->get('/invite/'.$invitation->token);

        $response->assertNotFound();
    }

    public function test_token_yang_tidak_ada_404(): void
    {
        $response = $this->get('/invite/token-ngasal-tidak-ada');

        $response->assertNotFound();
    }

    public function test_terima_undangan_membuat_user_baru_staf_dan_langsung_login(): void
    {
        $tenant = $this->tenant();
        $invitation = TenantInvitation::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'calon.staf@example.com',
            'role' => 'manager',
        ]);

        $response = $this->post('/invite/'.$invitation->token, [
            'name' => 'Calon Staf',
            'password' => 'password-aman',
            'password_confirmation' => 'password-aman',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'calon.staf@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->email_verified_at);

        $this->assertDatabaseHas('staf', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'manager',
        ]);

        $this->assertNotNull($invitation->fresh()->used_at);
    }

    public function test_undangan_yang_sudah_dipakai_tidak_bisa_dipakai_lagi(): void
    {
        $invitation = TenantInvitation::factory()->sudahDipakai()->create(['tenant_id' => $this->tenant()->id]);

        $response = $this->post('/invite/'.$invitation->token, [
            'name' => 'Calon Staf',
            'password' => 'password-aman',
            'password_confirmation' => 'password-aman',
        ]);

        $response->assertNotFound();
        $this->assertGuest();
    }

    public function test_terima_undangan_untuk_email_yang_sudah_punya_akun_menambahkan_staf_ke_akun_lama(): void
    {
        $tenant = $this->tenant();
        $userLama = User::factory()->create(['email' => 'sudah.ada@example.com']);

        $invitation = TenantInvitation::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'sudah.ada@example.com',
            'role' => 'staff',
        ]);

        $this->post('/invite/'.$invitation->token, [
            'name' => 'Nama Diabaikan',
            'password' => 'password-aman',
            'password_confirmation' => 'password-aman',
        ]);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('staf', [
            'tenant_id' => $tenant->id,
            'user_id' => $userLama->id,
            'role' => 'staff',
        ]);
    }

    public function test_pengguna_yang_sedang_login_tidak_bisa_akses_halaman_undangan(): void
    {
        $tenant = $this->tenant();
        $staf = User::factory()->create();
        Staf::create(['tenant_id' => $tenant->id, 'user_id' => $staf->id, 'role' => 'owner', 'status_aktif' => true]);

        $invitation = TenantInvitation::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($staf)->get('/invite/'.$invitation->token);

        $response->assertRedirect();
    }
}
