<?php

namespace App\Models;

use Database\Factories\CatatanPromosiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Catatan promosi dan iklan milik superadmin (rencana Google Ads, kata kunci,
 * prompt gambar, ide kampanye). Isinya teks biasa yang ditampilkan apa adanya.
 */
#[Fillable([
    'judul',
    'kategori',
    'isi',
    'disematkan',
])]
class CatatanPromosi extends Model
{
    /** @use HasFactory<CatatanPromosiFactory> */
    use HasFactory;

    protected $table = 'catatan_promosi';

    /**
     * @var array<string, string>
     */
    public const KATEGORI = [
        'google-ads' => 'Google Ads',
        'meta-ads' => 'Meta Ads',
        'prompt-gambar' => 'Prompt gambar',
        'kampanye' => 'Rencana kampanye',
        'lainnya' => 'Lainnya',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'disematkan' => 'boolean',
        ];
    }
}
