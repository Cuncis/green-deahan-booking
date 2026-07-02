<?php

namespace Tests\Feature;

use App\Jobs\KirimNotifikasiWhatsApp;
use App\Models\Booking;
use App\Models\Cabang;
use App\Models\JadwalSlot;
use App\Models\Lapangan;
use App\Models\Staf;
use App\Models\Tenant;
use App\Models\TenantFitur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class BookingAdminControllerTest extends TestCase
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

    private function buatBooking(Tenant $tenant, array $slotOverride = [], array $bookingOverride = []): Booking
    {
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        $lapangan = Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);
        $slot = JadwalSlot::factory()->create(array_merge([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'status' => 'hold',
        ], $slotOverride));

        return Booking::factory()->create(array_merge([
            'tenant_id' => $tenant->id,
            'slot_id' => $slot->id,
            'status_booking' => 'menunggu',
        ], $bookingOverride));
    }

    public function test_admin_bisa_konfirmasi_booking_menunggu(): void
    {
        Bus::fake();

        $tenant = $this->tenant();
        $user = $this->staf($tenant);
        $booking = $this->buatBooking($tenant);

        $response = $this->actingAs($user)->post(route('admin.booking.confirm', $booking));

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('success');

        $booking->refresh();
        $this->assertSame('dikonfirmasi', $booking->status_booking);
        $this->assertSame('booked', $booking->slot->status);

        Bus::assertDispatched(
            KirimNotifikasiWhatsApp::class,
            fn ($job) => $job->booking->is($booking) && $job->tipe === 'dikonfirmasi',
        );
    }

    public function test_admin_konfirmasi_membuat_reminder_log_kalau_fitur_aktif(): void
    {
        Bus::fake();

        $tenant = $this->tenant();
        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('premium'),
        ));
        $user = $this->staf($tenant);
        $booking = $this->buatBooking($tenant, [
            'tanggal' => now()->addDay()->toDateString(),
            'jam_mulai' => '19:00',
        ]);

        $this->actingAs($user)->post(route('admin.booking.confirm', $booking));

        $this->assertDatabaseHas('reminder_log', [
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
        ]);
    }

    public function test_admin_tidak_bisa_konfirmasi_booking_yang_bukan_menunggu(): void
    {
        Bus::fake();

        $tenant = $this->tenant();
        $user = $this->staf($tenant);
        $booking = $this->buatBooking($tenant, [], ['status_booking' => 'dikonfirmasi']);

        $response = $this->actingAs($user)->post(route('admin.booking.confirm', $booking));

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('error');
        $this->assertSame('dikonfirmasi', $booking->fresh()->status_booking);

        Bus::assertNotDispatched(KirimNotifikasiWhatsApp::class);
    }

    public function test_admin_tidak_bisa_konfirmasi_booking_tenant_lain(): void
    {
        Bus::fake();

        $tenant = $this->tenant();
        $tenantLain = Tenant::factory()->create(['domain' => 'tenant-lain.test']);
        $user = $this->staf($tenant);
        $bookingTenantLain = $this->buatBooking($tenantLain);

        $response = $this->actingAs($user)->post(route('admin.booking.confirm', $bookingTenantLain));

        $response->assertNotFound();
        $this->assertSame('menunggu', $bookingTenantLain->fresh()->status_booking);

        Bus::assertNotDispatched(KirimNotifikasiWhatsApp::class);
    }

    public function test_admin_bisa_batalkan_booking(): void
    {
        Bus::fake();

        $tenant = $this->tenant();
        $user = $this->staf($tenant);
        $booking = $this->buatBooking($tenant);

        $response = $this->actingAs($user)->post(route('admin.booking.cancel', $booking), [
            'alasan' => 'Customer minta ganti jadwal',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('success');

        $booking->refresh();
        $this->assertSame('dibatalkan', $booking->status_booking);
        $this->assertSame('Customer minta ganti jadwal', $booking->alasan_pembatalan);
        $this->assertSame('kosong', $booking->slot->status);

        Bus::assertDispatched(
            KirimNotifikasiWhatsApp::class,
            fn ($job) => $job->booking->is($booking) && $job->tipe === 'dibatalkan',
        );
    }

    public function test_admin_bisa_batalkan_booking_yang_sudah_dikonfirmasi(): void
    {
        Bus::fake();

        $tenant = $this->tenant();
        $user = $this->staf($tenant);
        $booking = $this->buatBooking($tenant, ['status' => 'booked'], ['status_booking' => 'dikonfirmasi']);

        $response = $this->actingAs($user)->post(route('admin.booking.cancel', $booking));

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertSame('dibatalkan', $booking->fresh()->status_booking);
    }

    public function test_admin_tidak_bisa_batalkan_booking_yang_sudah_selesai(): void
    {
        Bus::fake();

        $tenant = $this->tenant();
        $user = $this->staf($tenant);
        $booking = $this->buatBooking($tenant, [], ['status_booking' => 'selesai']);

        $response = $this->actingAs($user)->post(route('admin.booking.cancel', $booking));

        $response->assertSessionHas('error');
        $this->assertSame('selesai', $booking->fresh()->status_booking);

        Bus::assertNotDispatched(KirimNotifikasiWhatsApp::class);
    }

    public function test_admin_tidak_bisa_batalkan_booking_tenant_lain(): void
    {
        Bus::fake();

        $tenant = $this->tenant();
        $tenantLain = Tenant::factory()->create(['domain' => 'tenant-lain.test']);
        $user = $this->staf($tenant);
        $bookingTenantLain = $this->buatBooking($tenantLain);

        $response = $this->actingAs($user)->post(route('admin.booking.cancel', $bookingTenantLain));

        $response->assertNotFound();
        $this->assertSame('menunggu', $bookingTenantLain->fresh()->status_booking);
    }
}
