<?php

namespace Tests\Feature;

use App\Livewire\DashboardAdmin;
use App\Models\Booking;
use App\Models\Cabang;
use App\Models\JadwalSlot;
use App\Models\Lapangan;
use App\Models\Membership;
use App\Models\ReminderLog;
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

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('pro'),
        ));

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

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('pro'),
        ));

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

    public function test_statistik_basic_hanya_menampilkan_booking_hari_ini_dan_menunggu_konfirmasi(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('basic'),
        ));

        $this->buatBooking($tenant, ['tanggal' => now()->toDateString()], ['status_booking' => 'dikonfirmasi']);

        Livewire::test(DashboardAdmin::class)
            ->assertViewHas('bookingHariIni', 1)
            ->assertViewHas('menungguKonfirmasi', 0)
            ->assertViewHas('bookingMingguIni', null)
            ->assertViewHas('tingkatKeterisian', null)
            ->assertSee('Booking Hari Ini')
            ->assertSee('Menunggu Konfirmasi')
            ->assertDontSee('Booking Minggu Ini')
            ->assertDontSee('Tingkat Keterisian');
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

    public function test_jam_ramai_disembunyikan_kalau_laporan_pendapatan_tidak_aktif(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('basic'),
        ));

        Livewire::test(DashboardAdmin::class)
            ->assertDontSee('Jam Ramai')
            ->assertViewHas('jamRamai', []);
    }

    public function test_jam_ramai_menghitung_slot_booked_per_jam(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('pro'),
        ));

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

        Livewire::test(DashboardAdmin::class)
            ->assertSee('Jam Ramai')
            ->assertViewHas('jamRamai', fn ($jamRamai) => $jamRamai[19] === 2 && $jamRamai[8] === 0);
    }

    public function test_kode_promo_manager_tampil_kalau_fitur_kode_promo_aktif(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('pro'),
        ));

        Livewire::test(DashboardAdmin::class)->assertSee('Kode Promo');
    }

    public function test_kode_promo_manager_tersembunyi_kalau_fitur_tidak_aktif(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('basic'),
        ));

        Livewire::test(DashboardAdmin::class)->assertDontSee('Kode Promo');
    }

    public function test_panel_membership_menampilkan_jumlah_dan_persen_diskon_per_tier(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('premium'),
        ));

        Membership::factory()->count(2)->create(['tenant_id' => $tenant->id, 'tier' => 'gold']);
        Membership::factory()->create(['tenant_id' => $tenant->id, 'tier' => 'silver']);

        Livewire::test(DashboardAdmin::class)
            ->assertSee('Membership Tiers')
            ->assertViewHas('ringkasanMembership', fn ($ringkasan) => collect($ringkasan)->firstWhere('tier', 'gold')['jumlah'] === 2
                && collect($ringkasan)->firstWhere('tier', 'gold')['persen'] === 15
                && collect($ringkasan)->firstWhere('tier', 'silver')['jumlah'] === 1);
    }

    public function test_panel_membership_tersembunyi_kalau_fitur_tidak_aktif(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('pro'),
        ));

        Livewire::test(DashboardAdmin::class)->assertDontSee('Membership Tiers');
    }

    public function test_reminder_menampilkan_booking_terjadwal_dan_yang_sudah_terkirim(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('premium'),
        ));

        $bookingTerjadwal = $this->buatBooking(
            $tenant,
            ['tanggal' => now()->addDay()->toDateString()],
            ['reminder_aktif' => true],
        );

        $bookingSudahDikirim = $this->buatBooking(
            $tenant,
            ['tanggal' => now()->addDay()->toDateString()],
            ['reminder_aktif' => true],
        );
        ReminderLog::factory()->create([
            'tenant_id' => $tenant->id,
            'booking_id' => $bookingSudahDikirim->id,
            'status' => 'terkirim',
        ]);

        Livewire::test(DashboardAdmin::class)
            ->assertSee('Reminder Otomatis')
            ->assertViewHas('daftarReminder', function ($daftar) {
                $statusList = collect($daftar)->pluck('status');

                return $statusList->contains('terjadwal') && $statusList->contains('terkirim');
            });
    }

    public function test_perbandingan_cabang_meranking_berdasarkan_pendapatan(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('premium'),
        ));

        $cabangKecil = Cabang::factory()->create(['tenant_id' => $tenant->id, 'nama_cabang' => 'Cabang Kecil']);
        $cabangBesar = Cabang::factory()->create(['tenant_id' => $tenant->id, 'nama_cabang' => 'Cabang Besar']);

        $lapanganKecil = Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabangKecil->id]);
        $lapanganBesar = Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabangBesar->id]);

        $this->buatBooking($tenant, ['lapangan_id' => $lapanganKecil->id], ['status_booking' => 'selesai', 'total_bayar' => 50000]);
        $slotBesar = JadwalSlot::factory()->create(['tenant_id' => $tenant->id, 'lapangan_id' => $lapanganBesar->id]);
        Booking::factory()->create(['tenant_id' => $tenant->id, 'slot_id' => $slotBesar->id, 'status_booking' => 'selesai', 'total_bayar' => 500000]);

        Livewire::test(DashboardAdmin::class)
            ->assertSee('Perbandingan Performa Cabang')
            ->assertViewHas('perbandinganCabang', fn ($hasil) => $hasil->first()['cabang']->nama_cabang === 'Cabang Besar');
    }

    public function test_panel_premium_tersembunyi_untuk_paket_pro(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('pro'),
        ));

        Livewire::test(DashboardAdmin::class)
            ->assertDontSee('Perbandingan Performa Cabang')
            ->assertDontSee('Staf & Operator', false)
            ->assertDontSee('Membership Tiers')
            ->assertDontSee('Reminder Otomatis');
    }
}
