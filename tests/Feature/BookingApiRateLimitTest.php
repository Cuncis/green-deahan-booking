<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\JadwalSlot;
use App\Models\Lapangan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class BookingApiRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        RateLimiter::clear('booking-slot:127.0.0.1');
        RateLimiter::clear('booking-hold:127.0.0.1');
        RateLimiter::clear('booking-store:127.0.0.1');

        parent::tearDown();
    }

    private function tenant(): Tenant
    {
        return Tenant::where('domain', 'localhost')->firstOrFail();
    }

    private function buatLapangan(Tenant $tenant): Lapangan
    {
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);

        return Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);
    }

    public function test_lihat_slot_kena_limit_setelah_60_request_per_menit(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->buatLapangan($tenant);

        for ($i = 0; $i < 60; $i++) {
            $this->getJson("/api/booking/lapangan/{$lapangan->id}/slot?tanggal=2026-08-01")->assertOk();
        }

        $response = $this->getJson("/api/booking/lapangan/{$lapangan->id}/slot?tanggal=2026-08-01");

        $response->assertStatus(429);
        $response->assertJson(['message' => 'Terlalu banyak permintaan, coba lagi sebentar lagi.']);
    }

    public function test_hold_slot_kena_limit_setelah_10_request_per_menit(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->buatLapangan($tenant);
        $slot = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'status' => 'booked',
        ]);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson("/api/booking/slot/{$slot->id}/hold");
        }

        $response = $this->postJson("/api/booking/slot/{$slot->id}/hold");

        $response->assertStatus(429);
        $response->assertJson(['message' => 'Terlalu banyak percobaan pilih jam, tunggu sebentar sebelum coba lagi.']);
    }

    public function test_buat_booking_kena_limit_setelah_5_request_per_menit(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->buatLapangan($tenant);
        $slot = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'status' => 'kosong',
        ]);

        $payload = [
            'slot_id' => $slot->id,
            'nama' => 'Budi',
            'whatsapp' => '081234567890',
            'tipe_pembayaran' => 'lunas',
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/booking', $payload);
        }

        $response = $this->postJson('/api/booking', $payload);

        $response->assertStatus(429);
        $response->assertJson(['message' => 'Terlalu banyak percobaan booking, tunggu sebentar sebelum coba lagi.']);
    }
}
