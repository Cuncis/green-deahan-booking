<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantFitur;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Produk & Layanan > Sistem Booking (/sistem-booking). Harga dan batas
 * lapangan dibaca dari Tenant::HARGA_PAKET dan TenantFitur::presetUntukPaket(),
 * sama dengan yang ditagih saat pendaftaran (/daftar), supaya halaman ini
 * tidak pernah menampilkan harga yang berbeda.
 */
class SistemBookingController extends Controller
{
    /**
     * @var array<string, array{nama: string, tagline: string, satuan: string, sorotan: array<int, string>}>
     */
    private const PAKET = [
        'basic' => [
            'nama' => 'Basic',
            'tagline' => 'Untuk lapangan tunggal yang baru mulai online.',
            'satuan' => 'per tahun, memakai domain kami',
            'sorotan' => ['Booking mandiri 24 jam', 'Pembayaran online (QRIS, VA, e-wallet)', 'Konfirmasi otomatis (WA/email)', 'Setup awal & support email 1 tahun'],
        ],
        'pro' => [
            'nama' => 'Pro',
            'tagline' => 'Untuk lapangan yang serius cari pelanggan dan untung.',
            'satuan' => 'per tahun, domain dan hosting sendiri',
            'sorotan' => ['Semua fitur Basic', 'Pilihan DP atau bayar penuh', 'Kode promo & booking berulang', 'Laporan pendapatan & jam ramai', 'Support WA + email, pelatihan online'],
        ],
        'premium' => [
            'nama' => 'Premium',
            'tagline' => 'Untuk multi-lapangan atau cabang dengan fitur lengkap.',
            'satuan' => 'per tahun, full custom dan prioritas',
            'sorotan' => ['Semua fitur Pro', 'Multi-cabang', 'Membership & reminder sebelum main', 'Kelola staf & analitik lengkap', 'Support prioritas, pelatihan online + video'],
        ],
    ];

    public function __invoke(): Response
    {
        return Inertia::render('SistemBooking', [
            'paket' => collect(self::PAKET)->map(fn (array $paket, string $kunci) => [
                'kunci' => $kunci,
                ...$paket,
                'harga' => Tenant::HARGA_PAKET[$kunci],
                'batasLapangan' => TenantFitur::presetUntukPaket($kunci)['batas_lapangan'],
                'unggulan' => $kunci === 'pro',
                'daftarUrl' => '/daftar?paket='.$kunci,
            ])->values()->all(),
            'diskonPerpanjanganPersen' => Tenant::DISKON_PERPANJANGAN_PERSEN,
            'meta' => [
                'title' => 'Sistem Booking Lapangan Online untuk Futsal, Padel & Badminton - Green Deahan Sport',
                'description' => 'Website booking lapangan dengan jadwal real-time, anti double-booking, dan pembayaran online QRIS, VA, e-wallet. Paket mulai Rp1,5 juta per tahun, sudah termasuk setup.',
            ],
        ]);
    }
}
