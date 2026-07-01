<?php

namespace Tests\Feature;

use App\Livewire\DashboardAdmin;
use App\Models\Booking;
use App\Models\Cabang;
use App\Models\JadwalSlot;
use App\Models\Lapangan;
use App\Models\Tenant;
use App\Models\TenantFitur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardAdminTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(): Tenant
    {
        $tenant = Tenant::where('domain', 'localhost')->firstOrFail();
        app()->instance('tenant', $tenant);

        return $tenant;
    }

    private function buatBooking(Tenant $tenant, array $slotOverride = [], array $bookingOverride = []): Booking
    {
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        $lapangan = Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);
        $slot = JadwalSlot::factory()->create(array_merge([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'tanggal' => now()->toDateString(),
        ], $slotOverride));

        return Booking::factory()->create(array_merge([
            'tenant_id' => $tenant->id,
            'slot_id' => $slot->id,
        ], $bookingOverride));
    }

    public function test_stat_cards_menghitung_booking_dengan_benar(): void
    {
        $tenant = $this->tenant();

        $this->buatBooking($tenant, ['tanggal' => now()->toDateString()], ['status_booking' => 'dikonfirmasi']);
        $this->buatBooking($tenant, ['tanggal' => now()->toDateString()], ['status_booking' => 'menunggu']);
        $this->buatBooking($tenant, ['tanggal' => now()->toDateString()], ['status_booking' => 'dibatalkan']);
        $this->buatBooking($tenant, ['tanggal' => now()->addWeeks(2)->toDateString()], ['status_booking' => 'dikonfirmasi']);

        Livewire::test(DashboardAdmin::class)
            ->assertSet('cabangId', null)
            ->assertViewHas('bookingHariIni', 2)
            ->assertViewHas('menungguKonfirmasi', 1)
            ->assertViewHas('bookingMingguIni', 2);
    }

    public function test_tingkat_keterisian_dihitung_dari_slot_minggu_ini(): void
    {
        $tenant = $this->tenant();
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        $lapangan = Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);

        foreach (['09:00', '10:00', '11:00'] as $jam) {
            JadwalSlot::factory()->create([
                'tenant_id' => $tenant->id,
                'lapangan_id' => $lapangan->id,
                'tanggal' => now()->toDateString(),
                'jam_mulai' => $jam,
                'status' => 'booked',
            ]);
        }

        JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'tanggal' => now()->toDateString(),
            'jam_mulai' => '12:00',
            'status' => 'kosong',
        ]);

        Livewire::test(DashboardAdmin::class)
            ->assertViewHas('tingkatKeterisian', 75);
    }

    public function test_grafik_pendapatan_disembunyikan_kalau_fitur_laporan_pendapatan_tidak_aktif(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('basic'),
        ));

        Livewire::test(DashboardAdmin::class)
            ->assertDontSee('Pendapatan 7 Hari Terakhir');
    }

    public function test_grafik_pendapatan_tampil_kalau_fitur_laporan_pendapatan_aktif(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('pro'),
        ));

        $this->buatBooking($tenant, [], ['status_booking' => 'dikonfirmasi', 'total_bayar' => 150000]);

        Livewire::test(DashboardAdmin::class)
            ->assertSee('Pendapatan 7 Hari Terakhir');
    }

    public function test_filter_cabang_disembunyikan_kalau_fitur_multi_cabang_tidak_aktif(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('pro'),
        ));

        Livewire::test(DashboardAdmin::class)
            ->assertDontSee('Filter Cabang');
    }

    public function test_filter_cabang_tampil_dan_berfungsi_kalau_fitur_multi_cabang_aktif(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('premium'),
        ));

        $cabangA = Cabang::factory()->create(['tenant_id' => $tenant->id, 'nama_cabang' => 'Cabang A']);
        $cabangB = Cabang::factory()->create(['tenant_id' => $tenant->id, 'nama_cabang' => 'Cabang B']);
        $lapanganA = Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabangA->id]);
        $lapanganB = Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabangB->id]);

        $slotA = JadwalSlot::factory()->create(['tenant_id' => $tenant->id, 'lapangan_id' => $lapanganA->id]);
        $slotB = JadwalSlot::factory()->create(['tenant_id' => $tenant->id, 'lapangan_id' => $lapanganB->id]);

        Booking::factory()->create(['tenant_id' => $tenant->id, 'slot_id' => $slotA->id]);
        Booking::factory()->create(['tenant_id' => $tenant->id, 'slot_id' => $slotB->id]);

        Livewire::test(DashboardAdmin::class)
            ->assertSee('Filter Cabang')
            ->assertViewHas('bookingTerbaru', fn ($bookings) => $bookings->count() === 2)
            ->set('cabangId', $cabangA->id)
            ->assertViewHas('bookingTerbaru', fn ($bookings) => $bookings->count() === 1);
    }

    public function test_booking_terbaru_menampilkan_link_wa_tanpa_emoji(): void
    {
        $tenant = $this->tenant();

        $booking = $this->buatBooking($tenant, [], []);
        $booking->customer->update(['no_telepon' => '081234567890']);

        $component = Livewire::test(DashboardAdmin::class);

        $component->assertSee('https://wa.me/081234567890', false);
        $component->assertDontSee('💬');
    }

    public function test_booking_dibatalkan_tetap_tampil_di_tabel_booking_terbaru(): void
    {
        $tenant = $this->tenant();

        $this->buatBooking($tenant, [], ['status_booking' => 'dibatalkan']);

        Livewire::test(DashboardAdmin::class)
            ->assertViewHas('bookingTerbaru', fn ($bookings) => $bookings->count() === 1);
    }
}
