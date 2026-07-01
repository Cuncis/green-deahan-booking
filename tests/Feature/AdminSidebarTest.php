<?php

namespace Tests\Feature;

use App\Models\Staf;
use App\Models\Tenant;
use App\Models\TenantFitur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    private function kunjungiDashboard()
    {
        $user = $this->staf($this->tenant());

        return $this->actingAs($user)->get('/admin');
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
        $response->assertSee('Dashboard');
        $response->assertSee('Semua Booking');
        $response->assertSee('Lapangan Saya');
        $response->assertSee('Jadwal');
        $response->assertSee('Pengaturan');
        $response->assertDontSee('Laporan Pendapatan');
        $response->assertDontSee('Staf & Operator', false);
        $response->assertDontSee('Member & Loyalti', false);
        $response->assertDontSee('Reminder Otomatis');
        $response->assertDontSee('Analitik Antar Cabang');
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
        $response->assertSee('Laporan Pendapatan');
        $response->assertDontSee('Staf & Operator', false);
        $response->assertDontSee('Member & Loyalti', false);
        $response->assertDontSee('Reminder Otomatis');
        $response->assertDontSee('Analitik Antar Cabang');
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
        $response->assertSee('Staf & Operator', false);
        $response->assertSee('Member & Loyalti', false);
        $response->assertSee('Reminder Otomatis');
        $response->assertSee('Analitik Antar Cabang');
    }

    public function test_badge_paket_basic_pakai_warna_cream_deep(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('basic'),
        ));
        $tenant->update(['paket' => 'basic']);

        $response = $this->kunjungiDashboard();

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

        $response = $this->kunjungiDashboard();

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

        $response = $this->kunjungiDashboard();

        $response->assertOk();
        $response->assertSee('bg-plum text-white', false);
    }

    public function test_halaman_placeholder_booking_lapangan_jadwal_pengaturan_bisa_diakses(): void
    {
        $user = $this->staf($this->tenant());

        $this->actingAs($user)->get('/admin/booking')->assertOk()->assertSee('Semua Booking');
        $this->actingAs($user)->get('/admin/lapangan')->assertOk()->assertSee('Lapangan Saya');
        $this->actingAs($user)->get('/admin/jadwal')->assertOk()->assertSee('Jadwal');
        $this->actingAs($user)->get('/admin/pengaturan')->assertOk()->assertSee('Pengaturan');
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
