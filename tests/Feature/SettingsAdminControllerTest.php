<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\Staf;
use App\Models\Tenant;
use App\Models\TenantFitur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsAdminControllerTest extends TestCase
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

    public function test_admin_bisa_lihat_halaman_pengaturan(): void
    {
        $tenant = $this->tenant();
        $user = $this->staf($tenant);

        $response = $this->actingAs($user)->get(route('admin.pengaturan'));

        $response->assertOk();
        $response->assertSee($tenant->nama_bisnis);
    }

    public function test_admin_bisa_update_profil_bisnis_dan_cabang_default(): void
    {
        Storage::fake('public');

        $tenant = $this->tenant();
        $user = $this->staf($tenant);
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id, 'jam_buka' => '08:00', 'jam_tutup' => '22:00']);

        $response = $this->actingAs($user)->put(route('admin.pengaturan.update'), [
            'nama_bisnis' => 'Nama Bisnis Baru',
            'whatsapp_admin' => '628111222333',
            'email_admin' => 'admin@example.com',
            'warna_utama' => '#123456',
            'jam_buka' => '07:00',
            'jam_tutup' => '23:00',
        ]);

        $response->assertRedirect(route('admin.pengaturan'));
        $response->assertSessionHas('success');

        $tenant->refresh();
        $this->assertSame('Nama Bisnis Baru', $tenant->nama_bisnis);
        $this->assertSame('628111222333', $tenant->whatsapp_admin);
        $this->assertSame('admin@example.com', $tenant->email_admin);
        $this->assertSame('#123456', $tenant->warna_utama);

        $cabang->refresh();
        $this->assertSame('07:00', $cabang->jam_buka);
        $this->assertSame('23:00', $cabang->jam_tutup);
    }

    public function test_admin_bisa_upload_logo(): void
    {
        Storage::fake('public');

        $tenant = $this->tenant();
        $user = $this->staf($tenant);
        Cabang::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->put(route('admin.pengaturan.update'), [
            'nama_bisnis' => $tenant->nama_bisnis,
            'whatsapp_admin' => $tenant->whatsapp_admin ?? '628111222333',
            'jam_buka' => '08:00',
            'jam_tutup' => '22:00',
            'logo' => UploadedFile::fake()->image('logo.jpg'),
        ]);

        $response->assertRedirect(route('admin.pengaturan'));
        $this->assertNotNull($tenant->fresh()->logo_url);
    }

    public function test_update_gagal_kalau_format_warna_tidak_valid(): void
    {
        $tenant = $this->tenant();
        $user = $this->staf($tenant);
        Cabang::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->put(route('admin.pengaturan.update'), [
            'nama_bisnis' => $tenant->nama_bisnis,
            'whatsapp_admin' => '628111222333',
            'warna_utama' => 'bukan-hex',
            'jam_buka' => '08:00',
            'jam_tutup' => '22:00',
        ]);

        $response->assertSessionHasErrors('warna_utama');
    }

    public function test_premium_bisa_update_beberapa_cabang_sekaligus(): void
    {
        $tenant = $this->tenant();
        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('premium'),
        ));
        $user = $this->staf($tenant);

        $cabangA = Cabang::factory()->create(['tenant_id' => $tenant->id, 'nama_cabang' => 'Cabang A']);
        $cabangB = Cabang::factory()->create(['tenant_id' => $tenant->id, 'nama_cabang' => 'Cabang B']);

        $response = $this->actingAs($user)->put(route('admin.pengaturan.update'), [
            'nama_bisnis' => $tenant->nama_bisnis,
            'whatsapp_admin' => '628111222333',
            'cabang' => [
                $cabangA->id => ['nama_cabang' => 'Cabang A Baru', 'alamat' => 'Jl. A', 'kota' => 'Jakarta', 'jam_buka' => '08:00', 'jam_tutup' => '22:00'],
                $cabangB->id => ['nama_cabang' => 'Cabang B Baru', 'alamat' => 'Jl. B', 'kota' => 'Bekasi', 'jam_buka' => '09:00', 'jam_tutup' => '21:00'],
            ],
        ]);

        $response->assertRedirect(route('admin.pengaturan'));
        $this->assertSame('Cabang A Baru', $cabangA->fresh()->nama_cabang);
        $this->assertSame('Cabang B Baru', $cabangB->fresh()->nama_cabang);
    }

    public function test_premium_tidak_bisa_update_cabang_tenant_lain_lewat_id_asing(): void
    {
        $tenant = $this->tenant();
        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('premium'),
        ));
        $user = $this->staf($tenant);
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);

        $tenantLain = Tenant::factory()->create(['domain' => 'tenant-lain.test']);
        $cabangLain = Cabang::factory()->create(['tenant_id' => $tenantLain->id, 'nama_cabang' => 'Milik Tenant Lain']);

        $response = $this->actingAs($user)->put(route('admin.pengaturan.update'), [
            'nama_bisnis' => $tenant->nama_bisnis,
            'whatsapp_admin' => '628111222333',
            'cabang' => [
                $cabang->id => ['nama_cabang' => 'Cabang Sendiri', 'alamat' => 'Jl. A', 'kota' => 'Jakarta', 'jam_buka' => '08:00', 'jam_tutup' => '22:00'],
                $cabangLain->id => ['nama_cabang' => 'Coba Diubah', 'alamat' => 'Jl. B', 'kota' => 'Bekasi', 'jam_buka' => '09:00', 'jam_tutup' => '21:00'],
            ],
        ]);

        $response->assertRedirect(route('admin.pengaturan'));
        $this->assertSame('Milik Tenant Lain', $cabangLain->fresh()->nama_cabang);
    }
}
