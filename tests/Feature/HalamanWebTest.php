<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Lapangan;
use App\Models\Tenant;
use App\Models\TenantFitur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HalamanWebTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(): Tenant
    {
        return Tenant::where('domain', 'localhost')->firstOrFail();
    }

    public function test_halaman_booking_menampilkan_lapangan_aktif_milik_tenant(): void
    {
        $tenant = $this->tenant();
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        Lapangan::factory()->create([
            'tenant_id' => $tenant->id,
            'cabang_id' => $cabang->id,
            'nama' => 'Lapangan A',
            'status_aktif' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Lapangan A');
    }

    public function test_admin_dashboard_mengarahkan_guest_ke_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    public function test_admin_dashboard_bisa_diakses_user_yang_sudah_login(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertOk();
    }

    public function test_admin_laporan_forbidden_kalau_fitur_laporan_pendapatan_tidak_aktif(): void
    {
        $tenant = $this->tenant();
        $user = User::factory()->create();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('basic'),
        ));

        $response = $this->actingAs($user)->get('/admin/laporan');

        $response->assertForbidden();
    }

    public function test_admin_laporan_bisa_diakses_kalau_fitur_laporan_pendapatan_aktif(): void
    {
        $tenant = $this->tenant();
        $user = User::factory()->create();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('pro'),
        ));

        $response = $this->actingAs($user)->get('/admin/laporan');

        $response->assertOk();
    }
}
