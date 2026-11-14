<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantFitur;

/**
 * Paket langganan sistem booking (Basic/Pro/Premium) untuk halaman
 * /sistem-booking dan /daftar. Harga dan batas lapangan dibaca dari
 * Tenant::HARGA_PAKET dan TenantFitur::presetUntukPaket(), sama dengan yang
 * ditagih saat pendaftaran, supaya kedua halaman tidak pernah menampilkan
 * harga yang berbeda dari invoice.
 */
class PaketSistemBookingService
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

    /**
     * @return array<int, array{kunci: string, nama: string, tagline: string, satuan: string, sorotan: array<int, string>, harga: int, batasLapangan: int|null, unggulan: bool, daftarUrl: string}>
     */
    public function semua(): array
    {
        return collect(self::PAKET)->map(fn (array $paket, string $kunci) => [
            'kunci' => $kunci,
            ...$paket,
            'harga' => Tenant::HARGA_PAKET[$kunci],
            'batasLapangan' => TenantFitur::presetUntukPaket($kunci)['batas_lapangan'],
            'unggulan' => $kunci === 'pro',
            'daftarUrl' => '/daftar?paket='.$kunci,
        ])->values()->all();
    }

    public function ada(?string $kunci): bool
    {
        return $kunci !== null && array_key_exists($kunci, self::PAKET);
    }
}
