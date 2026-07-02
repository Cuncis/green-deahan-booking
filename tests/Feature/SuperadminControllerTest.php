<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Cabang;
use App\Models\JadwalSlot;
use App\Models\Lapangan;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SuperadminControllerTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->is_superadmin = true;
        $user->save();

        return $user;
    }

    public function test_dashboard_menampilkan_statistik_dan_tenant_terbaru(): void
    {
        $superadmin = $this->superadmin();

        $tenant = Tenant::where('domain', 'localhost')->firstOrFail();
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        $lapangan = Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);
        $slot = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'tanggal' => now()->toDateString(),
        ]);
        Booking::factory()->create(['tenant_id' => $tenant->id, 'slot_id' => $slot->id]);

        $response = $this->actingAs($superadmin)->get(route('superadmin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('totalTenant', 1);
        $response->assertViewHas('tenantAktif', 1);
        $response->assertViewHas('bookingHariIni', 1);
        $response->assertSee($tenant->nama_bisnis);
    }

    public function test_tenants_menampilkan_semua_tenant(): void
    {
        $superadmin = $this->superadmin();
        Tenant::factory()->create(['domain' => 'kedua.test', 'nama_bisnis' => 'Tenant Kedua']);

        $response = $this->actingAs($superadmin)->get(route('superadmin.tenants'));

        $response->assertOk();
        $response->assertSee('Tenant Kedua');
    }

    public function test_tenants_bisa_difilter_berdasarkan_paket(): void
    {
        $superadmin = $this->superadmin();
        Tenant::factory()->create(['domain' => 'satu-pro.test', 'nama_bisnis' => 'Tenant Pro Satu', 'paket' => 'pro']);
        Tenant::factory()->create(['domain' => 'satu-premium.test', 'nama_bisnis' => 'Tenant Premium Satu', 'paket' => 'premium']);

        $response = $this->actingAs($superadmin)->get(route('superadmin.tenants', ['paket' => 'pro']));

        $response->assertOk();
        $response->assertSee('Tenant Pro Satu');
        $response->assertDontSee('Tenant Premium Satu');
    }

    public function test_tenants_bisa_difilter_berdasarkan_status(): void
    {
        $superadmin = $this->superadmin();
        Tenant::factory()->create(['domain' => 'aktif.test', 'nama_bisnis' => 'Tenant Aktif', 'status_aktif' => true]);
        Tenant::factory()->create(['domain' => 'nonaktif.test', 'nama_bisnis' => 'Tenant Nonaktif', 'status_aktif' => false]);

        $response = $this->actingAs($superadmin)->get(route('superadmin.tenants', ['status' => 'nonaktif']));

        $response->assertOk();
        $response->assertSee('Tenant Nonaktif');
        $response->assertDontSee('Tenant Aktif');
    }

    public function test_form_tambah_tenant_bisa_dibuka(): void
    {
        $superadmin = $this->superadmin();

        $response = $this->actingAs($superadmin)->get(route('superadmin.tenants.create'));

        $response->assertOk();
        $response->assertSee('Tambah Tenant Baru');
        $response->assertSee('Info Bisnis');
        $response->assertSee('Info PIC');
        $response->assertSee('.greendeahan.com');
        $response->assertSee('Buat Tenant dan Generate Invitation Link');
        $response->assertSee('Basic');
        $response->assertSee('Pro');
        $response->assertSee('Premium');
        $response->assertDontSee('💬');
    }

    public function test_store_membuat_tenant_fitur_cabang_dan_invitation(): void
    {
        Mail::fake();
        $superadmin = $this->superadmin();

        $response = $this->actingAs($superadmin)->post(route('superadmin.tenants.store'), [
            'nama_bisnis' => 'Arena Sport Baru',
            'subdomain' => 'arena-baru',
            'paket' => 'pro',
            'kota' => 'Bandung',
            'nama_pic' => 'Budi PIC',
            'email_pic' => 'budi@arenabaru.com',
            'whatsapp_pic' => '081234567890',
        ]);

        $tenant = Tenant::where('domain', 'arena-baru.greendeahan.com')->first();
        $this->assertNotNull($tenant);
        $this->assertSame('pro', $tenant->paket);
        $this->assertTrue($tenant->status_aktif);
        $this->assertSame('budi@arenabaru.com', $tenant->email_admin);
        $this->assertSame('081234567890', $tenant->whatsapp_admin);
        $this->assertTrue($tenant->punyaFitur('kode_promo'));
        $this->assertFalse($tenant->punyaFitur('multi_cabang'));

        $this->assertDatabaseHas('cabang', [
            'tenant_id' => $tenant->id,
            'nama_cabang' => 'Cabang Utama',
            'kota' => 'Bandung',
        ]);

        $invitation = TenantInvitation::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($invitation);
        $this->assertSame('budi@arenabaru.com', $invitation->email);
        $this->assertSame('owner', $invitation->role);

        $response->assertOk();
        $response->assertSee('Tenant Berhasil Dibuat');
        $response->assertSee('Arena Sport Baru');
        $response->assertSee($invitation->token, false);
        $response->assertSee('Budi PIC');
        $response->assertSee('Kopi Link');
        $response->assertSee('Buat Tenant Lain');
        $response->assertSee('Kembali ke Daftar Tenant');
        $response->assertDontSee('💬');
    }

    public function test_store_gagal_kalau_subdomain_sudah_dipakai(): void
    {
        $superadmin = $this->superadmin();
        Tenant::factory()->create(['domain' => 'sudah-ada.greendeahan.com']);

        $response = $this->actingAs($superadmin)->post(route('superadmin.tenants.store'), [
            'nama_bisnis' => 'Tenant Lain',
            'subdomain' => 'sudah-ada',
            'paket' => 'basic',
            'kota' => 'Bandung',
            'nama_pic' => 'Budi',
            'email_pic' => 'budi@lain.com',
            'whatsapp_pic' => '081234567890',
        ]);

        $response->assertSessionHasErrors('subdomain');
        $this->assertDatabaseCount('tenants', 2); // localhost (baseline) + sudah-ada
    }

    public function test_store_gagal_kalau_subdomain_mengandung_karakter_tidak_valid(): void
    {
        $superadmin = $this->superadmin();

        $response = $this->actingAs($superadmin)->post(route('superadmin.tenants.store'), [
            'nama_bisnis' => 'Tenant Lain',
            'subdomain' => 'Ada Spasi!',
            'paket' => 'basic',
            'kota' => 'Bandung',
            'nama_pic' => 'Budi',
            'email_pic' => 'budi@lain.com',
            'whatsapp_pic' => '081234567890',
        ]);

        $response->assertSessionHasErrors('subdomain');
    }

    public function test_store_gagal_kalau_email_pic_sudah_dipakai_user_lain(): void
    {
        $superadmin = $this->superadmin();
        User::factory()->create(['email' => 'dipakai@sudah.com']);

        $response = $this->actingAs($superadmin)->post(route('superadmin.tenants.store'), [
            'nama_bisnis' => 'Tenant Lain',
            'subdomain' => 'tenant-lain',
            'paket' => 'basic',
            'kota' => 'Bandung',
            'nama_pic' => 'Budi',
            'email_pic' => 'dipakai@sudah.com',
            'whatsapp_pic' => '081234567890',
        ]);

        $response->assertSessionHasErrors('email_pic');
        $this->assertDatabaseMissing('tenants', ['domain' => 'tenant-lain.greendeahan.com']);
    }

    public function test_activate_mengaktifkan_tenant(): void
    {
        $superadmin = $this->superadmin();
        $tenant = Tenant::factory()->create(['domain' => 'nonaktif.test', 'status_aktif' => false]);

        $response = $this->actingAs($superadmin)->post(route('superadmin.tenants.activate', $tenant));

        $response->assertRedirect(route('superadmin.tenants'));
        $this->assertTrue($tenant->fresh()->status_aktif);
    }

    public function test_deactivate_menonaktifkan_tenant(): void
    {
        $superadmin = $this->superadmin();
        $tenant = Tenant::factory()->create(['domain' => 'aktif.test', 'status_aktif' => true]);

        $response = $this->actingAs($superadmin)->post(route('superadmin.tenants.deactivate', $tenant));

        $response->assertRedirect(route('superadmin.tenants'));
        $this->assertFalse($tenant->fresh()->status_aktif);
    }

    public function test_invite_owner_membuat_invitation_baru_untuk_email_admin_tenant(): void
    {
        Mail::fake();
        $superadmin = $this->superadmin();
        $tenant = Tenant::factory()->create(['domain' => 'butuh-invite.test', 'email_admin' => 'admin@butuhinvite.com']);

        $response = $this->actingAs($superadmin)->post(route('superadmin.tenants.invite', $tenant));

        $response->assertRedirect(route('superadmin.tenants'));
        $response->assertSessionHas('invitation_link');

        $this->assertDatabaseHas('tenant_invitations', [
            'tenant_id' => $tenant->id,
            'email' => 'admin@butuhinvite.com',
            'role' => 'owner',
        ]);
    }

    public function test_invite_owner_gagal_kalau_tenant_belum_punya_email_admin(): void
    {
        $superadmin = $this->superadmin();
        $tenant = Tenant::factory()->create(['domain' => 'tanpa-email.test', 'email_admin' => null]);

        $response = $this->actingAs($superadmin)->post(route('superadmin.tenants.invite', $tenant));

        $response->assertRedirect(route('superadmin.tenants'));
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('tenant_invitations', 0);
    }

    public function test_bukan_superadmin_ditolak_dari_semua_route_superadmin(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        $this->actingAs($user)->get(route('superadmin.tenants'))->assertForbidden();
        $this->actingAs($user)->get(route('superadmin.tenants.create'))->assertForbidden();
        $this->actingAs($user)->post(route('superadmin.tenants.store'), [])->assertForbidden();
        $this->actingAs($user)->post(route('superadmin.tenants.activate', $tenant))->assertForbidden();
    }

    public function test_show_menampilkan_domain_default_dan_status_belum_dikonfigurasi(): void
    {
        $superadmin = $this->superadmin();
        $tenant = Tenant::factory()->create(['paket' => 'pro', 'custom_domain' => null]);

        $response = $this->actingAs($superadmin)->get(route('superadmin.tenants.show', $tenant));

        $response->assertOk();
        $response->assertSee($tenant->domain);
        $response->assertSee('Belum dikonfigurasi');
        $response->assertDontSee('💬');
    }

    public function test_show_menampilkan_dan_prefill_custom_domain_yang_diminta_saat_daftar(): void
    {
        $superadmin = $this->superadmin();
        $tenant = Tenant::factory()->create([
            'paket' => 'pro',
            'custom_domain' => null,
            'custom_domain_diminta' => 'diminta-pelanggan.com',
        ]);

        $response = $this->actingAs($superadmin)->get(route('superadmin.tenants.show', $tenant));

        $response->assertOk();
        $response->assertSee('diminta-pelanggan.com');
        $response->assertSee('value="diminta-pelanggan.com"', false);
    }

    public function test_show_untuk_paket_basic_menampilkan_pesan_upgrade_bukan_form(): void
    {
        $superadmin = $this->superadmin();
        $tenant = Tenant::factory()->create(['paket' => 'basic']);

        $response = $this->actingAs($superadmin)->get(route('superadmin.tenants.show', $tenant));

        $response->assertOk();
        $response->assertSee('Custom domain hanya tersedia untuk paket Pro dan Premium');
        $response->assertDontSee('name="custom_domain"', false);
    }

    public function test_show_status_aktif_kalau_domain_bisa_dijangkau(): void
    {
        Http::fake(['https://sudah-hidup.com' => Http::response('ok', 200)]);

        $superadmin = $this->superadmin();
        $tenant = Tenant::factory()->create(['paket' => 'pro', 'custom_domain' => 'sudah-hidup.com']);

        $response = $this->actingAs($superadmin)->get(route('superadmin.tenants.show', $tenant));

        $response->assertOk();
        $response->assertSeeInOrder(['Status Verifikasi', 'Aktif']);
        $response->assertSee('sudo certbot --nginx -d sudah-hidup.com', false);
    }

    public function test_show_status_ssl_pending_kalau_domain_belum_bisa_dijangkau(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection refused');
        });

        $superadmin = $this->superadmin();
        $tenant = Tenant::factory()->create(['paket' => 'pro', 'custom_domain' => 'belum-hidup.com']);

        $response = $this->actingAs($superadmin)->get(route('superadmin.tenants.show', $tenant));

        $response->assertOk();
        $response->assertSee('SSL Pending');
    }

    public function test_set_custom_domain_berhasil_untuk_paket_pro(): void
    {
        $superadmin = $this->superadmin();
        $tenant = Tenant::factory()->create(['paket' => 'pro', 'custom_domain' => null]);

        $response = $this->actingAs($superadmin)->post(route('superadmin.tenants.custom-domain.store', $tenant), [
            'custom_domain' => 'Klien-Sendiri.com',
        ]);

        $response->assertRedirect(route('superadmin.tenants.show', $tenant));
        $response->assertSessionHas('success');
        $this->assertSame('klien-sendiri.com', $tenant->fresh()->custom_domain);
    }

    public function test_set_custom_domain_ditolak_untuk_paket_basic(): void
    {
        $superadmin = $this->superadmin();
        $tenant = Tenant::factory()->create(['paket' => 'basic', 'custom_domain' => null]);

        $response = $this->actingAs($superadmin)->post(route('superadmin.tenants.custom-domain.store', $tenant), [
            'custom_domain' => 'klien-sendiri.com',
        ]);

        $response->assertSessionHas('error');
        $this->assertNull($tenant->fresh()->custom_domain);
    }

    public function test_set_custom_domain_ditolak_kalau_format_tidak_valid(): void
    {
        $superadmin = $this->superadmin();
        $tenant = Tenant::factory()->create(['paket' => 'pro']);

        $response = $this->actingAs($superadmin)->post(route('superadmin.tenants.custom-domain.store', $tenant), [
            'custom_domain' => 'ini bukan domain!!',
        ]);

        $response->assertSessionHasErrors('custom_domain');
        $this->assertNull($tenant->fresh()->custom_domain);
    }

    public function test_set_custom_domain_ditolak_kalau_subdomain_greendeahan(): void
    {
        $superadmin = $this->superadmin();
        $tenant = Tenant::factory()->create(['paket' => 'premium']);

        $response = $this->actingAs($superadmin)->post(route('superadmin.tenants.custom-domain.store', $tenant), [
            'custom_domain' => 'apapun.greendeahan.com',
        ]);

        $response->assertSessionHasErrors('custom_domain');
        $this->assertNull($tenant->fresh()->custom_domain);
    }

    public function test_set_custom_domain_ditolak_kalau_sudah_dipakai_tenant_lain(): void
    {
        $superadmin = $this->superadmin();
        Tenant::factory()->create(['custom_domain' => 'sudah-dipakai.com']);
        $tenant = Tenant::factory()->create(['paket' => 'pro', 'custom_domain' => null]);

        $response = $this->actingAs($superadmin)->post(route('superadmin.tenants.custom-domain.store', $tenant), [
            'custom_domain' => 'sudah-dipakai.com',
        ]);

        $response->assertSessionHasErrors('custom_domain');
        $this->assertNull($tenant->fresh()->custom_domain);
    }

    public function test_set_custom_domain_boleh_dipakai_ulang_oleh_tenant_yang_sama(): void
    {
        $superadmin = $this->superadmin();
        $tenant = Tenant::factory()->create(['paket' => 'pro', 'custom_domain' => 'punya-sendiri.com']);

        $response = $this->actingAs($superadmin)->post(route('superadmin.tenants.custom-domain.store', $tenant), [
            'custom_domain' => 'punya-sendiri.com',
        ]);

        $response->assertSessionDoesntHaveErrors('custom_domain');
    }

    public function test_remove_custom_domain_mengosongkan_kolomnya(): void
    {
        $superadmin = $this->superadmin();
        $tenant = Tenant::factory()->create(['paket' => 'pro', 'custom_domain' => 'mau-dihapus.com']);

        $response = $this->actingAs($superadmin)->delete(route('superadmin.tenants.custom-domain.destroy', $tenant));

        $response->assertRedirect(route('superadmin.tenants.show', $tenant));
        $this->assertNull($tenant->fresh()->custom_domain);
    }
}
