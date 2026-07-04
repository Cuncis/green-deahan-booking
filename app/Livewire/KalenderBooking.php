<?php

namespace App\Livewire;

use App\Models\Cabang;
use App\Models\JadwalSlot;
use App\Models\Lapangan;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class KalenderBooking extends Component
{
    public int $lapanganId;

    public ?int $selectedCabang = null;

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

        if (app('tenant')->punyaFitur('multi_cabang')) {
            $this->selectedCabang = Lapangan::where('tenant_id', app('tenant')->id)
                ->find($lapanganId)?->cabang_id;
        }
    }

    /**
     * Ganti cabang aktif lalu pindah ke lapangan pertama milik cabang itu,
     * supaya field tabs langsung menampilkan lapangan cabang yang dipilih.
     */
    public function pilihCabang(int $cabangId): void
    {
        $tenant = app('tenant');
        $this->selectedCabang = $cabangId;

        $lapanganBaru = Lapangan::where('tenant_id', $tenant->id)
            ->where('cabang_id', $cabangId)
            ->where('status_aktif', true)
            ->first();

        if ($lapanganBaru) {
            $this->pilihLapangan($lapanganBaru->id);
        }
    }

    public function pilihLapangan(int $lapanganId): void
    {
        $tenant = app('tenant');

        $lapangan = Lapangan::where('tenant_id', $tenant->id)->findOrFail($lapanganId);

        $this->lapanganId = $lapangan->id;
        $this->selectedSlot = null;
        $this->pesanError = null;
        $this->updateSummary();

        $this->dispatch(
            'lapangan-dipilih',
            lapanganId: $lapangan->id,
            nama: $lapangan->nama,
            jenisOlahraga: $lapangan->jenis_olahraga,
            hargaPerJam: $lapangan->harga_per_jam,
            cabangNama: $lapangan->cabang->nama_cabang,
            cabangAlamat: $lapangan->cabang->alamat,
            jamBuka: $lapangan->cabang->jam_buka,
            jamTutup: $lapangan->cabang->jam_tutup,
        );
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
        if ($slotId === $this->selectedSlot) {
            return;
        }

        $tenant = app('tenant');
        $this->pesanError = null;
        $slotSebelumnya = $this->selectedSlot;

        $slotTerpilih = DB::transaction(function () use ($tenant, $slotId, $slotSebelumnya) {
            // Lepas hold slot yang tadinya dipilih customer ini, supaya kalau dia
            // ganti pikiran pilih jam lain, slot lama langsung kosong lagi buat
            // customer lain, bukan tersandera diam-diam sampai 10 menit habis.
            if ($slotSebelumnya) {
                JadwalSlot::where('tenant_id', $tenant->id)
                    ->where('id', $slotSebelumnya)
                    ->where('status', 'hold')
                    ->lockForUpdate()
                    ->first()
                    ?->update(['status' => 'kosong', 'hold_sampai' => null]);
            }

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
        $tenant = app('tenant');

        $this->pastikanSlotDefaultAda($tenant->id, $this->lapanganId, $this->selectedDate);

        return JadwalSlot::where('tenant_id', $tenant->id)
            ->where('lapangan_id', $this->lapanganId)
            ->whereDate('tanggal', $this->selectedDate)
            ->orderBy('jam_mulai')
            ->get();
    }

    /**
     * Supaya customer tidak pernah lihat "belum ada jadwal" di tanggal
     * manapun, slot jam operasional (jam buka/tutup cabang, interval 1 jam)
     * dibuat otomatis begitu ada yang pertama kali buka tanggal itu, tanpa
     * admin harus generate manual dulu lewat halaman Jadwal. Admin tetap
     * bisa kustomisasi jam operasional lewat /admin/pengaturan (jam_buka/
     * jam_tutup per cabang) dan harga/nonaktifkan slot tertentu lewat
     * halaman Jadwal setelah slot ini muncul.
     */
    private function pastikanSlotDefaultAda(int $tenantId, int $lapanganId, string $tanggal): void
    {
        $sudahAda = JadwalSlot::where('tenant_id', $tenantId)
            ->where('lapangan_id', $lapanganId)
            ->whereDate('tanggal', $tanggal)
            ->exists();

        if ($sudahAda) {
            return;
        }

        $lapangan = Lapangan::where('tenant_id', $tenantId)->with('cabang')->find($lapanganId);

        if (! $lapangan) {
            return;
        }

        $jamMulai = Carbon::parse($lapangan->cabang->jam_buka);
        $jamTutup = Carbon::parse($lapangan->cabang->jam_tutup);

        while ($jamMulai->copy()->addHour()->lessThanOrEqualTo($jamTutup)) {
            $jamSelesaiSlot = $jamMulai->copy()->addHour();

            try {
                JadwalSlot::create([
                    'tenant_id' => $tenantId,
                    'lapangan_id' => $lapangan->id,
                    'tanggal' => $tanggal,
                    'jam_mulai' => $jamMulai->format('H:i'),
                    'jam_selesai' => $jamSelesaiSlot->format('H:i'),
                    'harga' => $lapangan->harga_per_jam,
                    'status' => 'kosong',
                ]);
            } catch (QueryException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
            }

            $jamMulai = $jamSelesaiSlot;
        }
    }

    public function render()
    {
        $tenant = app('tenant');
        $punyaMultiCabang = $tenant->punyaFitur('multi_cabang');

        return view('livewire.kalender-booking', [
            'tanggalPilihan' => collect(range(0, 6))->map(fn (int $i) => now()->addDays($i)),
            'slotTersedia' => $this->ambilSlot(),
            'punyaMultiCabang' => $punyaMultiCabang,
            'daftarCabang' => $punyaMultiCabang
                ? Cabang::where('tenant_id', $tenant->id)->where('status_aktif', true)->get()
                : collect(),
            'daftarLapangan' => $punyaMultiCabang
                ? Lapangan::where('tenant_id', $tenant->id)
                    ->where('status_aktif', true)
                    ->when($this->selectedCabang, fn ($q) => $q->where('cabang_id', $this->selectedCabang))
                    ->get()
                : collect(),
        ]);
    }
}
