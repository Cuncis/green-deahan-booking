<?php

namespace App\Livewire;

use App\Models\JadwalSlot;
use App\Models\Lapangan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class KalenderAdmin extends Component
{
    public ?int $lapanganId = null;

    public string $mingguAwal;

    public ?string $tanggalDipilih = null;

    public bool $tampilkanGenerateForm = false;

    public ?int $genLapanganId = null;

    public string $genTanggalMulai = '';

    public string $genTanggalSelesai = '';

    public float $genIntervalJam = 1;

    public ?int $genHarga = null;

    public ?string $pesanGenerate = null;

    public ?int $slotDiedit = null;

    public ?int $editHarga = null;

    public ?string $pesanError = null;

    public function mount(): void
    {
        $this->mingguAwal = now()->startOfWeek()->toDateString();
        $this->tanggalDipilih = now()->toDateString();
    }

    public function pilihTanggal(string $tanggal): void
    {
        $this->tanggalDipilih = $tanggal;
        $this->slotDiedit = null;
        $this->pesanError = null;
    }

    public function mingguSebelumnya(): void
    {
        $this->mingguAwal = Carbon::parse($this->mingguAwal)->subWeek()->toDateString();
    }

    public function mingguBerikutnya(): void
    {
        $this->mingguAwal = Carbon::parse($this->mingguAwal)->addWeek()->toDateString();
    }

    public function bukaFormGenerate(): void
    {
        $tenant = app('tenant');

        $this->genLapanganId = $this->lapanganId ?? Lapangan::where('tenant_id', $tenant->id)->value('id');
        $this->genTanggalMulai = $this->tanggalDipilih ?? now()->toDateString();
        $this->genTanggalSelesai = $this->genTanggalMulai;
        $this->genIntervalJam = 1;
        $this->genHarga = null;
        $this->pesanGenerate = null;
        $this->tampilkanGenerateForm = true;
    }

    public function tutupFormGenerate(): void
    {
        $this->tampilkanGenerateForm = false;
    }

    /**
     * Generate slot otomatis untuk satu lapangan dalam rentang tanggal
     * tertentu, memakai jam buka/tutup cabang lapangan itu sebagai batas
     * dan interval yang dipilih admin sebagai durasi tiap slot. Slot yang
     * kombinasi tanggal+jam-nya sudah ada dilewati (bukan dianggap error),
     * constraint unik di database (references/anti-double-booking.md) jadi
     * pengaman terakhir kalau ada percobaan generate yang tumpang tindih.
     */
    public function generateSlot(): void
    {
        $tenant = app('tenant');

        $data = $this->validate([
            'genLapanganId' => ['required', Rule::exists('lapangan', 'id')->where('tenant_id', $tenant->id)],
            'genTanggalMulai' => ['required', 'date'],
            'genTanggalSelesai' => ['required', 'date', 'after_or_equal:genTanggalMulai'],
            'genIntervalJam' => ['required', 'numeric', 'min:0.5', 'max:6'],
            'genHarga' => ['required', 'integer', 'min:0'],
        ]);

        $lapangan = Lapangan::where('tenant_id', $tenant->id)->with('cabang')->findOrFail($data['genLapanganId']);

        $intervalMenit = (int) round($data['genIntervalJam'] * 60);
        $tanggalMulai = Carbon::parse($data['genTanggalMulai']);
        $tanggalSelesai = Carbon::parse($data['genTanggalSelesai']);

        $dibuat = 0;
        $dilewati = 0;

        for ($tanggal = $tanggalMulai->copy(); $tanggal->lessThanOrEqualTo($tanggalSelesai); $tanggal->addDay()) {
            $jamMulai = Carbon::parse($lapangan->cabang->jam_buka);
            $batasTutup = Carbon::parse($lapangan->cabang->jam_tutup);

            while ($jamMulai->copy()->addMinutes($intervalMenit)->lessThanOrEqualTo($batasTutup)) {
                $jamSelesaiSlot = $jamMulai->copy()->addMinutes($intervalMenit);

                try {
                    JadwalSlot::create([
                        'tenant_id' => $tenant->id,
                        'lapangan_id' => $lapangan->id,
                        'tanggal' => $tanggal->toDateString(),
                        'jam_mulai' => $jamMulai->format('H:i'),
                        'jam_selesai' => $jamSelesaiSlot->format('H:i'),
                        'harga' => $data['genHarga'],
                        'status' => 'kosong',
                    ]);
                    $dibuat++;
                } catch (QueryException $e) {
                    if ($e->getCode() !== '23000') {
                        throw $e;
                    }

                    $dilewati++;
                }

                $jamMulai = $jamSelesaiSlot;
            }
        }

        $this->pesanGenerate = "{$dibuat} slot berhasil dibuat".($dilewati > 0 ? ", {$dilewati} slot dilewati karena sudah ada." : '.');
        $this->tampilkanGenerateForm = false;
    }

    public function bukaEditSlot(int $slotId): void
    {
        $tenant = app('tenant');

        $slot = JadwalSlot::where('tenant_id', $tenant->id)->findOrFail($slotId);

        $this->slotDiedit = $slot->id;
        $this->editHarga = $slot->harga;
        $this->pesanError = null;
    }

    public function tutupEditSlot(): void
    {
        $this->slotDiedit = null;
    }

    public function simpanHargaSlot(): void
    {
        $tenant = app('tenant');

        $data = $this->validate([
            'editHarga' => ['required', 'integer', 'min:0'],
        ]);

        JadwalSlot::where('tenant_id', $tenant->id)
            ->where('id', $this->slotDiedit)
            ->update(['harga' => $data['editHarga']]);

        $this->slotDiedit = null;
    }

    /**
     * Nonaktifkan atau aktifkan kembali slot kosong. Slot yang sedang
     * di-hold atau sudah booked tidak boleh diutak-atik lewat sini, supaya
     * tidak melanggar alur status kosong -> hold -> booked yang wajib
     * diikuti (references/anti-double-booking.md).
     */
    public function toggleNonaktif(int $slotId): void
    {
        $tenant = app('tenant');

        $berhasil = DB::transaction(function () use ($tenant, $slotId) {
            $slot = JadwalSlot::where('tenant_id', $tenant->id)
                ->where('id', $slotId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($slot->status, ['kosong', 'nonaktif'], true)) {
                return false;
            }

            $slot->update(['status' => $slot->status === 'kosong' ? 'nonaktif' : 'kosong']);

            return true;
        });

        $this->pesanError = $berhasil ? null : 'Slot yang sudah di-hold atau dibooking tidak bisa dinonaktifkan.';
    }

    private function slotQuery(): Builder
    {
        $tenant = app('tenant');

        return JadwalSlot::where('tenant_id', $tenant->id)
            ->when($this->lapanganId, fn ($q) => $q->where('lapangan_id', $this->lapanganId));
    }

    /**
     * @return array<int, array{tanggal: string, label: string, total: int, terisi: int}>
     */
    public function ringkasanMingguan(): array
    {
        return collect(range(0, 6))
            ->map(fn (int $i) => Carbon::parse($this->mingguAwal)->addDays($i))
            ->map(function (Carbon $tanggal) {
                $query = $this->slotQuery()->whereDate('tanggal', $tanggal->toDateString());

                return [
                    'tanggal' => $tanggal->toDateString(),
                    'label' => $tanggal->translatedFormat('D'),
                    'tanggalPendek' => $tanggal->format('d/m'),
                    'total' => (clone $query)->count(),
                    'terisi' => (clone $query)->where('status', 'booked')->count(),
                ];
            })
            ->all();
    }

    /**
     * @return Collection<int, JadwalSlot>
     */
    public function slotTanggalDipilih(): Collection
    {
        if (! $this->tanggalDipilih) {
            return collect();
        }

        return $this->slotQuery()
            ->whereDate('tanggal', $this->tanggalDipilih)
            ->with('lapangan')
            ->orderBy('jam_mulai')
            ->get();
    }

    public function render()
    {
        $tenant = app('tenant');

        return view('livewire.kalender-admin', [
            'tenant' => $tenant,
            'daftarLapangan' => Lapangan::where('tenant_id', $tenant->id)->orderBy('nama')->get(),
            'ringkasanMingguan' => $this->ringkasanMingguan(),
            'slotTanggalDipilih' => $this->slotTanggalDipilih(),
        ]);
    }
}
