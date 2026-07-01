<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\KodePromoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('kode_promo')]
#[Fillable([
    'tenant_id',
    'kode',
    'tipe_diskon',
    'nilai',
    'tanggal_mulai',
    'tanggal_berakhir',
    'kuota',
    'status_aktif',
])]
class KodePromo extends Model
{
    /** @use HasFactory<KodePromoFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nilai' => 'integer',
            'tanggal_mulai' => 'date',
            'tanggal_berakhir' => 'date',
            'kuota' => 'integer',
            'status_aktif' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
