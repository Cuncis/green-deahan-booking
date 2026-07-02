@php
    $labelKategori = [
        'futsal' => 'Futsal',
        'minisoccer' => 'Mini Soccer',
        'padel' => 'Padel',
        'badminton' => 'Badminton',
        'proses' => 'Proses Konstruksi',
    ];
@endphp

<x-layouts::superadmin active-page="galeri" judul="Galeri">
    <div class="mb-6 flex items-start justify-between gap-3">
        <div>
            <h1 class="font-display text-xl font-semibold text-ink">Galeri Proyek</h1>
            <p class="text-sm text-ink-soft mt-0.5">Kelola portofolio yang tampil di halaman /galeri.</p>
        </div>
        <a href="{{ route('superadmin.galeri.create') }}"
           class="inline-flex items-center justify-center gap-2 rounded-lg font-sans font-semibold text-sm px-5 py-2.5 transition-colors bg-green text-white hover:bg-green-mid">
            <x-icon name="plus" size="16" class="text-white" />
            Tambah Item
        </a>
    </div>

    <form method="GET" action="{{ route('superadmin.galeri') }}" class="flex flex-wrap items-end gap-3 mb-5">
        <div>
            <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Kategori</label>
            <select name="kategori" class="rounded-lg border border-cream-deep bg-white px-3 py-2 text-sm text-ink">
                <option value="">Semua Kategori</option>
                @foreach ($kategoriList as $kategori)
                    <option value="{{ $kategori }}" @selected($filterKategori === $kategori)>{{ $labelKategori[$kategori] }}</option>
                @endforeach
            </select>
        </div>

        <x-button type="submit" variant="secondary">Filter</x-button>

        @if ($filterKategori)
            <a href="{{ route('superadmin.galeri') }}" class="text-sm text-ink-soft underline">Reset</a>
        @endif
    </form>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($items as $item)
            <x-card class="!p-0 overflow-hidden">
                <div class="h-32 bg-cream-dark overflow-hidden">
                    <img src="{{ $item->foto_url }}" alt="{{ $item->judul }}" class="w-full h-full object-cover">
                </div>
                <div class="p-4">
                    <div class="flex items-start justify-between gap-2 mb-1">
                        <span class="font-display font-semibold text-ink text-sm">{{ $item->judul }}</span>
                        <x-badge :type="$item->status_aktif ? 'confirmed' : 'cancelled'">
                            {{ $item->status_aktif ? 'Tampil' : 'Sembunyi' }}
                        </x-badge>
                    </div>
                    <p class="text-xs text-ink-soft mb-3">{{ $labelKategori[$item->kategori] }}, {{ $item->kota }}</p>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('superadmin.galeri.edit', $item) }}" class="inline-flex items-center gap-1.5 rounded-lg font-sans font-semibold text-xs px-3 py-2 border-2 border-sand text-ink-mid hover:border-brown-light hover:text-brown transition-colors">
                            <x-icon name="pencil-edit" size="14" />
                            Edit
                        </a>

                        <div x-data="{ hapusTerbuka: false }">
                            <button type="button" x-on:click="hapusTerbuka = true" class="inline-flex items-center gap-1.5 rounded-lg font-sans font-semibold text-xs px-3 py-2 bg-danger/10 text-danger border border-danger/20">
                                <x-icon name="trash" size="14" />
                                Hapus
                            </button>

                            <div x-show="hapusTerbuka" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink/50 p-5">
                                <x-card class="max-w-sm w-full" x-on:click.outside="hapusTerbuka = false">
                                    <div class="font-display text-lg font-semibold text-ink mb-2">Hapus Item Galeri</div>
                                    <p class="text-sm text-ink-mid mb-3">"{{ $item->judul }}" akan dihapus permanen dari galeri.</p>
                                    <form method="POST" action="{{ route('superadmin.galeri.destroy', $item) }}">
                                        @csrf
                                        @method('DELETE')
                                        <div class="flex gap-2">
                                            <x-button type="submit" variant="danger" class="flex-1 justify-center">Ya, Hapus</x-button>
                                            <x-button type="button" variant="secondary" class="flex-1 justify-center" x-on:click="hapusTerbuka = false">Batal</x-button>
                                        </div>
                                    </form>
                                </x-card>
                            </div>
                        </div>
                    </div>
                </div>
            </x-card>
        @empty
            <x-card class="lg:col-span-3">
                <p class="text-sm text-ink-mid text-center py-4">Belum ada item galeri.</p>
            </x-card>
        @endforelse
    </div>
</x-layouts::superadmin>
