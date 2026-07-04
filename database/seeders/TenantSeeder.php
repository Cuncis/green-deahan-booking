<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Cabang;
use App\Models\Customer;
use App\Models\JadwalSlot;
use App\Models\KodePromo;
use App\Models\Lapangan;
use App\Models\Membership;
use App\Models\Staf;
use App\Models\Tenant;
use App\Models\TenantFitur;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder demo untuk tiga tenant contoh (Basic/Pro/Premium), dipisah per
 * domain supaya masing-masing tingkat paket bisa dicoba langsung tanpa
 * perlu setting fitur manual satu-satu.
 *
 * Jalankan: php artisan db:seed --class=TenantSeeder
 */
class TenantSeeder extends Seeder
{
    /**
     * Penghitung nomor urut slot per lapangan, supaya tanggal & jam yang
     * dibuat otomatis tidak bertabrakan dengan constraint unik
     * (lapangan_id, tanggal, jam_mulai).
     *
     * @var array<int, int>
     */
    private array $slotCounter = [];

    private int $customerCounter = 0;

    public function run(): void
    {
        $this->buatTenantBasic();
        $this->buatTenantPro();
        $this->buatTenantPremium();
    }

    private function buatTenantBasic(): void
    {
        $tenant = $this->buatTenant('basic.localhost', 'Lapangan Sederhana', 'basic');
        $this->buatOwnerStaf($tenant, 'owner@basic.localhost', 'Owner Basic');

        $cabang = Cabang::factory()->create([
            'tenant_id' => $tenant->id,
            'nama_cabang' => 'Cabang Utama',
        ]);

        $lapangan = Lapangan::factory()->create([
            'tenant_id' => $tenant->id,
            'cabang_id' => $cabang->id,
            'nama' => 'Lapangan Futsal A',
            'jenis_olahraga' => 'futsal',
        ]);

        $statusDummy = ['menunggu', 'dikonfirmasi', 'selesai', 'dibatalkan', 'dikonfirmasi'];

        foreach ($statusDummy as $status) {
            $this->buatBooking(
                tenant: $tenant,
                lapangan: $lapangan,
                statusBooking: $status,
                tipePembayaran: 'lunas',
            );
        }
    }

    private function buatTenantPro(): void
    {
        $tenant = $this->buatTenant('pro.localhost', 'Arena Sport Pro', 'pro');
        $this->buatOwnerStaf($tenant, 'owner@pro.localhost', 'Owner Pro');

        $cabang = Cabang::factory()->create([
            'tenant_id' => $tenant->id,
            'nama_cabang' => 'Cabang Utama',
        ]);

        $lapanganFutsalA = Lapangan::factory()->create([
            'tenant_id' => $tenant->id,
            'cabang_id' => $cabang->id,
            'nama' => 'Lapangan Futsal A',
            'jenis_olahraga' => 'futsal',
        ]);
        $lapanganFutsalB = Lapangan::factory()->create([
            'tenant_id' => $tenant->id,
            'cabang_id' => $cabang->id,
            'nama' => 'Lapangan Futsal B',
            'jenis_olahraga' => 'futsal',
        ]);
        $lapanganBadminton = Lapangan::factory()->create([
            'tenant_id' => $tenant->id,
            'cabang_id' => $cabang->id,
            'nama' => 'Lapangan Badminton',
            'jenis_olahraga' => 'badminton',
        ]);

        $kodePromo = KodePromo::create([
            'tenant_id' => $tenant->id,
            'kode' => 'SEPI20',
            'tipe_diskon' => 'persen',
            'nilai' => 20,
            'tanggal_mulai' => now()->subDays(7)->toDateString(),
            'tanggal_berakhir' => now()->addMonths(2)->toDateString(),
            'kuota' => null,
            'status_aktif' => true,
        ]);

        $daftarLapangan = [$lapanganFutsalA, $lapanganFutsalB, $lapanganBadminton];
        $statusSiklus = ['menunggu', 'dikonfirmasi', 'selesai', 'dibatalkan'];

        for ($i = 0; $i < 10; $i++) {
            $this->buatBooking(
                tenant: $tenant,
                lapangan: $daftarLapangan[$i % count($daftarLapangan)],
                statusBooking: $statusSiklus[$i % count($statusSiklus)],
                tipePembayaran: $i % 2 === 0 ? 'lunas' : 'dp',
                kodePromo: in_array($i, [1, 4, 7], true) ? $kodePromo : null,
            );
        }
    }

    private function buatTenantPremium(): void
    {
        $tenant = $this->buatTenant('premium.localhost', 'Champion Sport Center', 'premium');

        $cabangJakarta = Cabang::factory()->create([
            'tenant_id' => $tenant->id,
            'nama_cabang' => 'Cabang Jakarta',
            'kota' => 'Jakarta',
        ]);
        $cabangBekasi = Cabang::factory()->create([
            'tenant_id' => $tenant->id,
            'nama_cabang' => 'Cabang Bekasi',
            'kota' => 'Bekasi',
        ]);

        $lapanganJakartaFutsal = Lapangan::factory()->create([
            'tenant_id' => $tenant->id,
            'cabang_id' => $cabangJakarta->id,
            'nama' => 'Lapangan Futsal Jakarta',
            'jenis_olahraga' => 'futsal',
        ]);
        $lapanganJakartaBadminton = Lapangan::factory()->create([
            'tenant_id' => $tenant->id,
            'cabang_id' => $cabangJakarta->id,
            'nama' => 'Lapangan Badminton Jakarta',
            'jenis_olahraga' => 'badminton',
        ]);
        $lapanganBekasiFutsal = Lapangan::factory()->create([
            'tenant_id' => $tenant->id,
            'cabang_id' => $cabangBekasi->id,
            'nama' => 'Lapangan Futsal Bekasi',
            'jenis_olahraga' => 'futsal',
        ]);
        $lapanganBekasiTennis = Lapangan::factory()->create([
            'tenant_id' => $tenant->id,
            'cabang_id' => $cabangBekasi->id,
            'nama' => 'Lapangan Tennis Bekasi',
            'jenis_olahraga' => 'tennis',
        ]);

        // Member: 1 bronze, 1 silver, 1 gold.
        $memberBronze = $this->buatCustomer($tenant, 'Rian Bronze');
        $memberSilver = $this->buatCustomer($tenant, 'Sari Silver');
        $memberGold = $this->buatCustomer($tenant, 'Gilang Gold');

        $membershipBronze = Membership::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $memberBronze->id,
            'tier' => 'bronze',
            'total_booking' => 3,
        ]);
        $membershipSilver = Membership::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $memberSilver->id,
            'tier' => 'silver',
            'total_booking' => 8,
        ]);
        $membershipGold = Membership::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $memberGold->id,
            'tier' => 'gold',
            'total_booking' => 20,
        ]);

        // Staf: 1 owner, 1 manager per cabang (Jakarta & Bekasi), 1 staff.
        $this->buatStaf($tenant, 'owner@premium.localhost', 'Owner Premium', 'owner');
        $this->buatStaf($tenant, 'manager.jakarta@premium.localhost', 'Manager Cabang Jakarta', 'manager');
        $this->buatStaf($tenant, 'manager.bekasi@premium.localhost', 'Manager Cabang Bekasi', 'manager');
        $this->buatStaf($tenant, 'staff@premium.localhost', 'Staff Lapangan', 'staff');

        // 11 booking biasa, tersebar di 4 lapangan, 3 di antaranya milik member.
        $daftarLapangan = [$lapanganJakartaFutsal, $lapanganJakartaBadminton, $lapanganBekasiFutsal, $lapanganBekasiTennis];
        $statusSiklus = ['menunggu', 'dikonfirmasi', 'selesai', 'dibatalkan'];

        $customerPerIndex = [
            2 => [$memberBronze, $membershipBronze],
            5 => [$memberSilver, $membershipSilver],
            8 => [$memberGold, $membershipGold],
        ];

        for ($i = 0; $i < 11; $i++) {
            [$customer, $membership] = $customerPerIndex[$i] ?? [null, null];

            $this->buatBooking(
                tenant: $tenant,
                lapangan: $daftarLapangan[$i % count($daftarLapangan)],
                statusBooking: $statusSiklus[$i % count($statusSiklus)],
                tipePembayaran: $i % 3 === 0 ? 'dp' : 'lunas',
                customer: $customer,
                membership: $membership,
            );
        }

        // 4 booking berulang mingguan (booking_berulang), pelanggan yang sama
        // main tiap minggu di jam yang sama.
        $recurringGroupId = (string) Str::uuid();
        $pelangganRutin = $this->buatCustomer($tenant, 'Doni Pelanggan Rutin');

        for ($ke = 1; $ke <= 4; $ke++) {
            $this->buatBooking(
                tenant: $tenant,
                lapangan: $lapanganJakartaFutsal,
                statusBooking: $ke <= 2 ? 'selesai' : 'dikonfirmasi',
                tipePembayaran: 'lunas',
                customer: $pelangganRutin,
                tanggal: now()->addWeeks($ke - 1)->toDateString(),
                jamMulai: '19:00',
                recurringGroupId: $recurringGroupId,
                recurringKe: $ke,
            );
        }
    }

    /**
     * Buat tenant baru dengan preset fitur sesuai paket. Kalau domain ini
     * sudah pernah diseed sebelumnya, hapus dulu (cascade ke semua data
     * turunannya) supaya seeder ini aman dijalankan berulang kali.
     */
    private function buatTenant(string $domain, string $namaBisnis, string $paket): Tenant
    {
        Tenant::where('domain', $domain)->first()?->delete();

        $tenant = Tenant::factory()->create([
            'nama_bisnis' => $namaBisnis,
            'domain' => $domain,
            'paket' => $paket,
            'status_aktif' => true,
            'whatsapp_admin' => '6281200000001',
        ]);

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket($paket),
        ));

        return $tenant->refresh();
    }

    /**
     * Customer dengan nomor telepon deterministik (bukan acak) supaya
     * seeder idempotent, tidak membuat baris duplikat kalau dijalankan ulang.
     */
    private function buatCustomer(Tenant $tenant, string $nama): Customer
    {
        $this->customerCounter++;

        $kodePaket = match ($tenant->paket) {
            'pro' => '2',
            'premium' => '3',
            default => '1',
        };

        return Customer::firstOrCreate(
            ['no_telepon' => '081'.$kodePaket.sprintf('%07d', $this->customerCounter)],
            ['nama' => $nama],
        );
    }

    /**
     * Staf dengan role owner, dipakai supaya setiap tenant demo punya
     * minimal satu akun yang bisa login ke dashboard admin-nya sendiri
     * (perlu ada baris staf yang cocok, dicek middleware CheckTenantStaf).
     */
    private function buatOwnerStaf(Tenant $tenant, string $email, string $nama): Staf
    {
        return $this->buatStaf($tenant, $email, $nama, 'owner');
    }

    /**
     * Buat user (kalau belum ada) sekaligus baris staf yang menghubungkannya
     * ke tenant ini. email_verified_at diisi langsung supaya akun demo ini
     * bisa lolos middleware 'verified' tanpa perlu klik link verifikasi.
     */
    private function buatStaf(Tenant $tenant, string $email, string $nama, string $role): Staf
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            ['name' => $nama, 'password' => bcrypt('password'), 'email_verified_at' => now()],
        );

        return Staf::updateOrCreate(
            ['tenant_id' => $tenant->id, 'user_id' => $user->id],
            ['role' => $role, 'status_aktif' => true],
        );
    }

    /**
     * Buat satu booking dummy lengkap dengan jadwal_slot-nya. Tanggal dan
     * jam otomatis digilir per lapangan kalau tidak dioverride, supaya
     * tidak melanggar constraint unik (lapangan_id, tanggal, jam_mulai).
     */
    private function buatBooking(
        Tenant $tenant,
        Lapangan $lapangan,
        string $statusBooking,
        string $tipePembayaran,
        ?KodePromo $kodePromo = null,
        ?Customer $customer = null,
        ?Membership $membership = null,
        ?string $tanggal = null,
        ?string $jamMulai = null,
        ?string $recurringGroupId = null,
        ?int $recurringKe = null,
    ): Booking {
        $this->slotCounter[$lapangan->id] = ($this->slotCounter[$lapangan->id] ?? -1) + 1;
        $urutan = $this->slotCounter[$lapangan->id];

        $tanggal ??= now()->addDays($urutan % 14)->toDateString();
        $jamMulai ??= sprintf('%02d:00', 8 + ($urutan % 10));
        $jamSelesai = date('H:i', strtotime($jamMulai) + 3600);

        $harga = $lapangan->harga_per_jam;

        $slot = JadwalSlot::create([
            'tenant_id' => $tenant->id,
            'lapangan_id' => $lapangan->id,
            'tanggal' => $tanggal,
            'jam_mulai' => $jamMulai,
            'jam_selesai' => $jamSelesai,
            'harga' => $harga,
            'status' => $statusBooking === 'dibatalkan' ? 'kosong' : 'booked',
        ]);

        $customer ??= $this->buatCustomer($tenant, 'Customer Dummy');

        $diskonJumlah = 0;

        if ($kodePromo) {
            $diskonJumlah = $kodePromo->tipe_diskon === 'persen'
                ? (int) round($harga * $kodePromo->nilai / 100)
                : $kodePromo->nilai;
            $diskonJumlah = min($diskonJumlah, $harga);
        }

        $totalSetelahDiskon = $harga - $diskonJumlah;
        $totalBayar = $tipePembayaran === 'dp'
            ? (int) round($totalSetelahDiskon * 0.5)
            : $totalSetelahDiskon;

        return Booking::create([
            'tenant_id' => $tenant->id,
            'slot_id' => $slot->id,
            'customer_id' => $customer->id,
            'kode_promo_id' => $kodePromo?->id,
            'membership_id' => $membership?->id,
            'kode_booking' => Booking::generateKodeBooking(),
            'harga_normal' => $harga,
            'diskon_jumlah' => $diskonJumlah,
            'total_bayar' => $totalBayar,
            'tipe_pembayaran' => $tipePembayaran,
            'status_booking' => $statusBooking,
            'reminder_aktif' => $tenant->punyaFitur('reminder_otomatis'),
            'recurring_group_id' => $recurringGroupId,
            'recurring_ke' => $recurringKe,
        ]);
    }
}
