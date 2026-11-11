<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Cabang;
use App\Models\Customer;
use App\Models\JadwalSlot;
use App\Models\KodePromo;
use App\Models\Lapangan;
use App\Models\Membership;
use App\Models\Tenant;
use App\Models\TenantFitur;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Bagian berisiko halaman booking: token hold, perebutan slot, hold
 * kadaluarsa, slot yang sudah lewat, kuota promo, harga member, dan DP.
 */
class BookingAmanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partialMock(PaymentService::class, function ($mock) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('createInvoice')->andReturn([
                    'data' => ['id' => 'fake-invoice-id', 'link' => 'https://sandbox.mayar.club/invoice/fake-invoice-id'],
                ]);
        });
    }

    private function tenant(string $paket = 'premium'): Tenant
    {
        $tenant = Tenant::where('domain', 'localhost')->firstOrFail();
        TenantFitur::create(array_merge(['tenant_id' => $tenant->id], TenantFitur::presetUntukPaket($paket)));

        return $tenant;
    }

    private function lapangan(Tenant $tenant, array $override = []): Lapangan
    {
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id, 'jam_buka' => '08:00', 'jam_tutup' => '11:00']);

        return Lapangan::factory()->create(array_merge(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id, 'harga_per_jam' => 100000, 'status_aktif' => true], $override));
    }

    private function slot(Tenant $tenant, Lapangan $lapangan, array $override = []): JadwalSlot
    {
        return JadwalSlot::factory()->create(array_merge([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'tanggal' => now()->addDay()->toDateString(),
            'jam_mulai' => '10:00',
            'jam_selesai' => '11:00',
            'harga' => 100000,
            'status' => 'kosong',
        ], $override));
    }

    private function tahan(JadwalSlot $slot, array $body = []): TestResponse
    {
        return $this->postJson("/api/booking/slot/{$slot->id}/hold", $body);
    }

    /**
     * @param  array<string, mixed>  $override
     */
    private function pesan(JadwalSlot $slot, ?string $token, array $override = []): TestResponse
    {
        return $this->postJson('/api/booking', array_merge([
            'slot_id' => $slot->id,
            'hold_token' => $token,
            'nama' => 'Budi',
            'whatsapp' => '081234567890',
            'tipe_pembayaran' => 'lunas',
        ], $override));
    }

    public function test_hold_mengembalikan_token_dan_token_tidak_bocor_di_daftar_slot(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->lapangan($tenant);
        $slot = $this->slot($tenant, $lapangan);

        $token = $this->tahan($slot)->assertOk()->json('data.hold_token');

        $this->assertSame(40, strlen($token));
        $this->assertSame($token, $slot->fresh()->hold_token);

        $daftar = $this->getJson("/api/booking/lapangan/{$lapangan->id}/slot?tanggal=".now()->addDay()->toDateString());
        $daftar->assertOk()->assertJsonPath('data.0.status', 'terisi')->assertJsonPath('data.0.tersedia', false);
        $this->assertStringNotContainsString($token, $daftar->getContent());
    }

    public function test_dua_customer_berebut_slot_yang_sama_hanya_satu_yang_dapat(): void
    {
        $tenant = $this->tenant();
        $slot = $this->slot($tenant, $this->lapangan($tenant));

        $this->tahan($slot)->assertOk();
        $this->tahan($slot)->assertStatus(409)->assertJsonPath('message', 'Slot ini baru saja diambil orang lain, silakan pilih jam lain.');
    }

    public function test_booking_dengan_token_salah_atau_tanpa_token_ditolak(): void
    {
        $tenant = $this->tenant();
        $slot = $this->slot($tenant, $this->lapangan($tenant));
        $this->tahan($slot)->assertOk();

        $this->pesan($slot, 'token-orang-lain-yang-menebak-0000000000')->assertStatus(409);
        $this->pesan($slot, null)->assertStatus(422)->assertJsonValidationErrors('hold_token');

        $this->assertSame(0, Booking::count());
        $this->assertSame('hold', $slot->fresh()->status);
    }

    public function test_satu_hold_hanya_bisa_menghasilkan_satu_booking(): void
    {
        $tenant = $this->tenant();
        $slot = $this->slot($tenant, $this->lapangan($tenant));
        $token = $this->tahan($slot)->json('data.hold_token');

        $this->pesan($slot, $token)->assertCreated();
        $this->pesan($slot, $token)->assertStatus(409);

        $this->assertSame(1, Booking::count());
    }

    public function test_hold_kadaluarsa_tidak_bisa_dibooking_tapi_bisa_ditahan_customer_lain(): void
    {
        $tenant = $this->tenant();
        $slot = $this->slot($tenant, $this->lapangan($tenant), [
            'status' => 'hold',
            'hold_sampai' => now()->subMinute(),
            'hold_token' => 'token-lama-yang-sudah-kadaluarsa-000000000',
        ]);

        $this->pesan($slot, 'token-lama-yang-sudah-kadaluarsa-000000000')
            ->assertStatus(409)
            ->assertJsonPath('message', 'Waktu tahan slot sudah habis, silakan pilih jam lagi.');

        $tokenBaru = $this->tahan($slot)->assertOk()->json('data.hold_token');
        $this->assertNotSame('token-lama-yang-sudah-kadaluarsa-000000000', $tokenBaru);
    }

    public function test_slot_yang_booking_nya_sudah_dibuat_tidak_bisa_diambil_walau_hold_habis(): void
    {
        $tenant = $this->tenant();
        $slot = $this->slot($tenant, $this->lapangan($tenant), [
            'status' => 'hold',
            'hold_sampai' => now()->subMinute(),
            'hold_token' => null,
        ]);

        $this->tahan($slot)->assertStatus(409);
    }

    public function test_lepas_hold_butuh_token_yang_benar(): void
    {
        $tenant = $this->tenant();
        $slot = $this->slot($tenant, $this->lapangan($tenant));
        $token = $this->tahan($slot)->json('data.hold_token');

        $this->deleteJson("/api/booking/slot/{$slot->id}/hold", ['hold_token' => 'token-salah-000000000000000000000000000'])
            ->assertOk()->assertJsonPath('data.dilepas', false);
        $this->assertSame('hold', $slot->fresh()->status);

        $this->deleteJson("/api/booking/slot/{$slot->id}/hold", ['hold_token' => $token])
            ->assertOk()->assertJsonPath('data.dilepas', true);
        $this->assertSame('kosong', $slot->fresh()->status);
        $this->assertNull($slot->fresh()->hold_token);
    }

    public function test_ganti_jam_melepas_hold_sebelumnya_hanya_dengan_token_yang_benar(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->lapangan($tenant);
        $slotA = $this->slot($tenant, $lapangan);
        $slotB = $this->slot($tenant, $lapangan, ['jam_mulai' => '11:00', 'jam_selesai' => '12:00']);
        $slotC = $this->slot($tenant, $lapangan, ['jam_mulai' => '12:00', 'jam_selesai' => '13:00']);

        $tokenA = $this->tahan($slotA)->json('data.hold_token');

        $this->tahan($slotB, ['slot_sebelumnya' => $slotA->id, 'token_sebelumnya' => 'tebakan-000000000000000000000000000000'])->assertOk();
        $this->assertSame('hold', $slotA->fresh()->status, 'Hold orang lain tidak boleh terlepas dengan token tebakan.');

        $this->tahan($slotC, ['slot_sebelumnya' => $slotA->id, 'token_sebelumnya' => $tokenA])->assertOk();
        $this->assertSame('kosong', $slotA->fresh()->status);
    }

    public function test_slot_yang_jam_mulainya_sudah_lewat_tidak_bisa_ditahan(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->lapangan($tenant);
        $slot = $this->slot($tenant, $lapangan, ['tanggal' => now('Asia/Jakarta')->subDay()->toDateString()]);

        $this->tahan($slot)->assertStatus(409);
        $this->assertSame('kosong', $slot->fresh()->status);
    }

    public function test_daftar_slot_dibuat_otomatis_dan_tanggal_dibatasi(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->lapangan($tenant);
        $besok = now('Asia/Jakarta')->addDay()->toDateString();

        $this->getJson("/api/booking/lapangan/{$lapangan->id}/slot?tanggal={$besok}")
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.jam_mulai', '08:00')
            ->assertJsonPath('data.0.harga', 100000)
            ->assertJsonPath('data.0.tersedia', true);

        $kemarin = now('Asia/Jakarta')->subDay()->toDateString();
        $terlaluJauh = now('Asia/Jakarta')->addDays(31)->toDateString();
        $this->getJson("/api/booking/lapangan/{$lapangan->id}/slot?tanggal={$kemarin}")->assertStatus(422);
        $this->getJson("/api/booking/lapangan/{$lapangan->id}/slot?tanggal={$terlaluJauh}")->assertStatus(422);
        $this->assertSame(0, JadwalSlot::whereDate('tanggal', $terlaluJauh)->count());
    }

    public function test_daftar_slot_lapangan_nonaktif_atau_tenant_lain_404(): void
    {
        $tenant = $this->tenant();
        $nonaktif = $this->lapangan($tenant, ['status_aktif' => false]);
        $tenantLain = Tenant::factory()->create(['domain' => 'lain.test']);
        $cabangLain = Cabang::factory()->create(['tenant_id' => $tenantLain->id]);
        $lapanganLain = Lapangan::factory()->create(['tenant_id' => $tenantLain->id, 'cabang_id' => $cabangLain->id]);
        $besok = now('Asia/Jakarta')->addDay()->toDateString();

        $this->getJson("/api/booking/lapangan/{$nonaktif->id}/slot?tanggal={$besok}")->assertNotFound();
        $this->getJson("/api/booking/lapangan/{$lapanganLain->id}/slot?tanggal={$besok}")->assertNotFound();
        $this->assertSame(0, JadwalSlot::where('lapangan_id', $lapanganLain->id)->count());
    }

    public function test_slot_tenant_lain_tidak_bisa_ditahan(): void
    {
        $this->tenant();
        $tenantLain = Tenant::factory()->create(['domain' => 'lain.test']);
        $cabangLain = Cabang::factory()->create(['tenant_id' => $tenantLain->id]);
        $lapanganLain = Lapangan::factory()->create(['tenant_id' => $tenantLain->id, 'cabang_id' => $cabangLain->id]);
        $slotLain = $this->slot($tenantLain, $lapanganLain);

        $this->tahan($slotLain)->assertNotFound();
        $this->assertSame('kosong', $slotLain->fresh()->status);
    }

    public function test_kuota_promo_habis_tidak_memberi_diskon_dan_booking_batal_tidak_dihitung(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->lapangan($tenant);
        $promo = KodePromo::factory()->create(['tenant_id' => $tenant->id, 'kode' => 'HEMAT10', 'kuota' => 1]);
        $slotLama = $this->slot($tenant, $lapangan, ['jam_mulai' => '08:00', 'jam_selesai' => '09:00', 'status' => 'booked']);
        $bookingLama = Booking::factory()->create(['tenant_id' => $tenant->id, 'slot_id' => $slotLama->id, 'kode_promo_id' => $promo->id, 'status_booking' => 'dikonfirmasi']);

        $slot = $this->slot($tenant, $lapangan);
        $this->postJson('/api/booking/harga', ['slot_id' => $slot->id, 'kode_promo' => 'hemat10', 'tipe_pembayaran' => 'lunas'])
            ->assertOk()
            ->assertJsonPath('data.status_promo', 'habis')
            ->assertJsonPath('data.total_bayar', 100000);

        $token = $this->tahan($slot)->json('data.hold_token');
        $this->pesan($slot, $token, ['kode_promo' => 'HEMAT10'])
            ->assertCreated()
            ->assertJsonPath('data.kode_promo_id', null)
            ->assertJsonPath('data.total_bayar', 100000);

        $bookingLama->update(['status_booking' => 'dibatalkan']);
        $slotBaru = $this->slot($tenant, $lapangan, ['jam_mulai' => '09:00', 'jam_selesai' => '10:00']);
        $this->postJson('/api/booking/harga', ['slot_id' => $slotBaru->id, 'kode_promo' => 'HEMAT10', 'tipe_pembayaran' => 'lunas'])
            ->assertJsonPath('data.status_promo', 'valid')
            ->assertJsonPath('data.total_bayar', 90000);
    }

    public function test_harga_member_ditagih_lalu_promo_dari_harga_member_dan_ringkasan_sama_dengan_tagihan(): void
    {
        $tenant = $this->tenant();
        $slot = $this->slot($tenant, $this->lapangan($tenant));
        $customer = Customer::factory()->create(['no_telepon' => '081234567890']);
        $membership = Membership::factory()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'tier' => 'gold']);
        KodePromo::factory()->create(['tenant_id' => $tenant->id, 'kode' => 'HEMAT10', 'tipe_diskon' => 'persen', 'nilai' => 10]);

        // Gold 15%: 100.000 jadi 85.000, promo 10% dari 85.000 = 8.500, DP 50% dari 76.500.
        $ringkasan = $this->postJson('/api/booking/harga', [
            'slot_id' => $slot->id,
            'kode_promo' => 'HEMAT10',
            'tipe_pembayaran' => 'dp',
            'no_telepon' => '0812-3456-7890',
        ])->assertOk();

        $ringkasan->assertJsonPath('data.member.tier', 'Gold')
            ->assertJsonPath('data.diskon_member', 15000)
            ->assertJsonPath('data.diskon_promo', 8500)
            ->assertJsonPath('data.total_setelah_diskon', 76500)
            ->assertJsonPath('data.total_bayar', 38250);

        $token = $this->tahan($slot)->json('data.hold_token');
        $booking = $this->pesan($slot, $token, ['kode_promo' => 'HEMAT10', 'tipe_pembayaran' => 'dp', 'whatsapp' => '0812-3456-7890'])->assertCreated();

        $booking->assertJsonPath('data.total_bayar', $ringkasan->json('data.total_bayar'))
            ->assertJsonPath('data.diskon_jumlah', 23500)
            ->assertJsonPath('data.membership_id', $membership->id)
            ->assertJsonPath('data.customer_id', $customer->id);
    }

    public function test_harga_member_tidak_berlaku_kalau_fitur_membership_tidak_aktif(): void
    {
        $tenant = $this->tenant('pro');
        $slot = $this->slot($tenant, $this->lapangan($tenant));
        $customer = Customer::factory()->create(['no_telepon' => '081234567890']);
        Membership::factory()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'tier' => 'gold']);

        $token = $this->tahan($slot)->json('data.hold_token');
        $this->pesan($slot, $token)->assertCreated()->assertJsonPath('data.total_bayar', 100000)->assertJsonPath('data.membership_id', null);
    }

    public function test_dp_ditolak_kalau_tenant_tidak_punya_fitur_dp(): void
    {
        $tenant = $this->tenant('basic');
        $slot = $this->slot($tenant, $this->lapangan($tenant));
        $token = $this->tahan($slot)->json('data.hold_token');

        $this->pesan($slot, $token, ['tipe_pembayaran' => 'dp'])->assertStatus(422)->assertJsonValidationErrors('tipe_pembayaran');
        $this->postJson('/api/booking/harga', ['slot_id' => $slot->id, 'tipe_pembayaran' => 'dp'])->assertStatus(422);
        $this->assertSame(0, Booking::count());
    }

    public function test_customer_baru_disimpan_dengan_nomor_angka_saja(): void
    {
        $tenant = $this->tenant();
        $slot = $this->slot($tenant, $this->lapangan($tenant));
        $token = $this->tahan($slot)->json('data.hold_token');

        $this->pesan($slot, $token, ['whatsapp' => '+62 812-1111-2222'])->assertCreated();

        $this->assertDatabaseHas('customers', ['no_telepon' => '6281211112222']);
    }

    public function test_hitung_harga_slot_tenant_lain_404(): void
    {
        $this->tenant();
        $tenantLain = Tenant::factory()->create(['domain' => 'lain.test']);
        $cabangLain = Cabang::factory()->create(['tenant_id' => $tenantLain->id]);
        $lapanganLain = Lapangan::factory()->create(['tenant_id' => $tenantLain->id, 'cabang_id' => $cabangLain->id]);
        $slotLain = $this->slot($tenantLain, $lapanganLain);

        $this->postJson('/api/booking/harga', ['slot_id' => $slotLain->id, 'tipe_pembayaran' => 'lunas'])->assertNotFound();
    }
}
