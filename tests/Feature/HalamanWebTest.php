<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Lapangan;
use App\Models\Staf;
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

    public function test_halaman_booking_menampilkan_pesan_kosong_kalau_belum_ada_lapangan(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Belum ada lapangan tersedia untuk booking saat ini.');
    }

    public function test_halaman_booking_bisa_pindah_lapangan_lewat_query_string(): void
    {
        $tenant = $this->tenant();
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        $lapanganA = Lapangan::factory()->create([
            'tenant_id' => $tenant->id,
            'cabang_id' => $cabang->id,
            'nama' => 'Lapangan A',
        ]);
        $lapanganB = Lapangan::factory()->create([
            'tenant_id' => $tenant->id,
            'cabang_id' => $cabang->id,
            'nama' => 'Lapangan B',
        ]);

        $response = $this->get('/?lapangan='.$lapanganB->id);

        $response->assertOk();
        $response->assertSee('Lapangan B');
    }

    public function test_halaman_booking_menampilkan_input_promo_hanya_kalau_fitur_aktif(): void
    {
        $tenant = $this->tenant();
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('basic'),
        ));

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('Kode promo, misal SEPI20');
    }

    public function test_halaman_booking_menampilkan_input_promo_kalau_fitur_kode_promo_aktif(): void
    {
        $tenant = $this->tenant();
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('pro'),
        ));

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Kode promo, misal SEPI20');
    }

    public function test_halaman_booking_tidak_mengandung_emoji_mentah(): void
    {
        $tenant = $this->tenant();
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('🌿')
            ->assertDontSee('💬')
            ->assertDontSee('📍')
            ->assertDontSee('⭐')
            ->assertDontSee('🔥');
    }

    public function test_halaman_booking_menampilkan_transfer_manual_kalau_pembayaran_online_tidak_aktif(): void
    {
        $tenant = $this->tenant();
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('basic'),
        ));

        $tenant->update([
            'bank_nama' => 'Bank Sinarmas',
            'bank_no_rekening' => '1234567890',
            'bank_pemilik_rekening' => 'PT Green Deahan',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Transfer Manual');
        $response->assertSee('Konfirmasi via WhatsApp');
        $response->assertSee('Bank Sinarmas');
    }

    public function test_halaman_booking_tidak_menampilkan_transfer_manual_kalau_pembayaran_online_aktif(): void
    {
        $tenant = $this->tenant();
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('pro'),
        ));

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('Transfer Manual');
    }

    public function test_halaman_booking_menampilkan_toggle_reminder_kalau_fitur_aktif(): void
    {
        $tenant = $this->tenant();
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('premium'),
        ));

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Kirim pengingat WhatsApp 2 jam sebelum jadwal main');
    }

    public function test_halaman_booking_tidak_menampilkan_toggle_reminder_kalau_fitur_tidak_aktif(): void
    {
        $tenant = $this->tenant();
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('pro'),
        ));

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('Kirim pengingat WhatsApp 2 jam sebelum jadwal main');
    }

    public function test_admin_dashboard_mengarahkan_guest_ke_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    public function test_admin_dashboard_bisa_diakses_user_yang_sudah_login(): void
    {
        $tenant = $this->tenant();
        $user = $this->staf($tenant);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertOk();
    }

    /**
     * Beda dengan test guest di atas: ini user yang SUDAH login tapi tidak
     * punya baris staf sama sekali di tenant manapun. Middleware 'auth' saja
     * lolos, tapi CheckTenantStaf (references/multi-tenant.md) harus
     * menolaknya dengan 403, bukan malah bisa lihat dashboard tenant orang.
     */
    public function test_user_tanpa_staf_record_tidak_bisa_akses_admin(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertForbidden();
    }

    public function test_user_dengan_staf_record_bisa_akses_admin_tenant_sendiri(): void
    {
        $tenant = $this->tenant();
        $user = $this->staf($tenant);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertOk();
    }

    public function test_admin_laporan_forbidden_kalau_fitur_laporan_pendapatan_tidak_aktif(): void
    {
        $tenant = $this->tenant();
        $user = $this->staf($tenant);

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
        $user = $this->staf($tenant);

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('pro'),
        ));

        $response = $this->actingAs($user)->get('/admin/laporan');

        $response->assertOk();
    }
}
