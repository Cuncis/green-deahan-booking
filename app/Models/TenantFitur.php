<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('tenant_fitur', key: 'tenant_id', keyType: 'int', incrementing: false, timestamps: false)]
#[Fillable([
    'tenant_id',
    'booking_online',
    'notifikasi_whatsapp',
    'pembayaran_online',
    'dp_pembayaran',
    'kode_promo',
    'booking_berulang',
    'rating_ulasan',
    'laporan_pendapatan',
    'multi_cabang',
    'sistem_membership',
    'reminder_otomatis',
    'manajemen_staf',
    'analitik_lanjutan',
    'batas_lapangan',
])]
class TenantFitur extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'booking_online' => 'boolean',
            'notifikasi_whatsapp' => 'boolean',
            'pembayaran_online' => 'boolean',
            'dp_pembayaran' => 'boolean',
            'kode_promo' => 'boolean',
            'booking_berulang' => 'boolean',
            'rating_ulasan' => 'boolean',
            'laporan_pendapatan' => 'boolean',
            'multi_cabang' => 'boolean',
            'sistem_membership' => 'boolean',
            'reminder_otomatis' => 'boolean',
            'manajemen_staf' => 'boolean',
            'analitik_lanjutan' => 'boolean',
        ];
    }

    /**
     * Preset kombinasi fitur untuk tiap tingkat paket.
     *
     * @return array<string, bool|int|null>
     */
    public static function presetUntukPaket(string $paket): array
    {
        return match ($paket) {
            'basic' => [
                'booking_online' => true,
                'notifikasi_whatsapp' => true,
                'pembayaran_online' => false,
                'dp_pembayaran' => false,
                'kode_promo' => false,
                'booking_berulang' => false,
                'rating_ulasan' => false,
                'laporan_pendapatan' => false,
                'multi_cabang' => false,
                'sistem_membership' => false,
                'reminder_otomatis' => false,
                'manajemen_staf' => false,
                'analitik_lanjutan' => false,
                'batas_lapangan' => 1,
            ],
            'pro' => [
                'booking_online' => true,
                'notifikasi_whatsapp' => true,
                'pembayaran_online' => true,
                'dp_pembayaran' => true,
                'kode_promo' => true,
                'booking_berulang' => true,
                'rating_ulasan' => true,
                'laporan_pendapatan' => true,
                'multi_cabang' => false,
                'sistem_membership' => false,
                'reminder_otomatis' => false,
                'manajemen_staf' => false,
                'analitik_lanjutan' => false,
                'batas_lapangan' => 3,
            ],
            'premium' => [
                'booking_online' => true,
                'notifikasi_whatsapp' => true,
                'pembayaran_online' => true,
                'dp_pembayaran' => true,
                'kode_promo' => true,
                'booking_berulang' => true,
                'rating_ulasan' => true,
                'laporan_pendapatan' => true,
                'multi_cabang' => true,
                'sistem_membership' => true,
                'reminder_otomatis' => true,
                'manajemen_staf' => true,
                'analitik_lanjutan' => true,
                'batas_lapangan' => null,
            ],
            default => throw new \InvalidArgumentException("Paket \"{$paket}\" tidak dikenal."),
        };
    }
}
