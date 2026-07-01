<?php

namespace Tests\Feature;

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
use Tests\TestCase;

class BookingControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Semua test di sini bukan tentang integrasi Midtrans-nya sendiri (lihat
     * PaymentServiceTest untuk itu), jadi chargeMidtrans() di-mock supaya
     * tidak ada panggilan network sungguhan ke sandbox Midtrans tiap kali
     * suite ini jalan.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->partialMock(PaymentService::class, function ($mock) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('chargeMidtrans')->andReturn([
                    'transaction_id' => 'fake-transaction-id',
                    'actions' => [],
                    'va_numbers' => [['bank' => 'bca', 'va_number' => '1234567890']],
                ]);
        });
    }

    private function tenant(): Tenant
    {
        return Tenant::where('domain', 'localhost')->firstOrFail();
    }

    private function buatLapangan(Tenant $tenant): Lapangan
    {
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);

        return Lapangan::factory()->create([
            'tenant_id' => $tenant->id,
            'cabang_id' => $cabang->id,
        ]);
    }

    public function test_lihat_slot_mengembalikan_slot_milik_lapangan_pada_tanggal_tertentu(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->buatLapangan($tenant);

        $slotSesuai = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'tanggal' => '2026-08-01',
        ]);

        JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'tanggal' => '2026-08-02',
        ]);

        $response = $this->getJson("/api/booking/lapangan/{$lapangan->id}/slot?tanggal=2026-08-01");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $slotSesuai->id);
    }

    public function test_hold_slot_mengubah_status_kosong_jadi_hold(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->buatLapangan($tenant);

        $slot = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'status' => 'kosong',
        ]);

        $response = $this->postJson("/api/booking/slot/{$slot->id}/hold");

        $response->assertOk();
        $this->assertSame('hold', $slot->fresh()->status);
        $this->assertNotNull($slot->fresh()->hold_sampai);
    }

    public function test_hold_slot_mengembalikan_409_kalau_slot_sudah_tidak_kosong(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->buatLapangan($tenant);

        $slot = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'status' => 'hold',
            'hold_sampai' => now()->addMinutes(10),
        ]);

        $response = $this->postJson("/api/booking/slot/{$slot->id}/hold");

        $response->assertStatus(409);
        $this->assertSame('hold', $slot->fresh()->status);
    }

    public function test_buat_booking_berhasil_dan_membuat_customer_baru(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->buatLapangan($tenant);

        $slot = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'status' => 'hold',
            'hold_sampai' => now()->addMinutes(10),
            'harga' => 100000,
        ]);

        $response = $this->postJson('/api/booking', [
            'slot_id' => $slot->id,
            'nama' => 'Budi',
            'whatsapp' => '081234567890',
            'tipe_pembayaran' => 'lunas',
            'metode_pembayaran' => 'qris',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.harga_normal', 100000);
        $response->assertJsonPath('data.diskon_jumlah', 0);
        $response->assertJsonPath('data.total_bayar', 100000);
        $response->assertJsonPath('data.status_booking', 'menunggu');

        $this->assertDatabaseHas('customers', [
            'no_telepon' => '081234567890',
            'nama' => 'Budi',
        ]);
    }

    public function test_buat_booking_gagal_409_kalau_slot_bukan_hold(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->buatLapangan($tenant);

        $slot = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'status' => 'kosong',
        ]);

        $response = $this->postJson('/api/booking', [
            'slot_id' => $slot->id,
            'nama' => 'Budi',
            'whatsapp' => '081234567890',
            'tipe_pembayaran' => 'lunas',
            'metode_pembayaran' => 'qris',
        ]);

        $response->assertStatus(409);
        $this->assertDatabaseCount('booking', 0);
    }

    public function test_buat_booking_menghitung_diskon_dari_kode_promo_aktif(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->buatLapangan($tenant);

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('pro'),
        ));

        $kodePromo = KodePromo::factory()->create([
            'tenant_id' => $tenant->id,
            'kode' => 'HEMAT10',
            'tipe_diskon' => 'persen',
            'nilai' => 10,
        ]);

        $slot = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'status' => 'hold',
            'hold_sampai' => now()->addMinutes(10),
            'harga' => 100000,
        ]);

        $response = $this->postJson('/api/booking', [
            'slot_id' => $slot->id,
            'nama' => 'Budi',
            'whatsapp' => '081234567890',
            'kode_promo' => 'HEMAT10',
            'tipe_pembayaran' => 'lunas',
            'metode_pembayaran' => 'qris',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.diskon_jumlah', 10000);
        $response->assertJsonPath('data.total_bayar', 90000);
        $response->assertJsonPath('data.kode_promo_id', $kodePromo->id);
    }

    public function test_buat_booking_mengabaikan_kode_promo_kalau_fitur_tidak_aktif(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->buatLapangan($tenant);

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('basic'),
        ));

        KodePromo::factory()->create([
            'tenant_id' => $tenant->id,
            'kode' => 'HEMAT10',
            'tipe_diskon' => 'persen',
            'nilai' => 10,
        ]);

        $slot = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'status' => 'hold',
            'hold_sampai' => now()->addMinutes(10),
            'harga' => 100000,
        ]);

        $response = $this->postJson('/api/booking', [
            'slot_id' => $slot->id,
            'nama' => 'Budi',
            'whatsapp' => '081234567890',
            'kode_promo' => 'HEMAT10',
            'tipe_pembayaran' => 'lunas',
            'metode_pembayaran' => 'qris',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.diskon_jumlah', 0);
        $response->assertJsonPath('data.total_bayar', 100000);
        $response->assertJsonPath('data.kode_promo_id', null);
    }

    public function test_buat_booking_dp_menghasilkan_total_setengah_harga(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->buatLapangan($tenant);

        $slot = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'status' => 'hold',
            'hold_sampai' => now()->addMinutes(10),
            'harga' => 100000,
        ]);

        $response = $this->postJson('/api/booking', [
            'slot_id' => $slot->id,
            'nama' => 'Budi',
            'whatsapp' => '081234567890',
            'tipe_pembayaran' => 'dp',
            'metode_pembayaran' => 'va',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.total_bayar', 50000);
    }

    public function test_buat_booking_pakai_customer_yang_sudah_ada_berdasarkan_whatsapp(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->buatLapangan($tenant);

        $customer = Customer::factory()->create([
            'no_telepon' => '081234567890',
            'nama' => 'Budi Lama',
        ]);

        $slot = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'status' => 'hold',
            'hold_sampai' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/booking', [
            'slot_id' => $slot->id,
            'nama' => 'Nama Baru Diabaikan',
            'whatsapp' => '081234567890',
            'tipe_pembayaran' => 'lunas',
            'metode_pembayaran' => 'qris',
        ]);

        $response->assertCreated();
        $this->assertDatabaseCount('customers', 1);
        $response->assertJsonPath('data.customer_id', $customer->id);
    }

    public function test_cek_membership_mengembalikan_tier_dan_harga_member_kalau_customer_member(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->buatLapangan($tenant);
        $lapangan->update(['harga_per_jam' => 100000]);

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('premium'),
        ));

        $customer = Customer::factory()->create(['no_telepon' => '081234567890']);
        Membership::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'tier' => 'gold',
        ]);

        $response = $this->postJson('/api/booking/cek-membership', [
            'no_telepon' => '081234567890',
            'lapangan_id' => $lapangan->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.tier', 'Gold');
        $response->assertJsonPath('data.harga_member', 85000);
    }

    public function test_cek_membership_mengembalikan_null_kalau_customer_bukan_member(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->buatLapangan($tenant);

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('premium'),
        ));

        $response = $this->postJson('/api/booking/cek-membership', [
            'no_telepon' => '089900001111',
            'lapangan_id' => $lapangan->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data', null);
    }

    public function test_cek_membership_mengembalikan_null_kalau_fitur_sistem_membership_tidak_aktif(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->buatLapangan($tenant);

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('pro'),
        ));

        $customer = Customer::factory()->create(['no_telepon' => '081234567890']);
        Membership::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'tier' => 'gold',
        ]);

        $response = $this->postJson('/api/booking/cek-membership', [
            'no_telepon' => '081234567890',
            'lapangan_id' => $lapangan->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data', null);
    }

    public function test_buat_booking_menyimpan_reminder_aktif_kalau_fitur_aktif_dan_dipilih(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->buatLapangan($tenant);

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket('premium'),
        ));

        $slot = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'status' => 'hold',
            'hold_sampai' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/booking', [
            'slot_id' => $slot->id,
            'nama' => 'Budi',
            'whatsapp' => '081234567890',
            'tipe_pembayaran' => 'lunas',
            'metode_pembayaran' => 'qris',
            'reminder_aktif' => true,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('booking', [
            'id' => $response->json('data.id'),
            'reminder_aktif' => true,
        ]);
    }

    public function test_buat_booking_mengabaikan_reminder_aktif_kalau_fitur_tidak_aktif(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->buatLapangan($tenant);

        $slot = JadwalSlot::factory()->create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'status' => 'hold',
            'hold_sampai' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/booking', [
            'slot_id' => $slot->id,
            'nama' => 'Budi',
            'whatsapp' => '081234567890',
            'tipe_pembayaran' => 'lunas',
            'metode_pembayaran' => 'qris',
            'reminder_aktif' => true,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('booking', [
            'id' => $response->json('data.id'),
            'reminder_aktif' => false,
        ]);
    }
}
