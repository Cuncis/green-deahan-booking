<?php

namespace App\Models;

use Database\Factories\GaleriItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'kategori',
    'judul',
    'kota',
    'material',
    'deskripsi',
    'foto_url',
    'tampilan_besar',
    'urutan',
    'status_aktif',
])]
class GaleriItem extends Model
{
    /** @use HasFactory<GaleriItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tampilan_besar' => 'boolean',
            'urutan' => 'integer',
            'status_aktif' => 'boolean',
        ];
    }
}
