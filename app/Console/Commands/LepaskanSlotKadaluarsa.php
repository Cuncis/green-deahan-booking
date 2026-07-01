<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\JadwalSlot;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Signature('booking:lepas-slot-kadaluarsa')]
#[Description('Melepas slot hold yang sudah melewati batas waktu hold kembali menjadi kosong')]
class LepaskanSlotKadaluarsa extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $slotIds = JadwalSlot::withoutGlobalScopes()
            ->where('status', 'hold')
            ->where('hold_sampai', '<', now())
            ->pluck('id');

        $jumlahDilepas = 0;

        foreach ($slotIds as $slotId) {
            DB::transaction(function () use ($slotId, &$jumlahDilepas) {
                $slot = JadwalSlot::withoutGlobalScopes()
                    ->where('id', $slotId)
                    ->lockForUpdate()
                    ->first();

                if (! $slot || $slot->status !== 'hold' || $slot->hold_sampai === null || $slot->hold_sampai->isFuture()) {
                    return;
                }

                $slot->update([
                    'status' => 'kosong',
                    'hold_sampai' => null,
                ]);

                Booking::withoutGlobalScopes()
                    ->where('slot_id', $slot->id)
                    ->where('status_booking', 'menunggu')
                    ->update(['status_booking' => 'dibatalkan']);

                $jumlahDilepas++;
            });
        }

        Log::info('Melepas slot kadaluarsa', ['jumlah_dilepas' => $jumlahDilepas]);

        $this->info("Berhasil melepas {$jumlahDilepas} slot kadaluarsa.");

        return self::SUCCESS;
    }
}
