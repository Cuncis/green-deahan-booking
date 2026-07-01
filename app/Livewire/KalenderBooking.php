<?php

namespace App\Livewire;

use App\Models\JadwalSlot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class KalenderBooking extends Component
{
    public int $lapanganId;

    public string $selectedDate;

    public ?int $selectedSlot = null;

    public ?string $pesanError = null;

    public ?string $jamMulai = null;

    public ?string $jamSelesai = null;

    public int $harga = 0;

    public function mount(int $lapanganId): void
    {
        $this->lapanganId = $lapanganId;
        $this->selectedDate = now()->toDateString();
    }

    public function pilihTanggal(string $tanggal): void
    {
        $this->selectedDate = $tanggal;
        $this->selectedSlot = null;
        $this->pesanError = null;
        $this->updateSummary();
    }

    /**
     * Hold slot langsung lewat query (bukan HTTP call ke endpoint API) supaya
     * tidak perlu request bolak-balik ke diri sendiri. Pola locking-nya sama
     * persis dengan BookingController::holdSlot(), lihat references/anti-double-booking.md.
     */
    public function pilihSlot(int $slotId): void
    {
        $tenant = app('tenant');
        $this->pesanError = null;

        $slotTerpilih = DB::transaction(function () use ($tenant, $slotId) {
            $slot = JadwalSlot::where('tenant_id', $tenant->id)
                ->where('id', $slotId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($slot->status !== 'kosong') {
                return null;
            }

            $slot->update([
                'status' => 'hold',
                'hold_sampai' => now()->addMinutes(10),
            ]);

            return $slot;
        });

        if (! $slotTerpilih) {
            $this->pesanError = 'Slot ini baru saja diambil orang lain, silakan pilih jam lain.';
            $this->selectedSlot = null;
            $this->updateSummary();

            return;
        }

        $this->selectedSlot = $slotTerpilih->id;
        $this->jamMulai = $slotTerpilih->jam_mulai;
        $this->jamSelesai = $slotTerpilih->jam_selesai;
        $this->harga = $slotTerpilih->harga;

        $this->dispatch(
            'slot-dipilih',
            slotId: $this->selectedSlot,
            tanggal: $this->selectedDate,
            jamMulai: $this->jamMulai,
            jamSelesai: $this->jamSelesai,
            harga: $this->harga,
        );
    }

    public function updateSummary(): void
    {
        if (! $this->selectedSlot) {
            $this->jamMulai = null;
            $this->jamSelesai = null;
            $this->harga = 0;
        }
    }

    public function ambilSlot(): Collection
    {
        return JadwalSlot::where('tenant_id', app('tenant')->id)
            ->where('lapangan_id', $this->lapanganId)
            ->whereDate('tanggal', $this->selectedDate)
            ->orderBy('jam_mulai')
            ->get();
    }

    public function render()
    {
        return view('livewire.kalender-booking', [
            'tanggalPilihan' => collect(range(0, 6))->map(fn (int $i) => now()->addDays($i)),
            'slotTersedia' => $this->ambilSlot(),
        ]);
    }
}
