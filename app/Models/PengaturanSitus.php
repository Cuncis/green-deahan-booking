<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Key-value pengaturan situs korporat (GA4, Search Console, SEO default).
 * Bukan data tenant, jadi tanpa BelongsToTenant.
 */
#[Table('pengaturan_situs', key: 'kunci', keyType: 'string', incrementing: false)]
#[Fillable(['kunci', 'nilai'])]
class PengaturanSitus extends Model
{
    private const CACHE_KEY = 'pengaturan_situs';

    /**
     * @var array<string, string>
     */
    public const BAWAAN = [
        'nama_situs' => 'Green Deahan Sport',
        'meta_deskripsi' => 'Jasa pembuatan lapangan futsal, mini soccer, padel dan badminton se-Indonesia, lengkap dengan website booking lapangan.',
        'og_image_url' => '',
        'ga4_measurement_id' => '',
        'gsc_verifikasi' => '',
        'indeks_mesin_pencari' => '1',
    ];

    /**
     * @return array<string, string>
     */
    public static function semua(): array
    {
        $tersimpan = Cache::rememberForever(
            self::CACHE_KEY,
            fn () => static::query()->pluck('nilai', 'kunci')->all(),
        );

        $hasil = self::BAWAAN;

        foreach (self::BAWAAN as $kunci => $bawaan) {
            if (isset($tersimpan[$kunci]) && $tersimpan[$kunci] !== '') {
                $hasil[$kunci] = (string) $tersimpan[$kunci];
            } elseif (array_key_exists($kunci, $tersimpan)) {
                $hasil[$kunci] = '';
            }
        }

        return $hasil;
    }

    public static function ambil(string $kunci): string
    {
        return self::semua()[$kunci] ?? '';
    }

    /**
     * @param  array<string, string|null>  $nilai
     */
    public static function simpan(array $nilai): void
    {
        foreach ($nilai as $kunci => $isi) {
            if (! array_key_exists($kunci, self::BAWAAN)) {
                continue;
            }

            static::query()->updateOrCreate(['kunci' => $kunci], ['nilai' => $isi ?? '']);
        }

        Cache::forget(self::CACHE_KEY);
    }
}
