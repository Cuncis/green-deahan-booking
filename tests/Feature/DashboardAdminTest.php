<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Cabang;
use App\Models\JadwalSlot;
use App\Models\Lapangan;
use App\Models\Membership;
use App\Models\ReminderLog;
use App\Models\Staf;
use App\Models\Tenant;
use App\Models\TenantFitur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
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

    /**
     * Buka dashboard React sebagai owner tenant "localhost".
     *
     * @param  array<string, mixed>  $query
     */
    private function dashboard(array $query = []): TestResponse
    {
        $tenant = $this->tenant();
        $user = User::factory()->create();
        Staf::create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'role' => 'owner', 'status_aktif' => true]);

        return $this->actingAs($user)->get('/admin'.($query ? '?'.http_build_query($query) : ''))->assertOk();
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

        $this->dashboard()->assertInertia(fn (Assert $page) => $page
            ->component('admin/Dashboard')
            ->where('statistik.bookingHariIni', 2)
            ->where('statistik.menungguKonfirmasi', 1)
            ->where('statistik.bookingMingguIni', 2));
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

        $this->dashboard()->assertInertia(fn (Assert $page) => $page->where('statistik.tingkatKeterisian', 75));
    }

    public function test_statistik_basic_hanya_menampilkan_booking_hari_ini_dan_menunggu_konfirmasi(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('basic'),
        ));

        $this->buatBooking($tenant, ['tanggal' => now()->toDateString()], ['status_booking' => 'dikonfirmasi']);

        // Basic: kartu Minggu Ini & Keterisian tidak dikirim (null), halaman React menyembunyikannya.
        $this->dashboard()->assertInertia(fn (Assert $page) => $page
            ->where('statistik.bookingHariIni', 1)
            ->where('statistik.menungguKonfirmasi', 0)
            ->where('statistik.bookingMingguIni', null)
            ->where('statistik.tingkatKeterisian', null));
    }

    public function test_filter_cabang_disembunyikan_kalau_fitur_multi_cabang_tidak_aktif(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('pro'),
        ));

        $this->dashboard()->assertInertia(fn (Assert $page) => $page->where('filterCabang', null));
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

        $this->dashboard()->assertInertia(fn (Assert $page) => $page
            ->has('filterCabang.pilihan', 2)
            ->where('filterCabang.terpilih', null)
            ->has('bookingTerbaru', 2));

        $this->get('/admin?cabang='.$cabangA->id)->assertInertia(fn (Assert $page) => $page
            ->where('filterCabang.terpilih', $cabangA->id)
            ->has('bookingTerbaru', 1));
    }

    public function test_booking_terbaru_menampilkan_link_wa_tanpa_emoji(): void
    {
        $tenant = $this->tenant();

        $booking = $this->buatBooking($tenant, [], []);
        $booking->customer->update(['no_telepon' => '081234567890']);

        $response = $this->dashboard();

        $response->assertInertia(fn (Assert $page) => $page->where('bookingTerbaru.0.waUrl', 'https://wa.me/081234567890'));
        $response->assertDontSee('💬');
    }

    public function test_booking_dibatalkan_tetap_tampil_di_tabel_booking_terbaru(): void
    {
        $tenant = $this->tenant();

        $this->buatBooking($tenant, [], ['status_booking' => 'dibatalkan']);

        $this->dashboard()->assertInertia(fn (Assert $page) => $page
            ->has('bookingTerbaru', 1)
            ->where('bookingTerbaru.0.status', 'dibatalkan')
            ->where('bookingTerbaru.0.bisaDibatalkan', false));
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

        $this->dashboard()->assertInertia(fn (Assert $page) => $page->where('membership', fn ($ringkasan) => collect($ringkasan)->firstWhere('tier', 'gold')['jumlah'] === 2
            && collect($ringkasan)->firstWhere('tier', 'gold')['persen'] === 15
            && collect($ringkasan)->firstWhere('tier', 'silver')['jumlah'] === 1));
    }

    public function test_panel_membership_tersembunyi_kalau_fitur_tidak_aktif(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('pro'),
        ));

        $this->dashboard()->assertInertia(fn (Assert $page) => $page->where('membership', null));
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

        $this->dashboard()->assertInertia(fn (Assert $page) => $page->where('reminder', function ($daftar) {
            $statusList = collect($daftar)->pluck('status');

            return $statusList->contains('terjadwal') && $statusList->contains('terkirim');
        }));
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

        $this->dashboard()->assertInertia(fn (Assert $page) => $page->where('reminder', function ($daftar) use ($log) {
            $perluDikirim = collect($daftar)->firstWhere('status', 'perlu_dikirim');
            $nomor = preg_replace('/[^0-9]/', '', $log->booking->customer->no_telepon);

            return $perluDikirim
                && $perluDikirim['id'] === $log->id
                && $perluDikirim['waUrl'] === 'https://wa.me/'.$nomor.'?text='.urlencode($log->pesan)
                && $perluDikirim['tandaiUrl'] === route('admin.reminder.terkirim', $log->id);
        }));
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

        $this->dashboard()->assertInertia(fn (Assert $page) => $page->where('reminder', fn ($daftar) => ! collect($daftar)->pluck('status')->contains('perlu_dikirim')));
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

        $this->dashboard();
        $this->post(route('admin.reminder.terkirim', $log->id))->assertRedirect();

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

        TenantFitur::create(array_merge(['tenant_id' => $this->tenant()->id], TenantFitur::presetUntukPaket('premium')));

        $this->dashboard();
        $this->post(route('admin.reminder.terkirim', $logLain->id));

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

        $this->dashboard()->assertInertia(fn (Assert $page) => $page->where('perbandinganCabang.0.nama', 'Cabang Besar'));
    }

    public function test_panel_premium_tersembunyi_untuk_paket_pro(): void
    {
        $tenant = $this->tenant();

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('pro'),
        ));

        // Pro: data panel Premium tidak dikirim sama sekali.
        $this->dashboard()->assertInertia(fn (Assert $page) => $page
            ->where('perbandinganCabang', null)
            ->where('staf', null)
            ->where('membership', null)
            ->where('reminder', null));
    }

    public function test_filter_cabang_tenant_lain_diabaikan(): void
    {
        $tenant = $this->tenant();
        TenantFitur::create(array_merge(['tenant_id' => $tenant->id], TenantFitur::presetUntukPaket('premium')));
        $cabangLain = Cabang::factory()->create(['tenant_id' => Tenant::factory()->create()->id]);
        $this->buatBooking($tenant);

        $this->dashboard(['cabang' => $cabangLain->id])->assertInertia(fn (Assert $page) => $page
            ->where('filterCabang.terpilih', null)
            ->has('bookingTerbaru', 1));
    }

    public function test_tandai_terkirim_ditolak_kalau_paket_tidak_punya_reminder(): void
    {
        $tenant = $this->tenant();
        TenantFitur::create(array_merge(['tenant_id' => $tenant->id], TenantFitur::presetUntukPaket('pro')));
        $booking = $this->buatBooking($tenant, [], ['reminder_aktif' => true]);
        $log = ReminderLog::factory()->create(['tenant_id' => $tenant->id, 'booking_id' => $booking->id, 'status' => 'menunggu', 'waktu_kirim' => now()->subMinutes(5)]);

        $this->dashboard();
        $this->post(route('admin.reminder.terkirim', $log->id))->assertNotFound();

        $this->assertSame('menunggu', $log->fresh()->status);
    }
}
