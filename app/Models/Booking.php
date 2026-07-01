<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    'reminder_aktif',
    'recurring_group_id',
    'recurring_ke',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use BelongsToTenant, HasFactory;

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
            'reminder_aktif' => 'boolean',
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

    public function reminderLogs(): HasMany
    {
        return $this->hasMany(ReminderLog::class);
    }

    public static function generateKodeBooking(): string
    {
        do {
            $kode = 'BK'.now()->format('ymd').strtoupper(Str::random(4));
        } while (self::withoutGlobalScopes()->where('kode_booking', $kode)->exists());

        return $kode;
    }
}
