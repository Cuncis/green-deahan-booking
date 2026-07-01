<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// is_superadmin sengaja TIDAK dimasukkan ke #[Fillable]. Kolom ini hanya
// boleh diset lewat penugasan properti langsung + save() (lihat
// CreateSuperadminAccount), supaya tidak ada jalur mass-assignment mana pun
// (form request, API, dst) yang bisa menaikkan privilege user jadi superadmin.
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_superadmin' => 'boolean',
        ];
    }

    public function isSuperadmin(): bool
    {
        return (bool) $this->is_superadmin;
    }

    /**
     * Tenant tempat user ini jadi staf (owner/manager/staff). Superadmin
     * tidak punya baris staf sama sekali, jadi relasi ini akan selalu
     * kosong untuk mereka, sesuai tujuan: superadmin independen dari tenant.
     */
    public function tenants(): HasManyThrough
    {
        return $this->hasManyThrough(
            Tenant::class,
            Staf::class,
            'user_id',
            'id',
            'id',
            'tenant_id',
        );
    }
}
