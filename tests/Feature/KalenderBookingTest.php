<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\JadwalSlot;
use App\Models\Lapangan;
use App\Models\Tenant;
use App\Models\TenantFitur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Pilih cabang, lapangan, tanggal, dan jam di halaman booking customer.
 * Dulu Livewire KalenderBooking; sekarang data halaman (HalamanBookingController)
 * dan API slot/hold (BookingController + SlotBookingService).
 */
class KalenderBookingTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(): Tenant
    {
        return Tenant::where('domain', 'localhost')->firstOrFail();
    }

    private function buatLapangan(Tenant $tenant): Lapangan
    {
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);

        return Lapangan::factory()->create([
            'tenant_id' => $tenant->id,
            'cabang_id' => $cabang->id,
        ]);
    }

    private function besok(): string
    {
        return now('Asia/Jakarta')->addDay()->toDateString();
    }

    private function slot(Tenant $tenant, Lapangan $lapangan, array $override = []): JadwalSlot
    {
        return JadwalSlot::factory()->create(array_merge([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'tanggal' => $this->besok(),
            'status' => 'kosong',
        ], $override));
    }

    public function test_slot_default_dibuat_otomatis_sesuai_jam_operasional_cabang_kalau_belum_ada(): void
    {
        $tenant = $this->tenant();
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id, 'jam_buka' => '07:00', 'jam_tutup' => '21:00']);
        $lapangan = Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id, 'harga_per_jam' => 90000]);

        $this->assertDatabaseCount('jadwal_slot', 0);

        $this->getJson("/api/booking/lapangan/{$lapangan->id}/slot?tanggal={$this->besok()}")
            ->assertOk()
            ->assertJsonCount(14, 'data')
            ->assertJsonPath('data.0.jam_mulai', '07:00')
            ->assertJsonPath('data.13.jam_mulai', '20:00');

        $this->assertDatabaseCount('jadwal_slot', 14);
        $this->assertDatabaseHas('jadwal_slot', [
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'jam_mulai' => '07:00',
            'jam_selesai' => '08:00',
            'harga' => 90000,
            'status' => 'kosong',
        ]);
    }

    public function test_slot_tidak_digenerate_ulang_kalau_sudah_ada_slot_untuk_tanggal_itu(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->buatLapangan($tenant);
        $this->slot($tenant, $lapangan, ['jam_mulai' => '09:00', 'harga' => 50000]);

        $this->getJson("/api/booking/lapangan/{$lapangan->id}/slot?tanggal={$this->besok()}")->assertJsonCount(1, 'data');

        $this->assertDatabaseCount('jadwal_slot', 1);
    }

    public function test_slot_yang_tampil_hanya_milik_lapangan_dan_tanggal_yang_dipilih(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->buatLapangan($tenant);
        $lapanganLain = $this->buatLapangan($tenant);
        $slotSesuai = $this->slot($tenant, $lapangan);
        $this->slot($tenant, $lapangan, ['tanggal' => now('Asia/Jakarta')->addDays(2)->toDateString()]);
        $this->slot($tenant, $lapanganLain);

        $this->getJson("/api/booking/lapangan/{$lapangan->id}/slot?tanggal={$this->besok()}")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $slotSesuai->id);
    }

    public function test_ganti_tanggal_melepas_hold_lewat_token(): void
    {
        $tenant = $this->tenant();
        $slot = $this->slot($tenant, $this->buatLapangan($tenant));
        $token = $this->postJson("/api/booking/slot/{$slot->id}/hold")->json('data.hold_token');

        $this->deleteJson("/api/booking/slot/{$slot->id}/hold", ['hold_token' => $token])->assertJsonPath('data.dilepas', true);

        $this->assertSame('kosong', $slot->fresh()->status);
    }

    public function test_pilih_slot_yang_kosong_berhasil_hold(): void
    {
        $tenant = $this->tenant();
        $slot = $this->slot($tenant, $this->buatLapangan($tenant), ['harga' => 120000, 'jam_mulai' => '10:00', 'jam_selesai' => '11:00']);

        $this->postJson("/api/booking/slot/{$slot->id}/hold")
            ->assertOk()
            ->assertJsonPath('data.slot_id', $slot->id)
            ->assertJsonPath('data.harga', 120000)
            ->assertJsonPath('data.jam_mulai', '10:00')
            ->assertJsonPath('data.tanggal', $this->besok());

        $this->assertSame('hold', $slot->fresh()->status);
        $this->assertNotNull($slot->fresh()->hold_sampai);
    }

    public function test_pilih_slot_lain_melepas_hold_slot_sebelumnya(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->buatLapangan($tenant);
        $slotPertama = $this->slot($tenant, $lapangan, ['jam_mulai' => '10:00', 'jam_selesai' => '11:00']);
        $slotKedua = $this->slot($tenant, $lapangan, ['jam_mulai' => '11:00', 'jam_selesai' => '12:00']);

        $token = $this->postJson("/api/booking/slot/{$slotPertama->id}/hold")->json('data.hold_token');
        $this->postJson("/api/booking/slot/{$slotKedua->id}/hold", ['slot_sebelumnya' => $slotPertama->id, 'token_sebelumnya' => $token])->assertOk();

        $this->assertSame('kosong', $slotPertama->fresh()->status);
        $this->assertNull($slotPertama->fresh()->hold_sampai);
        $this->assertSame('hold', $slotKedua->fresh()->status);
    }

    public function test_menahan_lagi_slot_yang_sudah_ditahan_sendiri_tidak_melepasnya(): void
    {
        $tenant = $this->tenant();
        $slot = $this->slot($tenant, $this->buatLapangan($tenant));
        $token = $this->postJson("/api/booking/slot/{$slot->id}/hold")->json('data.hold_token');

        $this->postJson("/api/booking/slot/{$slot->id}/hold", ['slot_sebelumnya' => $slot->id, 'token_sebelumnya' => $token])->assertStatus(409);

        $this->assertSame('hold', $slot->fresh()->status);
        $this->assertSame($token, $slot->fresh()->hold_token);
    }

    public function test_pilih_slot_yang_sudah_di_hold_menampilkan_pesan_error(): void
    {
        $tenant = $this->tenant();
        $slot = $this->slot($tenant, $this->buatLapangan($tenant), [
            'status' => 'hold',
            'hold_sampai' => now()->addMinutes(10),
            'hold_token' => 'token-customer-lain-00000000000000000000',
        ]);

        $this->postJson("/api/booking/slot/{$slot->id}/hold")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Slot ini baru saja diambil orang lain, silakan pilih jam lain.');
    }

    public function test_slot_milik_tenant_lain_tidak_ikut_tampil(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->buatLapangan($tenant);
        $slotSendiri = $this->slot($tenant, $lapangan);

        $tenantLain = Tenant::factory()->create(['domain' => 'tenant-lain.test']);
        $cabangLain = Cabang::factory()->create(['tenant_id' => $tenantLain->id]);
        $lapanganLain = Lapangan::factory()->create(['tenant_id' => $tenantLain->id, 'cabang_id' => $cabangLain->id]);
        $this->slot($tenantLain, $lapanganLain);

        $this->getJson("/api/booking/lapangan/{$lapangan->id}/slot?tanggal={$this->besok()}")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $slotSendiri->id);
        $this->getJson("/api/booking/lapangan/{$lapanganLain->id}/slot?tanggal={$this->besok()}")->assertNotFound();
    }

    public function test_dropdown_cabang_tampil_kalau_fitur_multi_cabang_aktif(): void
    {
        $tenant = $this->tenant();
        TenantFitur::create(array_merge(['tenant_id' => $tenant->id], TenantFitur::presetUntukPaket('premium')));
        $cabangA = Cabang::factory()->create(['tenant_id' => $tenant->id, 'nama_cabang' => 'Cabang A']);
        $cabangB = Cabang::factory()->create(['tenant_id' => $tenant->id, 'nama_cabang' => 'Cabang B']);
        $lapanganA = Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabangA->id, 'nama' => 'A Lapangan']);
        Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabangB->id, 'nama' => 'B Lapangan']);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('fitur.multiCabang', true)
            ->where('cabang', [['id' => $cabangA->id, 'nama' => 'Cabang A'], ['id' => $cabangB->id, 'nama' => 'Cabang B']])
            ->where('lapanganAwal', $lapanganA->id)
        );
    }

    public function test_dropdown_cabang_tidak_tampil_kalau_fitur_tidak_aktif(): void
    {
        $tenant = $this->tenant();
        $this->buatLapangan($tenant);
        $this->buatLapangan($tenant);

        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('fitur.multiCabang', false)->where('cabang', []));
    }

    public function test_setiap_lapangan_membawa_cabangnya_supaya_ganti_cabang_memilih_lapangan_cabang_itu(): void
    {
        $tenant = $this->tenant();
        TenantFitur::create(array_merge(['tenant_id' => $tenant->id], TenantFitur::presetUntukPaket('premium')));
        $cabangA = Cabang::factory()->create(['tenant_id' => $tenant->id, 'nama_cabang' => 'Cabang A']);
        $cabangB = Cabang::factory()->create(['tenant_id' => $tenant->id, 'nama_cabang' => 'Cabang B', 'alamat' => 'Jl. Cabang B']);
        Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabangA->id, 'nama' => 'A Lapangan']);
        $lapanganB = Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabangB->id, 'nama' => 'B Lapangan']);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('lapangan.1.id', $lapanganB->id)
            ->where('lapangan.1.cabangId', $cabangB->id)
            ->where('lapangan.1.alamat', 'Jl. Cabang B')
        );
    }
}
