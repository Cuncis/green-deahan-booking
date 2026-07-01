<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\LapanganFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('lapangan')]
#[Fillable([
    'tenant_id',
    'cabang_id',
    'nama',
    'jenis_olahraga',
    'harga_per_jam',
    'harga_jam_sibuk',
    'foto_url',
    'deskripsi',
    'status_aktif',
])]
class Lapangan extends Model
{
    /** @use HasFactory<LapanganFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'harga_per_jam' => 'integer',
            'harga_jam_sibuk' => 'integer',
            'status_aktif' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class);
    }

    public function jadwalSlot(): HasMany
    {
        return $this->hasMany(JadwalSlot::class);
    }
}
