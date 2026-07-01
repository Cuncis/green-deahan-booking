<?php

namespace Tests\Feature;

use App\Livewire\RingkasanBooking;
use App\Models\Cabang;
use App\Models\KodePromo;
use App\Models\Lapangan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RingkasanBookingTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(): Tenant
    {
        $tenant = Tenant::where('domain', 'localhost')->firstOrFail();
        app()->instance('tenant', $tenant);

        return $tenant;
    }

    private function buatLapangan(Tenant $tenant): Lapangan
    {
        $cabang = Cabang::factory()->create(['tenant_id' => $tenant->id]);

        return Lapangan::factory()->create(['tenant_id' => $tenant->id, 'cabang_id' => $cabang->id]);
    }

    public function test_cek_promo_valid_menghitung_diskon_dan_dispatch_event(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->buatLapangan($tenant);

        KodePromo::factory()->create([
            'tenant_id' => $tenant->id,
            'kode' => 'HEMAT10',
            'tipe_diskon' => 'persen',
            'nilai' => 10,
        ]);

        Livewire::test(RingkasanBooking::class, ['lapanganId' => $lapangan->id])
            ->dispatch('slot-dipilih', slotId: 1, tanggal: now()->toDateString(), jamMulai: '10:00', jamSelesai: '11:00', harga: 100000)
            ->set('kodePromo', 'hemat10')
            ->call('cekPromo')
            ->assertSet('promoValid', true)
            ->assertSet('diskonJumlah', 10000)
            ->assertDispatched('ringkasan-berubah', diskonJumlah: 10000, totalBayar: 90000);
    }

    public function test_cek_promo_tidak_valid_mengembalikan_status_gagal(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->buatLapangan($tenant);

        Livewire::test(RingkasanBooking::class, ['lapanganId' => $lapangan->id])
            ->set('kodePromo', 'TIDAKADA')
            ->call('cekPromo')
            ->assertSet('promoValid', false)
            ->assertSet('diskonJumlah', 0);
    }

    public function test_pilih_tipe_pembayaran_dp_menghitung_setengah_harga(): void
    {
        $tenant = $this->tenant();
        $lapangan = $this->buatLapangan($tenant);

        Livewire::test(RingkasanBooking::class, ['lapanganId' => $lapangan->id])
            ->dispatch('slot-dipilih', slotId: 1, tanggal: now()->toDateString(), jamMulai: '10:00', jamSelesai: '11:00', harga: 100000)
            ->call('pilihTipePembayaran', 'dp')
            ->assertDispatched('ringkasan-berubah', tipePembayaran: 'dp', totalBayar: 50000);
    }

    public function test_pindah_lapangan_mereset_promo_dan_harga(): void
    {
        $tenant = $this->tenant();
        $lapanganA = $this->buatLapangan($tenant);
        $lapanganB = $this->buatLapangan($tenant);

        KodePromo::factory()->create(['tenant_id' => $tenant->id, 'kode' => 'HEMAT10', 'tipe_diskon' => 'persen', 'nilai' => 10]);

        Livewire::test(RingkasanBooking::class, ['lapanganId' => $lapanganA->id])
            ->dispatch('slot-dipilih', slotId: 1, tanggal: now()->toDateString(), jamMulai: '10:00', jamSelesai: '11:00', harga: 100000)
            ->set('kodePromo', 'hemat10')
            ->call('cekPromo')
            ->assertSet('promoValid', true)
            ->dispatch('lapangan-dipilih', lapanganId: $lapanganB->id, nama: 'Lain', jenisOlahraga: 'futsal', hargaPerJam: 80000, cabangNama: 'X', cabangAlamat: 'Y', jamBuka: '08:00', jamTutup: '22:00')
            ->assertSet('lapanganId', $lapanganB->id)
            ->assertSet('harga', 0)
            ->assertSet('promoValid', null)
            ->assertSet('diskonJumlah', 0);
    }
}
