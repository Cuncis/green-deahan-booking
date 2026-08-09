<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Fillable([
    'nama_bisnis',
    'domain',
    'kode_pendaftaran',
    'custom_domain',
    'custom_domain_diminta',
    'paket',
    'status_aktif',
    'tanggal_mulai',
    'tanggal_berakhir',
    'dibayar_at',
    'logo_url',
    'warna_utama',
    'warna_aksen',
    'whatsapp_admin',
    'email_admin',
])]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    /**
     * Harga langganan tahunan per paket, dipakai TenantRegistrationController
     * untuk bikin invoice Mayar sekaligus ditampilkan di halaman /daftar dan
     * /harga, supaya tidak ada dua sumber angka yang bisa beda sendiri-sendiri.
     *
     * @var array<string, int>
     */
    public const HARGA_PAKET = [
        'basic' => 1_500_000,
        'pro' => 2_500_000,
        'premium' => 4_500_000,
    ];

    public const HARGA_ADDON_CUSTOM_DOMAIN = 250_000;

    /**
     * Diskon perpanjangan tahunan dibanding harga tahun pertama, biaya
     * hosting/domain/maintenance yang lebih ringan untuk tahun berikutnya
     * (lihat teks di halaman /harga). Custom domain TIDAK ditagih ulang
     * terpisah saat perpanjangan, sudah termasuk dalam angka ini.
     */
    public const DISKON_PERPANJANGAN_PERSEN = 30;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_aktif' => 'boolean',
            'tanggal_mulai' => 'date',
            'tanggal_berakhir' => 'date',
            'dibayar_at' => 'datetime',
        ];
    }

    public function fitur(): HasOne
    {
        return $this->hasOne(TenantFitur::class);
    }

    public function cabang(): HasMany
    {
        return $this->hasMany(Cabang::class);
    }

    public function lapangan(): HasMany
    {
        return $this->hasMany(Lapangan::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function staf(): HasMany
    {
        return $this->hasMany(Staf::class);
    }

    public function tagihanPerpanjangan(): HasMany
    {
        return $this->hasMany(TenantTagihanPerpanjangan::class);
    }

    public function punyaFitur(string $namaFitur): bool
    {
        return (bool) ($this->fitur?->{$namaFitur} ?? false);
    }

    public static function hitungHargaLangganan(string $paket, bool $customDomain): int
    {
        return self::HARGA_PAKET[$paket] + ($customDomain ? self::HARGA_ADDON_CUSTOM_DOMAIN : 0);
    }

    public static function hitungHargaPerpanjangan(string $paket): int
    {
        return (int) round(self::HARGA_PAKET[$paket] * (100 - self::DISKON_PERPANJANGAN_PERSEN) / 100);
    }

    /**
     * Kode referensi unik untuk mencocokkan invoice Mayar kembali ke tenant
     * ini di webhook (lihat PaymentService::createLanggananTransaction()),
     * sama seperti Booking::generateKodeBooking() tapi tanpa
     * withoutGlobalScopes() karena Tenant tidak pakai BelongsToTenant.
     */
    public static function generateKodePendaftaran(): string
    {
        do {
            $kode = 'TNT'.now()->format('ymd').strtoupper(Str::random(4));
        } while (self::where('kode_pendaftaran', $kode)->exists());

        return $kode;
    }
}
