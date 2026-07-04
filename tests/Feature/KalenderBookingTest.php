<?php

namespace Tests\Feature;

use App\Livewire\KalenderBooking;
use App\Models\Cabang;
use App\Models\JadwalSlot;
use App\Models\Lapangan;
use App\Models\Tenant;
use App\Models\TenantFitur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

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

    public function test_slot_default_dibuat_otomatis_sesuai_jam_operasional_cabang_kalau_belum_ada(): void
    {
        $tenant = $this->tenant();
        app()->instance('tenant', $tenant);

        $cabang = Cabang::factory()->create([
            'tenant_id' => $tenant->id,
            'jam_buka' => '07:00',
            'jam_tutup' => '21:00',
        ]);
        $lapangan = Lapangan::factory()->create([
            'tenant_id' => $tenant->id,
            'cabang_id' => $cabang->id,
            'harga_per_jam' => 90000,
        ]);

        $this->assertDatabaseCount('jadwal_slot', 0);

        Livewire::test(KalenderBooking::class, ['lapanganId' => $lapangan->id])
            ->assertSee('07:00')
            ->assertSee('20:00');

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
        app()->instance('tenant', $tenant);
        $lapangan = $this->buatLapangan($tenant);

        JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '09:00',
            'harga' => 50000,
        ]);

        Livewire::test(KalenderBooking::class, ['lapanganId' => $lapangan->id]);

        $this->assertDatabaseCount('jadwal_slot', 1);
    }

    public function test_slot_yang_tampil_hanya_milik_lapangan_dan_tanggal_yang_dipilih(): void
    {
        $tenant = $this->tenant();
        app()->instance('tenant', $tenant);
        $lapangan = $this->buatLapangan($tenant);

        $slotSesuai = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'tanggal' => now()->toDateString(),
        ]);

        JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'tanggal' => now()->addDay()->toDateString(),
        ]);

        Livewire::test(KalenderBooking::class, ['lapanganId' => $lapangan->id])
            ->assertSet('selectedDate', now()->toDateString())
            ->assertViewHas('tanggalPilihan')
            ->assertSee(substr($slotSesuai->jam_mulai, 0, 5));
    }

    public function test_pilih_tanggal_mereset_slot_terpilih(): void
    {
        $tenant = $this->tenant();
        app()->instance('tenant', $tenant);
        $lapangan = $this->buatLapangan($tenant);

        $slot = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'tanggal' => now()->toDateString(),
            'status' => 'kosong',
        ]);

        Livewire::test(KalenderBooking::class, ['lapanganId' => $lapangan->id])
            ->call('pilihSlot', $slot->id)
            ->assertSet('selectedSlot', $slot->id)
            ->call('pilihTanggal', now()->addDay()->toDateString())
            ->assertSet('selectedSlot', null)
            ->assertSet('harga', 0);
    }

    public function test_pilih_slot_yang_kosong_berhasil_hold_dan_dispatch_event(): void
    {
        $tenant = $this->tenant();
        app()->instance('tenant', $tenant);
        $lapangan = $this->buatLapangan($tenant);

        $slot = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'tanggal' => now()->toDateString(),
            'status' => 'kosong',
            'harga' => 120000,
        ]);

        Livewire::test(KalenderBooking::class, ['lapanganId' => $lapangan->id])
            ->call('pilihSlot', $slot->id)
            ->assertSet('selectedSlot', $slot->id)
            ->assertSet('harga', 120000)
            ->assertDispatched('slot-dipilih');

        $this->assertSame('hold', $slot->fresh()->status);
        $this->assertNotNull($slot->fresh()->hold_sampai);
    }

    public function test_pilih_slot_lain_melepas_hold_slot_sebelumnya(): void
    {
        $tenant = $this->tenant();
        app()->instance('tenant', $tenant);
        $lapangan = $this->buatLapangan($tenant);

        $slotLama = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '10:00',
            'status' => 'kosong',
        ]);

        $slotBaru = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '11:00',
            'status' => 'kosong',
        ]);

        Livewire::test(KalenderBooking::class, ['lapanganId' => $lapangan->id])
            ->call('pilihSlot', $slotLama->id)
            ->assertSet('selectedSlot', $slotLama->id)
            ->call('pilihSlot', $slotBaru->id)
            ->assertSet('selectedSlot', $slotBaru->id);

        $this->assertSame('kosong', $slotLama->fresh()->status);
        $this->assertNull($slotLama->fresh()->hold_sampai);
        $this->assertSame('hold', $slotBaru->fresh()->status);
    }

    public function test_pilih_slot_yang_sama_yang_sudah_dipilih_tidak_melakukan_apa_apa(): void
    {
        $tenant = $this->tenant();
        app()->instance('tenant', $tenant);
        $lapangan = $this->buatLapangan($tenant);

        $slot = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'tanggal' => now()->toDateString(),
            'status' => 'kosong',
        ]);

        Livewire::test(KalenderBooking::class, ['lapanganId' => $lapangan->id])
            ->call('pilihSlot', $slot->id)
            ->assertSet('selectedSlot', $slot->id)
            ->call('pilihSlot', $slot->id)
            ->assertSet('selectedSlot', $slot->id)
            ->assertSet('pesanError', null);

        $this->assertSame('hold', $slot->fresh()->status);
    }

    public function test_pilih_slot_yang_sudah_di_hold_menampilkan_pesan_error(): void
    {
        $tenant = $this->tenant();
        app()->instance('tenant', $tenant);
        $lapangan = $this->buatLapangan($tenant);

        $slot = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'tanggal' => now()->toDateString(),
            'status' => 'hold',
            'hold_sampai' => now()->addMinutes(10),
        ]);

        Livewire::test(KalenderBooking::class, ['lapanganId' => $lapangan->id])
            ->call('pilihSlot', $slot->id)
            ->assertSet('selectedSlot', null)
            ->assertSet('pesanError', 'Slot ini baru saja diambil orang lain, silakan pilih jam lain.')
            ->assertNotDispatched('slot-dipilih');
    }

    public function test_slot_milik_tenant_lain_tidak_ikut_tampil(): void
    {
        $tenant = $this->tenant();
        app()->instance('tenant', $tenant);
        $lapangan = $this->buatLapangan($tenant);

        $tenantLain = Tenant::factory()->create(['domain' => 'tenant-lain.test']);
        $lapanganLain = $this->buatLapangan($tenantLain);

        JadwalSlot::factory()->create([
            'tenant_id' => $tenantLain->id,
            'lapangan_id' => $lapanganLain->id,
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '09:00',
        ]);

        $slotSendiri = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '10:00',
        ]);

        $component = Livewire::test(KalenderBooking::class, ['lapanganId' => $lapangan->id]);

        $component->assertSee(substr($slotSendiri->jam_mulai, 0, 5));
    }

    public function test_dropdown_cabang_tampil_kalau_fitur_multi_cabang_aktif(): void
    {
        $tenant = $this->tenant();
        app()->instance('tenant', $tenant);

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('premium'),
        ));

        $cabangA = Cabang::factory()->create(['tenant_id' => $tenant->id, 'nama_cabang' => 'Cabang A']);
        $cabangB = Cabang::factory()->create(['tenant_id' => $tenant->id, 'nama_cabang' => 'Cabang B']);
        $lapangan = Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabangA->id]);
        Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabangB->id]);

        Livewire::test(KalenderBooking::class, ['lapanganId' => $lapangan->id])
            ->assertSet('selectedCabang', $cabangA->id)
            ->assertSee('Cabang A')
            ->assertSee('Cabang B');
    }

    public function test_dropdown_cabang_tidak_tampil_kalau_fitur_tidak_aktif(): void
    {
        $tenant = $this->tenant();
        app()->instance('tenant', $tenant);
        $lapangan = $this->buatLapangan($tenant);

        Livewire::test(KalenderBooking::class, ['lapanganId' => $lapangan->id])
            ->assertSet('selectedCabang', null)
            ->assertDontSee('Pilih Cabang');
    }

    public function test_pilih_cabang_memindahkan_lapangan_ke_cabang_lain_dan_dispatch_event(): void
    {
        $tenant = $this->tenant();
        app()->instance('tenant', $tenant);

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('premium'),
        ));

        $cabangA = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        $cabangB = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        $lapanganA = Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabangA->id]);
        $lapanganB = Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabangB->id]);

        Livewire::test(KalenderBooking::class, ['lapanganId' => $lapanganA->id])
            ->call('pilihCabang', $cabangB->id)
            ->assertSet('selectedCabang', $cabangB->id)
            ->assertSet('lapanganId', $lapanganB->id)
            ->assertDispatched('lapangan-dipilih');
    }
}
