<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Staf;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Kelola staf & operator dari dashboard admin (fitur Premium
 * manajemen_staf, sebelumnya Livewire StafManager).
 */
class StafAdminController extends Controller
{
    private const ROLE = ['owner', 'manager', 'staff'];

    public function store(Request $request): RedirectResponse
    {
        $tenant = app('tenant');
        abort_unless($tenant->punyaFitur('manajemen_staf'), 404);

        $data = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'in:'.implode(',', self::ROLE)],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'Belum ada akun terdaftar dengan email ini, minta orangnya daftar dulu.',
            ]);
        }

        if (Staf::where('tenant_id', $tenant->id)->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Orang ini sudah terdaftar sebagai staf.',
            ]);
        }

        Staf::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => $data['role'],
            'status_aktif' => true,
        ]);

        return back()->with('success', $user->name.' ditambahkan sebagai '.$data['role'].'.');
    }

    public function ubahRole(Request $request, int $staf): RedirectResponse
    {
        $tenant = app('tenant');
        abort_unless($tenant->punyaFitur('manajemen_staf'), 404);

        $data = $request->validate([
            'role' => ['required', 'in:'.implode(',', self::ROLE)],
        ]);

        $diubah = Staf::where('tenant_id', $tenant->id)
            ->where('id', $staf)
            ->update(['role' => $data['role']]);

        abort_if($diubah === 0, 404);

        return back()->with('success', 'Role staf diperbarui.');
    }
}
