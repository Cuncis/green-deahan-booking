<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\JadwalSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LepaskanSlotKadaluarsaTest extends TestCase
{
    use RefreshDatabase;

    public function test_slot_hold_yang_kadaluarsa_dilepas_kembali_ke_kosong(): void
    {
        $slot = JadwalSlot::factory()->create([
            'status' => 'hold',
            'hold_sampai' => now()->subMinutes(5),
        ]);

        $this->artisan('booking:lepas-slot-kadaluarsa')->assertSuccessful();

        $this->assertSame('kosong', $slot->fresh()->status);
        $this->assertNull($slot->fresh()->hold_sampai);
    }

    public function test_booking_menunggu_pada_slot_kadaluarsa_dibatalkan(): void
    {
        $slot = JadwalSlot::factory()->create([
            'status' => 'hold',
            'hold_sampai' => now()->subMinutes(5),
        ]);

        $booking = Booking::factory()->create([
            'tenant_id' => $slot->tenant_id,
            'slot_id' => $slot->id,
            'status_booking' => 'menunggu',
        ]);

        $this->artisan('booking:lepas-slot-kadaluarsa');

        $this->assertSame('dibatalkan', $booking->fresh()->status_booking);
    }

    public function test_slot_hold_yang_belum_kadaluarsa_tidak_disentuh(): void
    {
        $slot = JadwalSlot::factory()->create([
            'status' => 'hold',
            'hold_sampai' => now()->addMinutes(5),
        ]);

        $this->artisan('booking:lepas-slot-kadaluarsa');

        $this->assertSame('hold', $slot->fresh()->status);
        $this->assertNotNull($slot->fresh()->hold_sampai);
    }

    public function test_slot_yang_sudah_booked_tidak_disentuh(): void
    {
        $slot = JadwalSlot::factory()->create([
            'status' => 'booked',
            'hold_sampai' => now()->subMinutes(5),
        ]);

        $this->artisan('booking:lepas-slot-kadaluarsa');

        $this->assertSame('booked', $slot->fresh()->status);
    }

    public function test_booking_yang_sudah_dikonfirmasi_tidak_ikut_dibatalkan(): void
    {
        $slot = JadwalSlot::factory()->create([
            'status' => 'hold',
            'hold_sampai' => now()->subMinutes(5),
        ]);

        $booking = Booking::factory()->create([
            'tenant_id' => $slot->tenant_id,
            'slot_id' => $slot->id,
            'status_booking' => 'dikonfirmasi',
        ]);

        $this->artisan('booking:lepas-slot-kadaluarsa');

        $this->assertSame('dikonfirmasi', $booking->fresh()->status_booking);
    }
}
