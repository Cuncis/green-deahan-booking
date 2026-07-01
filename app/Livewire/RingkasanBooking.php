<?php

namespace App\Livewire;

use App\Models\KodePromo;
use Livewire\Attributes\On;
use Livewire\Component;

class RingkasanBooking extends Component
{
    public int $lapanganId;

    public int $harga = 0;

    public string $kodePromo = '';

    public ?bool $promoValid = null;

    public int $diskonJumlah = 0;

    public string $tipePembayaran = 'manual';

    public string $metodePembayaran = 'qris';

    public function mount(int $lapanganId): void
    {
        $tenant = app('tenant');

        $this->lapanganId = $lapanganId;
        $this->tipePembayaran = $tenant->punyaFitur('dp_pembayaran')
            ? 'dp'
            : ($tenant->punyaFitur('pembayaran_online') ? 'lunas' : 'manual');
    }

    #[On('slot-dipilih')]
    public function slotDipilih(int $slotId, string $tanggal, string $jamMulai, string $jamSelesai, int $harga): void
    {
        $this->harga = $harga;
        $this->diskonJumlah = $this->promoValid ? min($this->diskonJumlah, $this->harga) : 0;
        $this->kirimTotal();
    }

    #[On('lapangan-dipilih')]
    public function lapanganDipilih(int $lapanganId): void
    {
        $this->lapanganId = $lapanganId;
        $this->harga = 0;
        $this->kodePromo = '';
        $this->promoValid = null;
        $this->diskonJumlah = 0;
        $this->kirimTotal();
    }

    /**
     * Validasi kode promo langsung ke database, supaya diskon yang tampil
     * di ringkasan sudah pasti valid, tidak cuma cek ulang saat submit.
     */
    public function cekPromo(): void
    {
        $tenant = app('tenant');

        if ($this->kodePromo === '') {
            $this->promoValid = false;
            $this->diskonJumlah = 0;
            $this->kirimTotal();

            return;
        }

        $promo = KodePromo::where('tenant_id', $tenant->id)
            ->where('kode', strtoupper($this->kodePromo))
            ->where('status_aktif', true)
            ->whereDate('tanggal_mulai', '<=', now())
            ->whereDate('tanggal_berakhir', '>=', now())
            ->first();

        if (! $promo) {
            $this->promoValid = false;
            $this->diskonJumlah = 0;
            $this->kirimTotal();

            return;
        }

        $this->promoValid = true;
        $this->diskonJumlah = min(
            $promo->tipe_diskon === 'persen' ? (int) round($this->harga * $promo->nilai / 100) : $promo->nilai,
            $this->harga,
        );
        $this->kirimTotal();
    }

    public function pilihTipePembayaran(string $tipe): void
    {
        $this->tipePembayaran = $tipe;
        $this->kirimTotal();
    }

    public function pilihMetodePembayaran(string $metode): void
    {
        $this->metodePembayaran = $metode;
        $this->kirimTotal();
    }

    private function kirimTotal(): void
    {
        $totalSetelahDiskon = max($this->harga - $this->diskonJumlah, 0);
        $totalBayar = $this->tipePembayaran === 'dp'
            ? (int) round($totalSetelahDiskon * 0.5)
            : $totalSetelahDiskon;

        $this->dispatch(
            'ringkasan-berubah',
            tipePembayaran: $this->tipePembayaran,
            metodePembayaran: $this->metodePembayaran,
            kodePromo: $this->promoValid ? strtoupper($this->kodePromo) : null,
            diskonJumlah: $this->diskonJumlah,
            totalBayar: $totalBayar,
        );
    }

    public function render()
    {
        return view('livewire.ringkasan-booking', [
            'tenant' => app('tenant'),
        ]);
    }
}
