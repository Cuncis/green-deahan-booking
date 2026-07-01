<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Table('booking')]
#[Fillable([
    'tenant_id',
    'slot_id',
    'customer_id',
    'kode_promo_id',
    'membership_id',
    'kode_booking',
    'harga_normal',
    'diskon_jumlah',
    'total_bayar',
    'tipe_pembayaran',
    'status_booking',
    'recurring_group_id',
    'recurring_ke',
])]
class Booking extends Model
{
    use BelongsToTenant;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'harga_normal' => 'integer',
            'diskon_jumlah' => 'integer',
            'total_bayar' => 'integer',
            'recurring_ke' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(JadwalSlot::class, 'slot_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function kodePromo(): BelongsTo
    {
        return $this->belongsTo(KodePromo::class);
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }

    public static function generateKodeBooking(): string
    {
        do {
            $kode = 'BK'.now()->format('ymd').strtoupper(Str::random(4));
        } while (self::withoutGlobalScopes()->where('kode_booking', $kode)->exists());

        return $kode;
    }
}
