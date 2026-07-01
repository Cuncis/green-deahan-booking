<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\MembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('membership')]
#[Fillable(['tenant_id', 'customer_id', 'tier', 'total_booking'])]
class Membership extends Model
{
    /** @use HasFactory<MembershipFactory> */
    use BelongsToTenant, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_booking' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Persen diskon tetap per tier membership.
     */
    public function persenDiskon(): int
    {
        return self::persenDiskonUntukTier($this->tier);
    }

    public static function persenDiskonUntukTier(string $tier): int
    {
        return match ($tier) {
            'gold' => 15,
            'silver' => 10,
            'bronze' => 5,
            default => 0,
        };
    }

    /**
     * Harga per jam setelah diskon tier membership diterapkan.
     */
    public function hargaMember(int $hargaNormal): int
    {
        return $hargaNormal - (int) round($hargaNormal * $this->persenDiskon() / 100);
    }
}
