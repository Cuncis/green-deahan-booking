<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Cabang;
use App\Models\JadwalSlot;
use App\Models\Lapangan;
use App\Models\Staf;
use App\Models\Tenant;
use App\Models\TenantFitur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LapanganAdminControllerTest extends TestCase
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

    public function test_admin_bisa_lihat_daftar_lapangan(): void
    {
        $tenant = $this->tenant();
        $user = $this->staf($tenant);
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);

        $response = $this->actingAs($user)->get(route('admin.lapangan'));

        $response->assertOk();
        $response->assertViewHas('daftarLapangan', fn ($daftar) => $daftar->count() === 1);
    }

    public function test_daftar_lapangan_tidak_menampilkan_lapangan_tenant_lain(): void
    {
        $tenant = $this->tenant();
        $user = $this->staf($tenant);

        $tenantLain = Tenant::factory()->create(['domain' => 'tenant-lain.test']);
        $cabangLain = Cabang::factory()->create(['tenant_id' => $tenantLain->id]);
        Lapangan::factory()->create(['tenant_id' => $tenantLain->id, 'cabang_id' => $cabangLain->id]);

        $response = $this->actingAs($user)->get(route('admin.lapangan'));

        $response->assertViewHas('daftarLapangan', fn ($daftar) => $daftar->isEmpty());
    }

    public function test_admin_bisa_tambah_lapangan_dengan_foto(): void
    {
        Storage::fake('public');

        $tenant = $this->tenant();
        $user = $this->staf($tenant);
        Cabang::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->post(route('admin.lapangan.store'), [
            'nama' => 'Lapangan Futsal Baru',
            'jenis_olahraga' => 'futsal',
            'harga_per_jam' => 150000,
            'harga_jam_sibuk' => 200000,
            'deskripsi' => 'Lapangan indoor dengan rumput sintetis.',
            'foto' => UploadedFile::fake()->image('lapangan.jpg'),
        ]);

        $response->assertRedirect(route('admin.lapangan'));
        $response->assertSessionHas('success');

        $lapangan = Lapangan::where('tenant_id', $tenant->id)->where('nama', 'Lapangan Futsal Baru')->firstOrFail();
        $this->assertSame(150000, $lapangan->harga_per_jam);
        $this->assertNotNull($lapangan->foto_url);
    }

    /**
     * Tenant diakses dari macam-macam domain (subdomain, custom domain), jadi
     * foto_url yang dihasilkan wajib pakai host request saat ini, bukan
     * APP_URL statis, kalau tidak URL foto akan 404 di domain tenant manapun
     * selain APP_URL itu sendiri.
     */
    public function test_foto_lapangan_pakai_domain_tenant_saat_ini_bukan_app_url_statis(): void
    {
        Storage::fake('public');

        $tenant = Tenant::factory()->create(['domain' => 'demo-tenant.test', 'status_aktif' => true]);
        $user = $this->staf($tenant);
        Cabang::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)->post('http://demo-tenant.test'.route('admin.lapangan.store', [], false), [
            'nama' => 'Lapangan Domain Test',
            'jenis_olahraga' => 'futsal',
            'harga_per_jam' => 100000,
            'foto' => UploadedFile::fake()->image('lapangan.jpg'),
        ]);

        $response->assertRedirect();

        $lapangan = Lapangan::where('tenant_id', $tenant->id)->where('nama', 'Lapangan Domain Test')->firstOrFail();
        $this->assertStringStartsWith('http://demo-tenant.test/storage/lapangan/', $lapangan->foto_url);
    }

    public function test_tambah_lapangan_ditolak_kalau_sudah_capai_batas_paket(): void
    {
        $tenant = $this->tenant();
        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('basic'),
        ));
        $user = $this->staf($tenant);
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);

        $response = $this->actingAs($user)->post(route('admin.lapangan.store'), [
            'nama' => 'Lapangan Kedua',
            'jenis_olahraga' => 'futsal',
            'harga_per_jam' => 100000,
        ]);

        $response->assertSessionHasErrors('nama');
        $this->assertSame(1, Lapangan::where('tenant_id', $tenant->id)->count());
    }

    public function test_halaman_lapangan_basic_yang_capai_batas_menampilkan_tombol_upgrade_pro_dan_premium(): void
    {
        $tenant = $this->tenant();
        $tenant->update(['paket' => 'basic']);
        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('basic'),
        ));
        $user = $this->staf($tenant);
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);

        $response = $this->actingAs($user)->get(route('admin.lapangan'));

        $response->assertSee('Upgrade ke Pro');
        $response->assertSee('Upgrade ke Premium');
    }

    public function test_halaman_lapangan_pro_yang_capai_batas_hanya_menampilkan_tombol_upgrade_premium(): void
    {
        $tenant = $this->tenant();
        $tenant->update(['paket' => 'pro']);
        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('pro'),
        ));
        $user = $this->staf($tenant);
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        Lapangan::factory()->count(3)->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);

        $response = $this->actingAs($user)->get(route('admin.lapangan'));

        $response->assertDontSee('Upgrade ke Pro');
        $response->assertSee('Upgrade ke Premium');
    }

    public function test_tenant_basic_bisa_minta_upgrade_ke_pro_atau_premium(): void
    {
        Mail::fake();

        $tenant = $this->tenant();
        $tenant->update(['paket' => 'basic']);
        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('basic'),
        ));
        $user = $this->staf($tenant);

        $response = $this->actingAs($user)->post(route('admin.lapangan.minta-upgrade'), [
            'paket_tujuan' => 'premium',
        ]);

        $response->assertRedirect(route('admin.lapangan'));
        $response->assertSessionHas('success');
    }

    public function test_tenant_pro_hanya_bisa_minta_upgrade_ke_premium(): void
    {
        Mail::fake();

        $tenant = $this->tenant();
        $tenant->update(['paket' => 'pro']);
        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('pro'),
        ));
        $user = $this->staf($tenant);

        $responseValid = $this->actingAs($user)->post(route('admin.lapangan.minta-upgrade'), [
            'paket_tujuan' => 'premium',
        ]);
        $responseValid->assertSessionHas('success');

        $responseInvalid = $this->actingAs($user)->post(route('admin.lapangan.minta-upgrade'), [
            'paket_tujuan' => 'pro',
        ]);
        $responseInvalid->assertSessionHasErrors('paket_tujuan');
    }

    public function test_tenant_premium_tidak_bisa_minta_upgrade(): void
    {
        Mail::fake();

        $tenant = $this->tenant();
        $tenant->update(['paket' => 'premium']);
        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('premium'),
        ));
        $user = $this->staf($tenant);

        $response = $this->actingAs($user)->post(route('admin.lapangan.minta-upgrade'), [
            'paket_tujuan' => 'premium',
        ]);

        $response->assertSessionHasErrors('paket_tujuan');
    }

    public function test_admin_tidak_bisa_edit_lapangan_tenant_lain(): void
    {
        $tenant = $this->tenant();
        $user = $this->staf($tenant);

        $tenantLain = Tenant::factory()->create(['domain' => 'tenant-lain.test']);
        $cabangLain = Cabang::factory()->create(['tenant_id' => $tenantLain->id]);
        $lapanganLain = Lapangan::factory()->create(['tenant_id' => $tenantLain->id, 'cabang_id' => $cabangLain->id]);

        $response = $this->actingAs($user)->get(route('admin.lapangan.edit', $lapanganLain));

        $response->assertNotFound();
    }

    public function test_admin_bisa_update_lapangan(): void
    {
        $tenant = $this->tenant();
        $user = $this->staf($tenant);
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        $lapangan = Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);

        $response = $this->actingAs($user)->put(route('admin.lapangan.update', $lapangan), [
            'nama' => 'Nama Baru',
            'jenis_olahraga' => 'futsal',
            'harga_per_jam' => 175000,
            'status_aktif' => '0',
        ]);

        $response->assertRedirect(route('admin.lapangan'));
        $lapangan->refresh();
        $this->assertSame('Nama Baru', $lapangan->nama);
        $this->assertSame(175000, $lapangan->harga_per_jam);
        $this->assertFalse($lapangan->status_aktif);
    }

    public function test_admin_bisa_hapus_lapangan_tanpa_riwayat_booking(): void
    {
        $tenant = $this->tenant();
        $user = $this->staf($tenant);
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        $lapangan = Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);

        $response = $this->actingAs($user)->delete(route('admin.lapangan.destroy', $lapangan));

        $response->assertRedirect(route('admin.lapangan'));
        $this->assertModelMissing($lapangan);
    }

    public function test_admin_tidak_bisa_hapus_lapangan_yang_punya_riwayat_booking(): void
    {
        $tenant = $this->tenant();
        $user = $this->staf($tenant);
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        $lapangan = Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);
        $slot = JadwalSlot::factory()->create(['tenant_id' => $tenant->id, 'lapangan_id' => $lapangan->id, 'status' => 'booked']);
        Booking::factory()->create(['tenant_id' => $tenant->id, 'slot_id' => $slot->id, 'status_booking' => 'dikonfirmasi']);

        $response = $this->actingAs($user)->delete(route('admin.lapangan.destroy', $lapangan));

        $response->assertRedirect(route('admin.lapangan'));
        $response->assertSessionHas('error');
        $this->assertModelExists($lapangan);
    }
}
