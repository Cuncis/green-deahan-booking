<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Lapangan;
use App\Models\Staf;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Livewire hanya dipakai di halaman booking customer. Asset-nya tidak
 * di-inject otomatis (config/livewire.php), jadi halaman admin React dan
 * halaman lain tidak ikut memuat livewire.js.
 */
class LivewireAssetsTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(): Tenant
    {
        return Tenant::where('domain', 'localhost')->firstOrFail();
    }

    private function owner(Tenant $tenant): User
    {
        $user = User::factory()->create();
        Staf::create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'role' => 'owner', 'status_aktif' => true]);

        return $user;
    }

    public function test_halaman_booking_tetap_memuat_script_livewire(): void
    {
        $tenant = $this->tenant();
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id, 'status_aktif' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee('livewire.js', false)
            ->assertSeeLivewire('kalender-booking');
    }

    public function test_halaman_admin_react_tidak_memuat_livewire(): void
    {
        $user = $this->owner($this->tenant());

        $this->actingAs($user)->get('/admin')
            ->assertOk()
            ->assertDontSee('livewire.js', false)
            ->assertDontSee('Livewire Scripts', false);
    }

    public function test_halaman_superadmin_react_tidak_memuat_livewire(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->is_superadmin = true;
        $superadmin->save();

        $this->actingAs($superadmin)->get(route('superadmin.tenants'))
            ->assertOk()
            ->assertDontSee('livewire.js', false);
    }

    public function test_halaman_profil_memuat_alpine_dari_app_js_tanpa_livewire(): void
    {
        $user = $this->owner($this->tenant());

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('resources/js/app.js', false)
            ->assertDontSee('livewire.js', false);
    }
}
