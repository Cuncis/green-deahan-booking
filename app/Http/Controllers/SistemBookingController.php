<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\PaketSistemBookingService;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Layanan > Sistem Booking (/sistem-booking). Paket dan harganya
 * dari PaketSistemBookingService, sama dengan yang ditagih saat pendaftaran
 * (/daftar). Selama SISTEM_BOOKING_PUBLIK false, halaman ini noindex dan
 * tidak ada di menu maupun sitemap.
 */
class SistemBookingController extends Controller
{
    public function __invoke(PaketSistemBookingService $paket): Response
    {
        return Inertia::render('SistemBooking', [
            'paket' => $paket->semua(),
            'diskonPerpanjanganPersen' => Tenant::DISKON_PERPANJANGAN_PERSEN,
            'meta' => [
                'title' => 'Sistem Booking Lapangan Online - Green Deahan Sport',
                'description' => 'Website booking lapangan dengan jadwal real-time, anti double-booking, dan bayar online QRIS, VA, e-wallet. Paket mulai Rp1,5 juta per tahun, termasuk setup.',
                'noindex' => ! config('situs.sistem_booking_publik'),
            ],
        ]);
    }
}
