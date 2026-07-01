<?php

namespace App\Livewire;

use App\Models\Staf;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Component;

class StafManager extends Component
{
    public bool $tampilkanForm = false;

    public string $email = '';

    public string $role = 'staff';

    public ?string $pesanError = null;

    public function bukaForm(): void
    {
        $this->reset(['email', 'pesanError']);
        $this->role = 'staff';
        $this->tampilkanForm = true;
    }

    public function tutupForm(): void
    {
        $this->tampilkanForm = false;
    }

    public function tambah(): void
    {
        $tenant = app('tenant');

        $data = $this->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'in:owner,manager,staff'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            $this->pesanError = 'Belum ada akun terdaftar dengan email ini, minta orangnya daftar dulu.';

            return;
        }

        if (Staf::where('tenant_id', $tenant->id)->where('user_id', $user->id)->exists()) {
            $this->pesanError = 'Orang ini sudah terdaftar sebagai staf.';

            return;
        }

        Staf::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => $data['role'],
            'status_aktif' => true,
        ]);

        $this->tampilkanForm = false;
    }

    public function ubahRole(int $stafId, string $role): void
    {
        Staf::where('tenant_id', app('tenant')->id)
            ->where('id', $stafId)
            ->update(['role' => $role]);
    }

    /**
     * @return Collection<int, Staf>
     */
    public function daftarStaf(): Collection
    {
        return Staf::where('tenant_id', app('tenant')->id)
            ->with('user')
            ->get();
    }

    public function render()
    {
        return view('livewire.staf-manager', [
            'daftarStaf' => $this->daftarStaf(),
        ]);
    }
}
