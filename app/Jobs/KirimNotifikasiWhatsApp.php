<?php

namespace App\Jobs;

use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class KirimNotifikasiWhatsApp implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  'dikonfirmasi'|'dibatalkan'  $tipe
     */
    public function __construct(public Booking $booking, public string $tipe = 'dikonfirmasi')
    {
        //
    }

    /**
     * Belum terhubung ke WhatsApp Business API resmi (lihat
     * references/notifikasi-whatsapp.md). Untuk sekarang customer tetap
     * dikonfirmasi lewat link wa.me manual di UI, job ini hanya mencatat log
     * sebagai titik pemasangan API resmi nanti.
     */
    public function handle(): void
    {
        $pesan = $this->tipe === 'dibatalkan'
            ? 'Notifikasi WhatsApp booking dibatalkan (API belum terpasang)'
            : 'Notifikasi WhatsApp booking dikonfirmasi (API belum terpasang)';

        Log::info($pesan, [
            'booking_id' => $this->booking->id,
            'kode_booking' => $this->booking->kode_booking,
        ]);
    }
}
