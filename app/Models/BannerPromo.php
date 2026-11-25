<?php

namespace App\Models;

use Database\Factories\BannerPromoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Banner pop-up promo situs korporat. Hanya satu yang tampil ke pengunjung:
 * banner tayang terbaru. `target` menentukan halaman, `frekuensi` seberapa
 * sering satu pengunjung melihatnya.
 */
#[Fillable([
    'judul',
    'foto_url',
    'tautan',
    'label_tombol',
    'mulai_pada',
    'berakhir_pada',
    'target',
    'frekuensi',
    'tunda_detik',
    'status_aktif',
])]
class BannerPromo extends Model
{
    /** @use HasFactory<BannerPromoFactory> */
    use HasFactory;

    protected $table = 'banner_promo';

    public const TARGET = ['semua', 'beranda', 'toko'];

    public const FREKUENSI = ['sekali', 'sesi', 'selalu'];

    public const LABEL_TOMBOL_BAWAAN = 'Lihat Promo';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mulai_pada' => 'datetime',
            'berakhir_pada' => 'datetime',
            'tunda_detik' => 'integer',
            'status_aktif' => 'boolean',
        ];
    }

    /**
     * Aktif dan jam sekarang di dalam jadwalnya.
     *
     * @param  Builder<BannerPromo>  $query
     */
    #[Scope]
    protected function tayang(Builder $query): void
    {
        $query->where('status_aktif', true)
            ->where(fn (Builder $jadwal) => $jadwal->whereNull('mulai_pada')->orWhere('mulai_pada', '<=', now()))
            ->where(fn (Builder $jadwal) => $jadwal->whereNull('berakhir_pada')->orWhere('berakhir_pada', '>=', now()));
    }

    /**
     * Banner yang ditampilkan ke pengunjung saat ini, atau null.
     */
    public static function yangTampil(): ?self
    {
        return static::query()->tayang()->latest('id')->first();
    }

    /**
     * Status untuk admin: tayang, terjadwal, berakhir, atau nonaktif.
     */
    public function status(): string
    {
        return match (true) {
            ! $this->status_aktif => 'nonaktif',
            $this->berakhir_pada !== null && $this->berakhir_pada->isPast() => 'berakhir',
            $this->mulai_pada !== null && $this->mulai_pada->isFuture() => 'terjadwal',
            default => 'tayang',
        };
    }
}
