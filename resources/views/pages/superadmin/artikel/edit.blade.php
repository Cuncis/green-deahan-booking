<x-layouts::superadmin active-page="artikel" judul="Edit Artikel">
    <div class="mb-6">
        <h1 class="font-display text-xl font-semibold text-ink">Edit Artikel</h1>
        <p class="text-sm text-ink-soft mt-0.5">{{ $artikel->judul }}</p>
    </div>

    <x-card class="max-w-3xl">
        <form method="POST" action="{{ route('superadmin.artikel.update', $artikel) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <x-artikel-form :artikel="$artikel" />

            <div class="flex gap-2">
                <x-button type="submit">Simpan Perubahan</x-button>
                <a href="{{ route('superadmin.artikel') }}" class="inline-flex items-center justify-center rounded-lg font-sans font-semibold text-sm px-5 py-2.5 border-2 border-sand text-ink-mid hover:border-brown-light hover:text-brown transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </x-card>
</x-layouts::superadmin>
