@props([])

<div class="flex items-center justify-end mb-6">
    <x-dropdown align="right" width="w-60">
        <x-slot name="trigger">
            <button type="button" class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm hover:bg-cream transition-colors">
                <span class="w-8 h-8 rounded-full bg-green-pale text-green font-bold flex items-center justify-center text-xs uppercase">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </span>
                <span class="font-semibold text-ink hidden sm:inline">{{ auth()->user()->name }}</span>
                <x-icon name="chevron-down" size="14" class="text-ink-soft" />
            </button>
        </x-slot>

        <x-slot name="content">
            <a href="{{ route('profile.edit') }}" class="block px-4 py-2.5 text-sm text-ink-mid hover:bg-cream">
                Profil & Kata Sandi
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="block w-full text-left px-4 py-2.5 text-sm text-danger hover:bg-danger-pale">
                    Keluar
                </button>
            </form>
        </x-slot>
    </x-dropdown>
</div>
