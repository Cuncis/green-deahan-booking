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

    public function test_reminder_yang_sudah_lewat_waktu_kirim_tampil_perlu_dikirim(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('premium'),
        ));

        $booking = $this->buatBooking($tenant, [], ['reminder_aktif' => true]);
        $log = ReminderLog::factory()->create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'status' => 'menunggu',
            'waktu_kirim' => now()->subMinutes(10),
            'pesan' => 'Reminder: jadwal main kamu kurang dari 2 jam lagi.',
        ]);

        Livewire::test(DashboardAdmin::class)
            ->assertSee('Perlu Dikirim')
            ->assertViewHas('daftarReminder', function ($daftar) use ($log) {
                $perluDikirim = collect($daftar)->firstWhere('status', 'perlu_dikirim');

                return $perluDikirim
                    && $perluDikirim['id'] === $log->id
                    && $perluDikirim['no_telepon'] === $log->booking->customer->no_telepon
                    && $perluDikirim['pesan'] === $log->pesan;
            });
    }

    public function test_reminder_yang_belum_waktunya_tampil_terjadwal_bukan_perlu_dikirim(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('premium'),
        ));

        $booking = $this->buatBooking($tenant, [], ['reminder_aktif' => true]);
        ReminderLog::factory()->create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'status' => 'menunggu',
            'waktu_kirim' => now()->addHours(3),
        ]);

        Livewire::test(DashboardAdmin::class)
            ->assertViewHas('daftarReminder', function ($daftar) {
                $statusList = collect($daftar)->pluck('status');

                return ! $statusList->contains('perlu_dikirim');
            });
    }

    public function test_tandai_terkirim_mengubah_status_reminder_log(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('premium'),
        ));

        $booking = $this->buatBooking($tenant, [], ['reminder_aktif' => true]);
        $log = ReminderLog::factory()->create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'status' => 'menunggu',
            'waktu_kirim' => now()->subMinutes(5),
        ]);

        Livewire::test(DashboardAdmin::class)->call('tandaiTerkirim', $log->id);

        $this->assertSame('terkirim', $log->fresh()->status);
    }

    public function test_tandai_terkirim_tidak_bisa_untuk_reminder_tenant_lain(): void
    {
        $this->tenant();

        $tenantLain = Tenant::factory()->create();
        $bookingLain = $this->buatBooking($tenantLain, [], ['reminder_aktif' => true]);
        $logLain = ReminderLog::factory()->create([
            'tenant_id' => $tenantLain->id,
            'booking_id' => $bookingLain->id,
            'status' => 'menunggu',
            'waktu_kirim' => now()->subMinutes(5),
        ]);

        Livewire::test(DashboardAdmin::class)->call('tandaiTerkirim', $logLain->id);

        $this->assertSame('menunggu', $logLain->fresh()->status);
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
