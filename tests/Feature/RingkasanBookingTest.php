<?php

namespace Tests\Feature;

use App\Models\Cabang;
use App\Models\JadwalSlot;
use App\Models\KodePromo;
use App\Models\Lapangan;
use App\Models\Tenant;
use App\Models\TenantFitur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ringkasan harga di halaman booking (kode promo, DP). Dulu Livewire
 * RingkasanBooking; sekarang POST /api/booking/harga (HargaBookingService).
 */
class RingkasanBookingTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(): Tenant
    {
        $tenant = Tenant::where('domain', 'localhost')->firstOrFail();
        TenantFitur::create(array_merge(['tenant_id' => $tenant->id], TenantFitur::presetUntukPaket('premium')));

        return $tenant;
    }

    private function slot(Tenant $tenant, int $harga): JadwalSlot
    {
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);
        $lapangan = Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);

        return JadwalSlot::factory()->create(['tenant_id' => $tenant->id, 'lapangan_id' => $lapangan->id, 'harga' => $harga]);
    }

    public function test_cek_promo_valid_menghitung_diskon(): void
    {
        $tenant = $this->tenant();
        $slot = $this->slot($tenant, 100000);
        KodePromo::factory()->create(['tenant_id' => $tenant->id, 'kode' => 'HEMAT10', 'tipe_diskon' => 'persen', 'nilai' => 10]);

        $this->postJson('/api/booking/harga', ['slot_id' => $slot->id, 'kode_promo' => 'hemat10', 'tipe_pembayaran' => 'lunas'])
            ->assertOk()
            ->assertJsonPath('data.status_promo', 'valid')
            ->assertJsonPath('data.kode_promo', 'HEMAT10')
            ->assertJsonPath('data.diskon_promo', 10000)
            ->assertJsonPath('data.total_bayar', 90000);
    }

    public function test_cek_promo_tidak_valid_mengembalikan_status_gagal(): void
    {
        $tenant = $this->tenant();
        $slot = $this->slot($tenant, 100000);

        $this->postJson('/api/booking/harga', ['slot_id' => $slot->id, 'kode_promo' => 'TIDAKADA', 'tipe_pembayaran' => 'lunas'])
            ->assertOk()
            ->assertJsonPath('data.status_promo', 'tidak_valid')
            ->assertJsonPath('data.diskon_promo', 0)
            ->assertJsonPath('data.total_bayar', 100000);
    }

    public function test_pilih_tipe_pembayaran_dp_menghitung_setengah_harga(): void
    {
        $tenant = $this->tenant();
        $slot = $this->slot($tenant, 100000);

        $this->postJson('/api/booking/harga', ['slot_id' => $slot->id, 'tipe_pembayaran' => 'dp'])
            ->assertOk()
            ->assertJsonPath('data.total_setelah_diskon', 100000)
            ->assertJsonPath('data.total_bayar', 50000);
    }

    public function test_harga_selalu_dari_slot_yang_dipilih_bukan_dari_browser(): void
    {
        $tenant = $this->tenant();
        $slotMurah = $this->slot($tenant, 80000);
        $slotMahal = $this->slot($tenant, 150000);

        $this->postJson('/api/booking/harga', ['slot_id' => $slotMurah->id, 'tipe_pembayaran' => 'lunas', 'harga' => 1])
            ->assertJsonPath('data.harga_normal', 80000)
            ->assertJsonPath('data.total_bayar', 80000);
        $this->postJson('/api/booking/harga', ['slot_id' => $slotMahal->id, 'tipe_pembayaran' => 'lunas'])
            ->assertJsonPath('data.total_bayar', 150000);
    }
}
