@php
    $badgeRole = [
        'owner' => 'bg-plum text-white',
        'manager' => 'bg-gold text-white',
        'staff' => 'bg-cream-deep text-ink-mid',
    ];
@endphp

<x-card class="mb-6 !p-0 overflow-hidden">
    <div class="px-5 py-4 border-b border-cream-dark flex items-center justify-between gap-3">
        <div class="text-xs font-bold uppercase tracking-wide text-green">Staf & Operator</div>
        <x-button type="button" variant="secondary" wire:click="bukaForm">Tambah Staf</x-button>
    </div>

    @if ($tampilkanForm)
        <form wire:submit="tambah" class="px-5 py-4 border-b border-cream-dark bg-cream space-y-3.5">
            @if ($pesanError)
                <p class="text-sm text-danger">{{ $pesanError }}</p>
            @endif

            <div class="grid grid-cols-2 gap-3.5">
                <x-input label="Email Akun" name="email" type="email" placeholder="nama@email.com" wire:model="email" />
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Role</label>
                    <select wire:model="role" class="w-full rounded-lg border border-cream-deep bg-cream px-4 py-2.5 text-sm text-ink focus:border-green focus:outline-none focus:ring-1 focus:ring-green">
                        <option value="owner">Owner</option>
                        <option value="manager">Manager</option>
                        <option value="staff">Staff</option>
                    </select>
                </div>
            </div>

            @error('email') <p class="text-sm text-danger">{{ $message }}</p> @enderror

            <div class="flex gap-2">
                <x-button type="submit">Simpan Staf</x-button>
                <x-button type="button" variant="secondary" wire:click="tutupForm">Batal</x-button>
            </div>
        </form>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-cream text-left text-[0.7rem] uppercase tracking-wide text-ink-soft">
                    <th class="px-5 py-2.5 font-bold">Nama</th>
                    <th class="px-5 py-2.5 font-bold">Email</th>
                    <th class="px-5 py-2.5 font-bold">Role</th>
                    <th class="px-5 py-2.5 font-bold">Ubah Role</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftarStaf as $staf)
                    <tr wire:key="staf-{{ $staf->id }}" class="border-b border-cream last:border-0">
                        <td class="px-5 py-3 font-bold text-ink">{{ $staf->user->name }}</td>
                        <td class="px-5 py-3 text-ink-mid">{{ $staf->user->email }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold {{ $badgeRole[$staf->role] ?? $badgeRole['staff'] }}">
                                {{ ucfirst($staf->role) }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <select
                                wire:change="ubahRole({{ $staf->id }}, $event.target.value)"
                                class="rounded-lg border border-cream-deep bg-white px-2.5 py-1.5 text-xs text-ink"
                            >
                                <option value="owner" @selected($staf->role === 'owner')>Owner</option>
                                <option value="manager" @selected($staf->role === 'manager')>Manager</option>
                                <option value="staff" @selected($staf->role === 'staff')>Staff</option>
                            </select>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-5 py-6 text-center text-ink-soft">Belum ada staf terdaftar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
