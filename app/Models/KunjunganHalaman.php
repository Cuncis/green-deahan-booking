<?php

namespace App\Models;

use Database\Factories\KunjunganHalamanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu tampilan halaman di situs korporat, dicatat oleh middleware
 * CatatKunjungan dan diringkas oleh StatistikPengunjungService.
 */
#[Table('kunjungan_halaman')]
#[Fillable([
    'path',
    'tanggal',
    'pengunjung_hash',
    'perangkat',
    'sumber_host',
    'negara',
    'wilayah',
])]
class KunjunganHalaman extends Model
{
    /** @use HasFactory<KunjunganHalamanFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * Zona waktu untuk menentukan "hari" kunjungan (WIB), karena aplikasi
     * berjalan di UTC.
     */
    public const ZONA_WAKTU = 'Asia/Jakarta';

    // `tanggal` sengaja tanpa cast 'date': disimpan sebagai string Y-m-d
    // supaya perbandingan tanggal sama di MySQL dan SQLite (cast 'date'
    // menyimpan "Y-m-d 00:00:00" di SQLite).
}
