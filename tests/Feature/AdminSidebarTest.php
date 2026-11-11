<?php

namespace Tests\Feature;

use App\Models\Staf;
use App\Models\Tenant;
use App\Models\TenantFitur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminSidebarTest extends TestCase
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

    private function kunjungiDashboard(): TestResponse
    {
        $user = $this->staf($this->tenant());

        return $this->actingAs($user)->get('/admin');
    }

    /**
     * Halaman Profil masih Blade (sidebar <x-admin-sidebar>), dipakai untuk
     * cek warna badge paket di sidebar Blade.
     */
    private function kunjungiHalamanBlade(): TestResponse
    {
        $user = $this->staf($this->tenant());

        return $this->actingAs($user)->get('/profile');
    }

    /**
     * Label semua menu di sidebar React (prop "navigasi" dari NavigasiAdminService).
     *
     * @return array<int, string>
     */
    private function labelMenu(TestResponse $response): array
    {
        return collect($response->viewData('page')['props']['navigasi'])
            ->flatMap(fn (array $grup) => array_column($grup['item'], 'label'))
            ->all();
    }

    /**
     * @param  array<int, string>  $ada
     * @param  array<int, string>  $tidakAda
     */
    private function assertMenu(TestResponse $response, array $ada, array $tidakAda): void
    {
        $label = $this->labelMenu($response);

        foreach ($ada as $menu) {
            $this->assertContains($menu, $label);
        }

        foreach ($tidakAda as $menu) {
            $this->assertNotContains($menu, $label);
        }
    }

    public function test_sidebar_basic_hanya_menampilkan_menu_inti(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('basic'),
        ));

        $response = $this->kunjungiDashboard();

        $response->assertOk();
        $this->assertMenu(
            $response,
            ['Dashboard', 'Semua Booking', 'Lapangan Saya', 'Jadwal', 'Pengaturan'],
            ['Laporan Pendapatan', 'Staf & Operator', 'Member & Loyalti', 'Reminder Otomatis', 'Analitik Antar Cabang'],
        );
    }

    public function test_sidebar_pro_menampilkan_laporan_pendapatan_tanpa_menu_premium(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('pro'),
        ));

        $response = $this->kunjungiDashboard();

        $response->assertOk();
        $this->assertMenu(
            $response,
            ['Laporan Pendapatan'],
            ['Staf & Operator', 'Member & Loyalti', 'Reminder Otomatis', 'Analitik Antar Cabang'],
        );
    }

    public function test_sidebar_premium_menampilkan_semua_menu_tambahan(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('premium'),
        ));

        $response = $this->kunjungiDashboard();

        $response->assertOk();
        $this->assertMenu($response, ['Staf & Operator', 'Member & Loyalti', 'Reminder Otomatis', 'Analitik Antar Cabang'], []);
    }

    public function test_badge_paket_basic_pakai_warna_cream_deep(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('basic'),
        ));
        $tenant->update(['paket' => 'basic']);

        $response = $this->kunjungiHalamanBlade();

        $response->assertOk();
        $response->assertSee('bg-cream-deep text-ink-mid', false);
    }

    public function test_badge_paket_pro_pakai_warna_gold(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('pro'),
        ));
        $tenant->update(['paket' => 'pro']);

        $response = $this->kunjungiHalamanBlade();

        $response->assertOk();
        $response->assertSee('bg-gold text-white', false);
    }

    public function test_badge_paket_premium_pakai_warna_plum(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('premium'),
        ));
        $tenant->update(['paket' => 'premium']);

        $response = $this->kunjungiHalamanBlade();

        $response->assertOk();
        $response->assertSee('bg-plum text-white', false);
    }

    public function test_halaman_booking_lapangan_jadwal_pengaturan_bisa_diakses(): void
    {
        $user = $this->staf($this->tenant());

        $halaman = [
            '/admin/booking' => 'admin/Booking',
            '/admin/lapangan' => 'admin/Lapangan/Index',
            '/admin/lapangan/tambah' => 'admin/Lapangan/Form',
            '/admin/jadwal' => 'admin/Jadwal',
            '/admin/pengaturan' => 'admin/Pengaturan',
        ];

        foreach ($halaman as $url => $komponen) {
            $this->actingAs($user)->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component($komponen)
                ->where('area', 'admin')
                ->has('navigasi')
            );
        }
    }

    public function test_paket_tenant_dibagikan_ke_sidebar_react(): void
    {
        $tenant = $this->tenant();
        $tenant->update(['paket' => 'premium']);

        $this->kunjungiDashboard()->assertInertia(fn (Assert $page) => $page->where('tenant.paket', 'premium'));
    }

    public function test_user_yang_bukan_staf_tenant_ini_ditolak_403(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertForbidden();
    }

    public function test_staf_tenant_lain_tidak_bisa_akses_dashboard_tenant_ini(): void
    {
        $tenantLain = Tenant::factory()->create(['domain' => 'tenant-lain.test']);
        $user = $this->staf($tenantLain);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertForbidden();
    }
}
