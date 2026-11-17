<?php

namespace App\Models;

use Database\Factories\WilayahFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu wilayah administrasi. Kode Kemendagri bertingkat: "11" provinsi,
 * "11.01" kabupaten/kota, "11.01.01" kecamatan, "11.01.01.2001" kelurahan/desa
 * (level 1 sampai 4). Kode pos hanya ada di level 4.
 */
#[Fillable(['kode', 'nama', 'level', 'induk', 'kodepos'])]
class Wilayah extends Model
{
    /** @use HasFactory<WilayahFactory> */
    use HasFactory;

    public const LEVEL_PROVINSI = 1;

    public const LEVEL_KOTA = 2;

    public const LEVEL_KECAMATAN = 3;

    public const LEVEL_KELURAHAN = 4;

    protected $table = 'wilayah';

    protected $primaryKey = 'kode';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    /**
     * Induk langsung dari sebuah kode: "11.01.01.2001" jadi "11.01.01", provinsi tidak punya induk.
     */
    public static function indukDari(string $kode): ?string
    {
        $bagian = explode('.', $kode);
        array_pop($bagian);

        return $bagian === [] ? null : implode('.', $bagian);
    }

    public static function levelDari(string $kode): int
    {
        return count(explode('.', $kode));
    }
}
