<?php

namespace App\Models;

use Database\Factories\TenantInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Table('tenant_invitations')]
#[Fillable(['tenant_id', 'email', 'role', 'token', 'expires_at', 'used_at'])]
class TenantInvitation extends Model
{
    /** @use HasFactory<TenantInvitationFactory> */
    use HasFactory;

    const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Bikin undangan baru dengan token acak. Dipakai bareng lewat
     * SuperadminController, BuatUndanganStaf, dan AktifkanTenant supaya
     * cara generate token & masa berlaku selalu konsisten di satu tempat.
     */
    public static function buatUntuk(Tenant $tenant, string $email, string $role = 'owner', int $hariBerlaku = 7): self
    {
        return self::create([
            'tenant_id' => $tenant->id,
            'email' => $email,
            'role' => $role,
            'token' => Str::random(48),
            'expires_at' => now()->addDays($hariBerlaku),
        ]);
    }

    public function link(): string
    {
        return url('/invite/'.$this->token);
    }

    public function sudahDipakai(): bool
    {
        return $this->used_at !== null;
    }

    public function sudahKadaluarsa(): bool
    {
        return $this->expires_at->isPast();
    }

    public function masihValid(): bool
    {
        return ! $this->sudahDipakai() && ! $this->sudahKadaluarsa();
    }
}
