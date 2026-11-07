@php
    $formatUkuran = function (int $byte): string {
        if ($byte >= 1048576) {
            return number_format($byte / 1048576, 1, ',', '.').' MB';
        }

        return number_format($byte / 1024, 0, ',', '.').' KB';
    };
@endphp

<x-layouts::superadmin active-page="media" judul="Media">
    <div class="mb-6">
        <h1 class="font-display text-xl font-semibold text-ink">Pustaka Media</h1>
        <p class="text-sm text-ink-soft mt-0.5">
            {{ number_format($totalMedia, 0, ',', '.') }} file, {{ $formatUkuran((int) $totalUkuran) }}. Semua gambar dari galeri, blog, lapangan, dan logo tenant.
        </p>
    </div>

    <x-card class="mb-5" x-data="{ jumlah: 0 }">
        <form method="POST" action="{{ route('superadmin.media.store') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
            @csrf
            <label class="inline-flex items-center gap-2 rounded-lg border-2 border-dashed border-sand px-4 py-2.5 text-sm text-ink-mid cursor-pointer hover:border-brown-light">
                <x-icon name="upload" size="16" />
                <span x-text="jumlah ? jumlah + ' file dipilih' : 'Pilih gambar'"></span>
                <input type="file" name="files[]" accept="image/*" multiple required class="sr-only"
                       x-on:change="jumlah = $event.target.files.length">
            </label>
            <x-button type="submit">Upload</x-button>
            <span class="text-xs text-ink-soft">Maksimal 20 file, masing-masing 4 MB.</span>
        </form>
        @error('files')
            <p class="text-xs text-danger mt-2">{{ $message }}</p>
        @enderror
        @error('files.*')
            <p class="text-xs text-danger mt-2">{{ $message }}</p>
        @enderror
    </x-card>

    <form method="GET" action="{{ route('superadmin.media') }}" class="flex flex-wrap items-end gap-3 mb-5">
        <div>
            <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Cari nama file</label>
            <input type="text" name="q" value="{{ $keyword }}" class="rounded-lg border border-cream-deep bg-white px-3 py-2 text-sm text-ink">
        </div>
        <div>
            <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Folder</label>
            <select name="folder" class="rounded-lg border border-cream-deep bg-white px-3 py-2 text-sm text-ink">
                <option value="">Semua Folder</option>
                @foreach ($folderList as $folder)
                    <option value="{{ $folder }}" @selected($filterFolder === $folder)>{{ $folder }}</option>
                @endforeach
            </select>
        </div>

        <x-button type="submit" variant="secondary">Filter</x-button>

        @if ($filterFolder || $keyword !== '')
            <a href="{{ route('superadmin.media') }}" class="text-sm text-ink-soft underline">Reset</a>
        @endif
    </form>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @forelse ($items as $media)
            <x-card class="!p-0 overflow-hidden" x-data="{ detailTerbuka: false, hapusTerbuka: false, tersalin: false }">
                <button type="button" x-on:click="detailTerbuka = true" class="block w-full h-32 bg-cream-dark overflow-hidden">
                    <img src="{{ $media['url'] }}" alt="{{ $media['nama'] }}" loading="lazy" class="w-full h-full object-cover">
                </button>
                <div class="p-3">
                    <div class="text-xs font-semibold text-ink truncate" title="{{ $media['nama'] }}">{{ $media['nama'] }}</div>
                    <div class="text-xs text-ink-soft mt-0.5">{{ $media['folder'] }}, {{ $formatUkuran($media['ukuran']) }}</div>
                    <div class="mt-2">
                        <x-badge :type="$media['dipakai'] ? 'confirmed' : 'cancelled'">
                            {{ $media['dipakai'] ? 'Dipakai' : 'Tidak dipakai' }}
                        </x-badge>
                    </div>
                </div>

                <div x-show="detailTerbuka" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink/50 p-5">
                    <x-card class="max-w-lg w-full" x-on:click.outside="detailTerbuka = false">
                        <img src="{{ $media['url'] }}" alt="{{ $media['nama'] }}" class="w-full max-h-72 object-contain rounded-lg bg-cream-dark mb-3">
                        <div class="text-sm font-semibold text-ink break-all">{{ $media['nama'] }}</div>
                        <div class="text-xs text-ink-soft mb-3">
                            {{ $media['folder'] }}, {{ $formatUkuran($media['ukuran']) }}, {{ \Illuminate\Support\Carbon::createFromTimestamp($media['diubah'])->format('d/m/Y H:i') }}
                        </div>
                        <input type="text" readonly value="{{ $media['url'] }}" x-on:click="$el.select()"
                               class="w-full rounded-lg border border-cream-deep bg-white px-3 py-2 text-xs text-ink-mid mb-3">
                        <div class="flex gap-2">
                            <x-button type="button" variant="secondary" class="flex-1 justify-center"
                                      x-on:click="navigator.clipboard.writeText('{{ $media['url'] }}'); tersalin = true">
                                <span x-text="tersalin ? 'Tersalin' : 'Salin URL'"></span>
                            </x-button>
                            <x-button type="button" variant="danger" class="flex-1 justify-center"
                                      x-on:click="detailTerbuka = false; hapusTerbuka = true">Hapus</x-button>
                            <x-button type="button" variant="secondary" class="flex-1 justify-center" x-on:click="detailTerbuka = false">Tutup</x-button>
                        </div>
                    </x-card>
                </div>

                <div x-show="hapusTerbuka" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink/50 p-5">
                    <x-card class="max-w-sm w-full" x-on:click.outside="hapusTerbuka = false">
                        <div class="font-display text-lg font-semibold text-ink mb-2">Hapus File</div>
                        <p class="text-sm text-ink-mid mb-3">
                            "{{ $media['nama'] }}" akan dihapus permanen.
                            @if ($media['dipakai'])
                                File ini masih dipakai, jadi penghapusan akan ditolak.
                            @endif
                        </p>
                        <form method="POST" action="{{ route('superadmin.media.destroy') }}">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="path" value="{{ $media['path'] }}">
                            <div class="flex gap-2">
                                <x-button type="submit" variant="danger" class="flex-1 justify-center">Ya, Hapus</x-button>
                                <x-button type="button" variant="secondary" class="flex-1 justify-center" x-on:click="hapusTerbuka = false">Batal</x-button>
                            </div>
                        </form>
                    </x-card>
                </div>
            </x-card>
        @empty
            <x-card class="col-span-full">
                <p class="text-sm text-ink-mid text-center py-4">Belum ada file media.</p>
            </x-card>
        @endforelse
    </div>

    <div class="mt-5">
        {{ $items->links() }}
    </div>
</x-layouts::superadmin>
