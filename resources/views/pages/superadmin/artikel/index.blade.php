<x-layouts::superadmin active-page="artikel" judul="Blog">
    <div class="mb-6 flex items-start justify-between gap-3">
        <div>
            <h1 class="font-display text-xl font-semibold text-ink">Artikel Blog</h1>
            <p class="text-sm text-ink-soft mt-0.5">Kelola artikel yang tampil di halaman /blog.</p>
        </div>
        <a href="{{ route('superadmin.artikel.create') }}"
           class="inline-flex items-center justify-center gap-2 rounded-lg font-sans font-semibold text-sm px-5 py-2.5 transition-colors bg-green text-white hover:bg-green-mid">
            <x-icon name="plus" size="16" class="text-white" />
            Tulis Artikel
        </a>
    </div>

    <form method="GET" action="{{ route('superadmin.artikel') }}" class="flex flex-wrap items-end gap-3 mb-5">
        <div class="flex-1 min-w-[200px] max-w-xs">
            <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Cari Judul</label>
            <input type="text" name="cari" value="{{ $cari }}" class="w-full rounded-lg border border-cream-deep bg-white px-3 py-2 text-sm text-ink" />
        </div>

        <x-button type="submit" variant="secondary">Cari</x-button>

        @if ($cari)
            <a href="{{ route('superadmin.artikel') }}" class="text-sm text-ink-soft underline">Reset</a>
        @endif
    </form>

    <x-card class="!p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-cream text-left text-[0.7rem] uppercase tracking-wide text-ink-soft">
                        <th class="px-5 py-2.5 font-bold">Judul</th>
                        <th class="px-5 py-2.5 font-bold">Kategori</th>
                        <th class="px-5 py-2.5 font-bold">Tanggal Terbit</th>
                        <th class="px-5 py-2.5 font-bold">Status</th>
                        <th class="px-5 py-2.5 font-bold">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($artikel as $item)
                        <tr class="border-b border-cream last:border-0">
                            <td class="px-5 py-3 font-bold text-ink">{{ $item->judul }}</td>
                            <td class="px-5 py-3">{{ $item->kategori }}</td>
                            <td class="px-5 py-3">{{ $item->tanggal_terbit->format('d/m/Y') }}</td>
                            <td class="px-5 py-3"><x-badge :type="$item->status_aktif ? 'confirmed' : 'cancelled'">{{ $item->status_aktif ? 'Terbit' : 'Draft' }}</x-badge></td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('superadmin.artikel.edit', $item) }}" class="inline-flex items-center gap-1.5 rounded-lg font-sans font-semibold text-xs px-2.5 py-1.5 border-2 border-sand text-ink-mid hover:border-brown-light hover:text-brown transition-colors">
                                        <x-icon name="pencil-edit" size="13" />
                                        Edit
                                    </a>

                                    <div x-data="{ hapusTerbuka: false }">
                                        <button type="button" x-on:click="hapusTerbuka = true" class="inline-flex items-center gap-1.5 rounded-lg font-sans font-semibold text-xs px-2.5 py-1.5 bg-danger/10 text-danger border border-danger/20">
                                            <x-icon name="trash" size="13" />
                                            Hapus
                                        </button>

                                        <div x-show="hapusTerbuka" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink/50 p-5">
                                            <x-card class="max-w-sm w-full" x-on:click.outside="hapusTerbuka = false">
                                                <div class="font-display text-lg font-semibold text-ink mb-2">Hapus Artikel</div>
                                                <p class="text-sm text-ink-mid mb-3">"{{ $item->judul }}" akan dihapus permanen.</p>
                                                <form method="POST" action="{{ route('superadmin.artikel.destroy', $item) }}">
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
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-6 text-center text-ink-soft">Belum ada artikel.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</x-layouts::superadmin>
