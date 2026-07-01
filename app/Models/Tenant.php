<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'nama_bisnis',
    'domain',
    'paket',
    'status_aktif',
    'tanggal_mulai',
    'tanggal_berakhir',
    'logo_url',
    'warna_utama',
    'warna_aksen',
    'whatsapp_admin',
    'email_admin',
    'bank_nama',
    'bank_no_rekening',
    'bank_pemilik_rekening',
])]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

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

    public function punyaFitur(string $namaFitur): bool
    {
        return (bool) ($this->fitur?->{$namaFitur} ?? false);
    }
}
