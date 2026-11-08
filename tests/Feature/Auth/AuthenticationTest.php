<?php

namespace Tests\Feature\Auth;

use App\Models\Staf;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_login_screen_has_password_visibility_toggle(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('show = !show', false);
        $response->assertSee('Show password');
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_staf_tenant_yang_login_di_domain_platform_diarahkan_ke_domain_bisnisnya(): void
    {
        $tenant = Tenant::factory()->create(['domain' => 'bisnis.test', 'status_aktif' => true]);
        $user = User::factory()->create();
        Staf::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'role' => 'owner']);

        $response = $this->post('http://platform.test/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('http://bisnis.test/login', session('errors')->first('email'));
    }

    public function test_user_tanpa_bisnis_aktif_ditolak_di_domain_platform(): void
    {
        $user = User::factory()->create();

        $this->post('http://platform.test/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_superadmin_tetap_bisa_login_di_domain_platform(): void
    {
        $user = User::factory()->create();
        $user->is_superadmin = true;
        $user->save();

        $this->post('http://platform.test/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('superadmin.dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_superadmin_diarahkan_ke_superadmin_dashboard_setelah_login(): void
    {
        $user = User::factory()->create();
        $user->is_superadmin = true;
        $user->save();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('superadmin.dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
