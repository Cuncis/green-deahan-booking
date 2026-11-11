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
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Halaman Laporan Pendapatan (LaporanAdminController, sebelumnya Livewire
 * RevenueChart).
 */
class RevenueChartTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(string $paket = 'pro'): Tenant
    {
        $tenant = Tenant::where('domain', 'localhost')->firstOrFail();
        TenantFitur::create(array_merge(['tenant_id' => $tenant->id], TenantFitur::presetUntukPaket($paket)));

        return $tenant;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function bukaLaporan(Tenant $tenant, array $query = []): TestResponse
    {
        $user = User::factory()->create();
        Staf::create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'role' => 'owner', 'status_aktif' => true]);

        return $this->actingAs($user)->get(route('admin.laporan', $query));
    }

    /**
     * @return array<int, array{tanggal: string, hari: string, pendapatan: int}>
     */
    private function pendapatan(TestResponse $response): array
    {
        return $response->inertiaProps('pendapatanMingguan');
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

        $hasil = $this->pendapatan($this->bukaLaporan($tenant)->assertOk());

        $this->assertCount(7, $hasil);
        $this->assertSame(100000, array_sum(array_column($hasil, 'pendapatan')));
    }

    public function test_pendapatan_bisa_difilter_per_cabang(): void
    {
        $tenant = $this->tenant('premium');
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

        $response = $this->bukaLaporan($tenant, ['cabang' => $cabangA->id]);

        $response->assertInertia(fn (Assert $page) => $page->where('filterCabang.terpilih', $cabangA->id));
        $this->assertSame(70000, array_sum(array_column($this->pendapatan($response), 'pendapatan')));
    }

    public function test_jam_ramai_menghitung_slot_booked_per_jam(): void
    {
        $tenant = $this->tenant();
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        $lapangan = Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);

        JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'tanggal' => now()->addDay()->toDateString(),
            'jam_mulai' => '19:00',
            'status' => 'booked',
        ]);
        JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'tanggal' => now()->addDays(2)->toDateString(),
            'jam_mulai' => '19:00',
            'status' => 'booked',
        ]);
        JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'tanggal' => now()->addDays(3)->toDateString(),
            'jam_mulai' => '08:00',
            'status' => 'kosong',
        ]);

        $this->bukaLaporan($tenant)->assertInertia(fn (Assert $page) => $page
            ->component('admin/Laporan')
            ->has('jamRamai', 24)
            ->where('jamRamai.19', 2)
            ->where('jamRamai.8', 0)
        );
    }

    public function test_filter_cabang_tampil_kalau_tenant_punya_fitur_multi_cabang(): void
    {
        $tenant = $this->tenant('premium');
        Cabang::factory()->count(2)->create(['tenant_id' => $tenant->id]);

        $this->bukaLaporan($tenant)->assertInertia(fn (Assert $page) => $page
            ->has('filterCabang.pilihan', 2)
            ->where('filterCabang.terpilih', null)
        );
    }

    public function test_filter_cabang_tersembunyi_kalau_tenant_tidak_punya_fitur_multi_cabang(): void
    {
        $tenant = $this->tenant('pro');
        Cabang::factory()->count(2)->create(['tenant_id' => $tenant->id]);

        $this->bukaLaporan($tenant, ['cabang' => 1])->assertInertia(fn (Assert $page) => $page->where('filterCabang', null));
    }

    public function test_filter_cabang_milik_tenant_lain_diabaikan(): void
    {
        $tenant = $this->tenant('premium');
        Cabang::factory()->create(['tenant_id' => $tenant->id]);
        $cabangLain = Cabang::factory()->create(['tenant_id' => Tenant::factory()->create(['domain' => 'lain.test'])->id]);

        $this->bukaLaporan($tenant, ['cabang' => $cabangLain->id])
            ->assertInertia(fn (Assert $page) => $page->where('filterCabang.terpilih', null));
    }

    public function test_laporan_404_untuk_tenant_basic(): void
    {
        $this->bukaLaporan($this->tenant('basic'))->assertNotFound();
    }
}
