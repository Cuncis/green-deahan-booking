<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\JadwalSlot;
use App\Models\Lapangan;
use App\Models\Staf;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class JadwalAdminControllerTest extends TestCase
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

    private function buatLapangan(Tenant $tenant, array $cabangOverride = []): Lapangan
    {
        $cabang = Cabang::factory()->create(array_merge(['tenant_id' => $tenant->id], $cabangOverride));

        return Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);
    }

    /**
     * @return array{lapangan_id: int, tanggal_mulai: string, tanggal_selesai: string, interval_jam: int, harga: int}
     */
    private function dataGenerate(Lapangan $lapangan): array
    {
        return [
            'lapangan_id' => $lapangan->id,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->toDateString(),
            'interval_jam' => 1,
            'harga' => 100000,
        ];
    }

    public function test_admin_bisa_lihat_halaman_jadwal(): void
    {
        $tenant = $this->tenant();
        $user = $this->staf($tenant);
        $lapangan = $this->buatLapangan($tenant);
        JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:00',
            'status' => 'kosong',
        ]);

        $response = $this->actingAs($user)->get(route('admin.jadwal'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/Jadwal')
            ->has('ringkasanMingguan', 7)
            ->has('slot', 1)
            ->where('slot.0.jam', '08:00 - 09:00')
            ->where('slot.0.bisaDiubahStatus', true)
        );
    }

    public function test_halaman_jadwal_tidak_menampilkan_slot_tenant_lain(): void
    {
        $tenant = $this->tenant();
        $user = $this->staf($tenant);
        $tenantLain = Tenant::factory()->create(['domain' => 'tenant-lain.test']);
        $lapanganLain = $this->buatLapangan($tenantLain);
        JadwalSlot::factory()->create([
            'tenant_id' => $tenantLain->id,
            'lapangan_id' => $lapanganLain->id,
            'tanggal' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.jadwal', ['lapangan' => $lapanganLain->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('lapanganId', null)
                ->has('slot', 0)
                ->has('daftarLapangan', 0)
            );
    }

    public function test_query_tanggal_tidak_valid_jatuh_ke_hari_ini(): void
    {
        $user = $this->staf($this->tenant());

        $this->actingAs($user)
            ->get(route('admin.jadwal', ['tanggal' => 'bukan-tanggal']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('tanggalDipilih', now()->toDateString()));
    }

    public function test_generate_slot_membuat_slot_sesuai_jam_buka_tutup_dan_interval(): void
    {
        $tenant = $this->tenant();
        $user = $this->staf($tenant);
        $lapangan = $this->buatLapangan($tenant, ['jam_buka' => '08:00', 'jam_tutup' => '11:00']);

        $this->actingAs($user)
            ->post(route('admin.jadwal.generate'), $this->dataGenerate($lapangan))
            ->assertRedirect()
            ->assertSessionHas('success', '3 slot berhasil dibuat.');

        $slot = JadwalSlot::where('tenant_id', $tenant->id)->where('lapangan_id', $lapangan->id)->get();

        $this->assertCount(3, $slot);
        $this->assertSame(['08:00', '09:00', '10:00'], $slot->pluck('jam_mulai')->sort()->values()->all());
        $this->assertTrue($slot->every(fn (JadwalSlot $s) => $s->harga === 100000 && $s->status === 'kosong'));
    }

    public function test_generate_slot_melewati_slot_yang_sudah_ada(): void
    {
        $tenant = $this->tenant();
        $user = $this->staf($tenant);
        $lapangan = $this->buatLapangan($tenant, ['jam_buka' => '08:00', 'jam_tutup' => '10:00']);

        JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:00',
        ]);

        $this->actingAs($user)
            ->post(route('admin.jadwal.generate'), $this->dataGenerate($lapangan))
            ->assertSessionHas('success', '1 slot berhasil dibuat, 1 slot dilewati karena sudah ada.');

        $this->assertCount(2, JadwalSlot::where('tenant_id', $tenant->id)->where('lapangan_id', $lapangan->id)->get());
    }

    public function test_generate_slot_validasi_tanggal_selesai_dan_lapangan_tenant_lain(): void
    {
        $tenant = $this->tenant();
        $user = $this->staf($tenant);
        $lapanganLain = $this->buatLapangan(Tenant::factory()->create(['domain' => 'tenant-lain.test']));

        $this->actingAs($user)
            ->post(route('admin.jadwal.generate'), array_merge($this->dataGenerate($lapanganLain), [
                'tanggal_selesai' => now()->subDay()->toDateString(),
            ]))
            ->assertSessionHasErrors(['lapangan_id', 'tanggal_selesai']);

        $this->assertSame(0, JadwalSlot::count());
    }

    public function test_toggle_nonaktif_slot_kosong_lalu_aktifkan_lagi(): void
    {
        $tenant = $this->tenant();
        $user = $this->staf($tenant);
        $lapangan = $this->buatLapangan($tenant);
        $slot = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'status' => 'kosong',
        ]);

        $this->actingAs($user)
            ->post(route('admin.jadwal.status', $slot->id))
            ->assertSessionHas('success', 'Slot dinonaktifkan.');
        $this->assertSame('nonaktif', $slot->fresh()->status);

        $this->actingAs($user)
            ->post(route('admin.jadwal.status', $slot->id))
            ->assertSessionHas('success', 'Slot diaktifkan kembali.');
        $this->assertSame('kosong', $slot->fresh()->status);
    }

    public function test_toggle_nonaktif_ditolak_untuk_slot_yang_sudah_booked(): void
    {
        $tenant = $this->tenant();
        $user = $this->staf($tenant);
        $lapangan = $this->buatLapangan($tenant);
        $slot = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'status' => 'booked',
        ]);

        $this->actingAs($user)
            ->post(route('admin.jadwal.status', $slot->id))
            ->assertSessionHas('error', 'Slot yang sudah di-hold atau dibooking tidak bisa dinonaktifkan.');

        $this->assertSame('booked', $slot->fresh()->status);
    }

    public function test_simpan_harga_slot_memperbarui_harga(): void
    {
        $tenant = $this->tenant();
        $user = $this->staf($tenant);
        $lapangan = $this->buatLapangan($tenant);
        $slot = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'harga' => 100000,
        ]);

        $this->actingAs($user)
            ->put(route('admin.jadwal.harga', $slot->id), ['harga' => 175000])
            ->assertSessionHas('success');

        $this->assertSame(175000, $slot->fresh()->harga);
    }

    public function test_harga_slot_wajib_angka_tidak_negatif(): void
    {
        $tenant = $this->tenant();
        $user = $this->staf($tenant);
        $slot = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $this->buatLapangan($tenant)->id,
            'harga' => 100000,
        ]);

        $this->actingAs($user)
            ->put(route('admin.jadwal.harga', $slot->id), ['harga' => -5])
            ->assertSessionHasErrors('harga');

        $this->assertSame(100000, $slot->fresh()->harga);
    }

    public function test_tidak_bisa_ubah_slot_milik_tenant_lain(): void
    {
        $tenant = $this->tenant();
        $user = $this->staf($tenant);

        $tenantLain = Tenant::factory()->create(['domain' => 'tenant-lain.test']);
        $slotLain = JadwalSlot::factory()->create([
            'tenant_id' => $tenantLain->id,
            'lapangan_id' => $this->buatLapangan($tenantLain)->id,
            'harga' => 100000,
            'status' => 'kosong',
        ]);

        $this->actingAs($user)->put(route('admin.jadwal.harga', $slotLain->id), ['harga' => 1])->assertNotFound();
        $this->actingAs($user)->post(route('admin.jadwal.status', $slotLain->id))->assertNotFound();

        $this->assertSame(100000, $slotLain->fresh()->harga);
        $this->assertSame('kosong', $slotLain->fresh()->status);
    }
}
