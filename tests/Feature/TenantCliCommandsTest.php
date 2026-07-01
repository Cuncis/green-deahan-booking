<?php

namespace Tests\Feature;

use App\Models\Staf;
use App\Models\Tenant;
use App\Models\TenantFitur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantCliCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_activate_gagal_kalau_tenant_belum_ada(): void
    {
        $this->artisan('tenant:activate', ['subdomain' => 'tidak-ada'])
            ->expectsOutputToContain('Tenant tidak ditemukan, buat dulu via panel superadmin.')
            ->assertFailed();
    }

    public function test_activate_mengaktifkan_dan_memperpanjang_tenant_yang_sudah_kadaluarsa(): void
    {
        $tenant = Tenant::factory()->create([
            'domain' => 'arena-lama.greendeahan.com',
            'status_aktif' => false,
            'tanggal_berakhir' => today()->subDays(10),
            'email_admin' => 'admin@arenalama.com',
        ]);

        $this->artisan('tenant:activate', ['subdomain' => 'arena-lama', '--perpanjang' => 30])
            ->assertSuccessful();

        $tenant->refresh();
        $this->assertTrue($tenant->status_aktif);
        // Kadaluarsa, jadi dihitung dari hari ini, bukan dari tanggal lama.
        $this->assertSame(today()->addDays(30)->toDateString(), $tenant->tanggal_berakhir->toDateString());
    }

    public function test_activate_memperpanjang_dari_tanggal_berakhir_lama_kalau_masih_aktif(): void
    {
        $tanggalBerakhirLama = today()->addDays(20);
        $tenant = Tenant::factory()->create([
            'domain' => 'arena-aktif.greendeahan.com',
            'status_aktif' => true,
            'tanggal_berakhir' => $tanggalBerakhirLama,
        ]);

        $this->artisan('tenant:activate', ['subdomain' => 'arena-aktif', '--perpanjang' => 30])
            ->assertSuccessful();

        $tenant->refresh();
        $this->assertSame($tanggalBerakhirLama->copy()->addDays(30)->toDateString(), $tenant->tanggal_berakhir->toDateString());
    }

    public function test_activate_bisa_ganti_paket_dan_update_preset_fitur(): void
    {
        $tenant = Tenant::factory()->create(['domain' => 'naik-kelas.greendeahan.com', 'paket' => 'basic']);
        TenantFitur::create(array_merge(['tenant_id' => $tenant->id], TenantFitur::presetUntukPaket('basic')));

        $this->artisan('tenant:activate', ['subdomain' => 'naik-kelas', '--paket' => 'premium'])
            ->assertSuccessful();

        $tenant->refresh();
        $this->assertSame('premium', $tenant->paket);
        $this->assertTrue($tenant->punyaFitur('multi_cabang'));
        $this->assertTrue($tenant->punyaFitur('sistem_membership'));
    }

    public function test_activate_gagal_kalau_paket_tidak_dikenal(): void
    {
        Tenant::factory()->create(['domain' => 'salah-paket.greendeahan.com']);

        $this->artisan('tenant:activate', ['subdomain' => 'salah-paket', '--paket' => 'gratis'])
            ->assertFailed();
    }

    public function test_activate_menampilkan_invitation_link_kalau_ada_email_admin(): void
    {
        Tenant::factory()->create(['domain' => 'ada-email.greendeahan.com', 'email_admin' => 'admin@adaemail.com']);

        $this->artisan('tenant:activate', ['subdomain' => 'ada-email'])
            ->expectsOutputToContain('Link undangan owner')
            ->assertSuccessful();

        $this->assertDatabaseHas('tenant_invitations', [
            'email' => 'admin@adaemail.com',
            'role' => 'owner',
        ]);
    }

    public function test_activate_memperingatkan_kalau_tenant_belum_punya_email_admin(): void
    {
        Tenant::factory()->create(['domain' => 'tanpa-email.greendeahan.com', 'email_admin' => null]);

        $this->artisan('tenant:activate', ['subdomain' => 'tanpa-email'])
            ->expectsOutputToContain('belum punya email PIC')
            ->assertSuccessful();

        $this->assertDatabaseCount('tenant_invitations', 0);
    }

    public function test_deactivate_gagal_kalau_tenant_belum_ada(): void
    {
        $this->artisan('tenant:deactivate', ['subdomain' => 'tidak-ada'])
            ->expectsOutputToContain('Tenant tidak ditemukan, buat dulu via panel superadmin.')
            ->assertFailed();
    }

    public function test_deactivate_menonaktifkan_tenant_setelah_konfirmasi_ya(): void
    {
        $tenant = Tenant::factory()->create(['domain' => 'mau-off.greendeahan.com', 'status_aktif' => true]);

        $this->artisan('tenant:deactivate', ['subdomain' => 'mau-off'])
            ->expectsConfirmation('Yakin ingin menonaktifkan tenant ini? Halaman booking dan dashboard admin mereka akan berhenti bisa diakses.', 'yes')
            ->assertSuccessful();

        $this->assertFalse($tenant->fresh()->status_aktif);
    }

    public function test_deactivate_tidak_mengubah_apapun_kalau_konfirmasi_tidak(): void
    {
        $tenant = Tenant::factory()->create(['domain' => 'batal-off.greendeahan.com', 'status_aktif' => true]);

        $this->artisan('tenant:deactivate', ['subdomain' => 'batal-off'])
            ->expectsConfirmation('Yakin ingin menonaktifkan tenant ini? Halaman booking dan dashboard admin mereka akan berhenti bisa diakses.', 'no')
            ->assertSuccessful();

        $this->assertTrue($tenant->fresh()->status_aktif);
    }

    public function test_list_menampilkan_domain_paket_status_dan_jumlah_staf(): void
    {
        $tenant = Tenant::factory()->create([
            'domain' => 'punya-staf.greendeahan.com',
            'paket' => 'pro',
            'status_aktif' => true,
            'tanggal_berakhir' => '2027-01-01',
        ]);
        $user = User::factory()->create();
        Staf::create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'role' => 'owner', 'status_aktif' => true]);

        // Bangun baris yang diharapkan dari data DB yang sama (termasuk
        // tenant "localhost" baseline dari TestCase::setUp), dengan
        // transformasi yang identik dengan command-nya. expectsTable()
        // membandingkan baris demi baris hasil render tabel yang sesungguhnya,
        // jadi lebih akurat daripada mengecek substring mentah di output
        // (Symfony Table bisa memecah teks per sel saat menulis ke output,
        // jadi expectsOutputToContain gampang gagal walau tabelnya benar).
        $barisDiharapkan = Tenant::withCount('staf')->latest()->get()
            ->map(fn (Tenant $t) => [
                $t->domain,
                $t->paket,
                $t->status_aktif ? 'Aktif' : 'Nonaktif',
                $t->tanggal_berakhir?->format('d/m/Y') ?? '-',
                $t->staf_count,
            ])
            ->all();

        $this->artisan('tenant:list')
            ->expectsTable(['Domain', 'Paket', 'Status', 'Tanggal Berakhir', 'Jumlah Staf'], $barisDiharapkan)
            ->assertSuccessful();
    }

    public function test_list_menampilkan_pesan_kalau_belum_ada_tenant(): void
    {
        Tenant::query()->delete();

        $this->artisan('tenant:list')
            ->expectsOutputToContain('Belum ada tenant terdaftar.')
            ->assertSuccessful();
    }
}
