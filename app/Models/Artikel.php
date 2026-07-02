<?php

namespace App\Models;

use Database\Factories\ArtikelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table('artikel')]
#[Fillable([
    'judul',
    'slug',
    'kategori',
    'ringkasan',
    'konten',
    'foto_url',
    'status_aktif',
    'tanggal_terbit',
])]
class Artikel extends Model
{
    /** @use HasFactory<ArtikelFactory> */
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
            'tanggal_terbit' => 'date',
        ];
    }
}
