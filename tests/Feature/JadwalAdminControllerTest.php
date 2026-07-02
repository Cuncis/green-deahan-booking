<?php

namespace Tests\Feature;

use App\Livewire\KalenderAdmin;
use App\Models\Cabang;
use App\Models\JadwalSlot;
use App\Models\Lapangan;
use App\Models\Staf;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_admin_bisa_lihat_halaman_jadwal(): void
    {
        $tenant = $this->tenant();
        $user = $this->staf($tenant);

        $response = $this->actingAs($user)->get(route('admin.jadwal'));

        $response->assertOk();
        $response->assertSeeLivewire('kalender-admin');
    }

    public function test_generate_slot_membuat_slot_sesuai_jam_buka_tutup_dan_interval(): void
    {
        $tenant = $this->tenant();
        app()->instance('tenant', $tenant);
        $lapangan = $this->buatLapangan($tenant, ['jam_buka' => '08:00', 'jam_tutup' => '11:00']);

        Livewire::test(KalenderAdmin::class)
            ->set('genLapanganId', $lapangan->id)
            ->set('genTanggalMulai', now()->toDateString())
            ->set('genTanggalSelesai', now()->toDateString())
            ->set('genIntervalJam', 1)
            ->set('genHarga', 100000)
            ->call('generateSlot')
            ->assertSet('tampilkanGenerateForm', false);

        $slot = JadwalSlot::where('tenant_id', $tenant->id)->where('lapangan_id', $lapangan->id)->get();

        $this->assertCount(3, $slot);
        $this->assertSame(['08:00', '09:00', '10:00'], $slot->pluck('jam_mulai')->sort()->values()->all());
        $this->assertTrue($slot->every(fn (JadwalSlot $s) => $s->harga === 100000 && $s->status === 'kosong'));
    }

    public function test_generate_slot_melewati_slot_yang_sudah_ada(): void
    {
        $tenant = $this->tenant();
        app()->instance('tenant', $tenant);
        $lapangan = $this->buatLapangan($tenant, ['jam_buka' => '08:00', 'jam_tutup' => '10:00']);

        JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:00',
        ]);

        Livewire::test(KalenderAdmin::class)
            ->set('genLapanganId', $lapangan->id)
            ->set('genTanggalMulai', now()->toDateString())
            ->set('genTanggalSelesai', now()->toDateString())
            ->set('genIntervalJam', 1)
            ->set('genHarga', 100000)
            ->call('generateSlot')
            ->assertSet('pesanGenerate', '1 slot berhasil dibuat, 1 slot dilewati karena sudah ada.');

        $this->assertCount(2, JadwalSlot::where('tenant_id', $tenant->id)->where('lapangan_id', $lapangan->id)->get());
    }

    public function test_toggle_nonaktif_slot_kosong_lalu_aktifkan_lagi(): void
    {
        $tenant = $this->tenant();
        app()->instance('tenant', $tenant);
        $lapangan = $this->buatLapangan($tenant);
        $slot = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'status' => 'kosong',
        ]);

        Livewire::test(KalenderAdmin::class)
            ->call('toggleNonaktif', $slot->id)
            ->assertSet('pesanError', null);

        $this->assertSame('nonaktif', $slot->fresh()->status);

        Livewire::test(KalenderAdmin::class)
            ->call('toggleNonaktif', $slot->id);

        $this->assertSame('kosong', $slot->fresh()->status);
    }

    public function test_toggle_nonaktif_ditolak_untuk_slot_yang_sudah_booked(): void
    {
        $tenant = $this->tenant();
        app()->instance('tenant', $tenant);
        $lapangan = $this->buatLapangan($tenant);
        $slot = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'status' => 'booked',
        ]);

        Livewire::test(KalenderAdmin::class)
            ->call('toggleNonaktif', $slot->id)
            ->assertSet('pesanError', 'Slot yang sudah di-hold atau dibooking tidak bisa dinonaktifkan.');

        $this->assertSame('booked', $slot->fresh()->status);
    }

    public function test_simpan_harga_slot_memperbarui_harga(): void
    {
        $tenant = $this->tenant();
        app()->instance('tenant', $tenant);
        $lapangan = $this->buatLapangan($tenant);
        $slot = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'harga' => 100000,
        ]);

        Livewire::test(KalenderAdmin::class)
            ->call('bukaEditSlot', $slot->id)
            ->assertSet('editHarga', 100000)
            ->set('editHarga', 175000)
            ->call('simpanHargaSlot')
            ->assertSet('slotDiedit', null);

        $this->assertSame(175000, $slot->fresh()->harga);
    }

    public function test_tidak_bisa_edit_slot_milik_tenant_lain(): void
    {
        $tenant = $this->tenant();
        app()->instance('tenant', $tenant);

        $tenantLain = Tenant::factory()->create(['domain' => 'tenant-lain.test']);
        $lapanganLain = $this->buatLapangan($tenantLain);
        $slotLain = JadwalSlot::factory()->create([
            'tenant_id' => $tenantLain->id,
            'lapangan_id' => $lapanganLain->id,
        ]);

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(KalenderAdmin::class)->call('bukaEditSlot', $slotLain->id);
    }
}
