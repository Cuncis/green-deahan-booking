<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Lapangan;
use App\Models\Staf;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

/**
 * Livewire sudah dihapus dari project. Pastikan tidak ada halaman yang
 * masih mencoba memuat asset-nya.
 */
class LivewireAssetsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Vite seolah dev server berjalan, supaya tag script berisi nama entry
     * (resources/js/...) di mesin manapun, ada atau tidak ada build.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $hotFile = storage_path('framework/testing/vite.hot');
        if (! is_dir(dirname($hotFile))) {
            mkdir(dirname($hotFile), 0755, true);
        }
        file_put_contents($hotFile, 'http://vite.test');
        Vite::useHotFile($hotFile);
    }

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

    public function test_halaman_booking_tidak_memuat_livewire(): void
    {
        $tenant = $this->tenant();
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id, 'status_aktif' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee('resources/js/booking/app.tsx', false)
            ->assertDontSee('livewire', false)
            ->assertDontSee('wire:', false);
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
