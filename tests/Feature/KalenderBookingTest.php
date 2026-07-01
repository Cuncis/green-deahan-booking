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
