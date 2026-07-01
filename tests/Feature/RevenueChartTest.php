<?php

namespace Tests\Feature;

use App\Livewire\RevenueChart;
use App\Models\Booking;
use App\Models\Cabang;
use App\Models\JadwalSlot;
use App\Models\Lapangan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RevenueChartTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(): Tenant
    {
        $tenant = Tenant::where('domain', 'localhost')->firstOrFail();
        app()->instance('tenant', $tenant);

        return $tenant;
    }

    public function test_pendapatan_hanya_menghitung_booking_dikonfirmasi_atau_selesai(): void
    {
        $tenant = $this->tenant();
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        $lapangan = Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);
        $slot = JadwalSlot::factory()->create(['tenant_id' => $tenant->id, 'lapangan_id' => $lapangan->id]);

        Booking::factory()->create([
            'tenant_id' => $tenant->id,
            'slot_id' => $slot->id,
            'status_booking' => 'dikonfirmasi',
            'total_bayar' => 100000,
        ]);

        $slotMenunggu = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'tanggal' => now()->addDays(2)->toDateString(),
        ]);
        Booking::factory()->create([
            'tenant_id' => $tenant->id,
            'slot_id' => $slotMenunggu->id,
            'status_booking' => 'menunggu',
            'total_bayar' => 50000,
        ]);

        $hasil = Livewire::test(RevenueChart::class)->instance()->pendapatanMingguan();

        $this->assertSame(100000, array_sum($hasil));
    }

    public function test_pendapatan_bisa_difilter_per_cabang(): void
    {
        $tenant = $this->tenant();
        $cabangA = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        $cabangB = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        $lapanganA = Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabangA->id]);
        $lapanganB = Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabangB->id]);

        $slotA = JadwalSlot::factory()->create(['tenant_id' => $tenant->id, 'lapangan_id' => $lapanganA->id]);
        $slotB = JadwalSlot::factory()->create(['tenant_id' => $tenant->id, 'lapangan_id' => $lapanganB->id]);

        Booking::factory()->create([
            'tenant_id' => $tenant->id,
            'slot_id' => $slotA->id,
            'status_booking' => 'selesai',
            'total_bayar' => 70000,
        ]);
        Booking::factory()->create([
            'tenant_id' => $tenant->id,
            'slot_id' => $slotB->id,
            'status_booking' => 'selesai',
            'total_bayar' => 90000,
        ]);

        $hasil = Livewire::test(RevenueChart::class, ['cabangId' => $cabangA->id])->instance()->pendapatanMingguan();

        $this->assertSame(70000, array_sum($hasil));
    }
}
