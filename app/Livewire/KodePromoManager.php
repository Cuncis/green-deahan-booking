<?php

namespace App\Livewire;

use App\Models\KodePromo;
use Illuminate\Support\Collection;
use Livewire\Component;

class KodePromoManager extends Component
{
    public bool $tampilkanForm = false;

    public string $kode = '';

    public string $tipeDiskon = 'persen';

    public ?int $nilai = null;

    public string $tanggalMulai = '';

    public string $tanggalBerakhir = '';

    public ?int $kuota = null;

    public ?string $pesanError = null;

    public function bukaForm(): void
    {
        $this->reset(['kode', 'nilai', 'kuota', 'pesanError']);
        $this->tipeDiskon = 'persen';
        $this->tanggalMulai = now()->toDateString();
        $this->tanggalBerakhir = now()->addMonth()->toDateString();
        $this->tampilkanForm = true;
    }

    public function tutupForm(): void
    {
        $this->tampilkanForm = false;
    }

    public function simpan(): void
    {
        $tenant = app('tenant');

        $data = $this->validate([
            'kode' => ['required', 'string', 'max:50'],
            'tipeDiskon' => ['required', 'in:persen,nominal'],
            'nilai' => ['required', 'integer', 'min:1'],
            'tanggalMulai' => ['required', 'date'],
            'tanggalBerakhir' => ['required', 'date', 'after_or_equal:tanggalMulai'],
            'kuota' => ['nullable', 'integer', 'min:1'],
        ]);

        $kode = strtoupper($data['kode']);

        if (KodePromo::where('tenant_id', $tenant->id)->where('kode', $kode)->exists()) {
            $this->pesanError = 'Kode promo ini sudah dipakai, coba kode lain.';

            return;
        }

        KodePromo::create([
            'tenant_id' => $tenant->id,
            'kode' => $kode,
            'tipe_diskon' => $data['tipeDiskon'],
            'nilai' => $data['nilai'],
            'tanggal_mulai' => $data['tanggalMulai'],
            'tanggal_berakhir' => $data['tanggalBerakhir'],
            'kuota' => $data['kuota'],
            'status_aktif' => true,
        ]);

        $this->tampilkanForm = false;
    }

    /**
     * @return Collection<int, KodePromo>
     */
    public function daftarKodePromo(): Collection
    {
        return KodePromo::where('tenant_id', app('tenant')->id)
            ->latest()
            ->get();
    }

    public function render()
    {
        return view('livewire.kode-promo-manager', [
            'daftarKodePromo' => $this->daftarKodePromo(),
        ]);
    }
}
