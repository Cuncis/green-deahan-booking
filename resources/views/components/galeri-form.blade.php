@props(['item' => null, 'kategoriList'])

@php
    $labelKategori = [
        'futsal' => 'Futsal',
        'minisoccer' => 'Mini Soccer',
        'padel' => 'Padel',
        'badminton' => 'Badminton',
        'proses' => 'Proses Konstruksi',
    ];
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
    <x-input label="Judul" name="judul" value="{{ old('judul', $item->judul ?? '') }}" required />

    <div>
        <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Kategori</label>
        <select name="kategori" class="w-full rounded-lg border border-cream-deep bg-cream px-4 py-2.5 text-sm text-ink focus:border-green focus:outline-none focus:ring-1 focus:ring-green">
            @foreach ($kategoriList as $kategori)
                <option value="{{ $kategori }}" @selected(old('kategori', $item->kategori ?? '') === $kategori)>{{ $labelKategori[$kategori] }}</option>
            @endforeach
        </select>
        @error('kategori')
            <p class="text-xs text-danger mt-1">{{ $message }}</p>
        @enderror
    </div>

    <x-input label="Kota" name="kota" value="{{ old('kota', $item->kota ?? '') }}" required />
    <x-input label="Material" name="material" value="{{ old('material', $item->material ?? '') }}" required />

    <div>
        <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Urutan Tampil</label>
        <input
            type="number"
            name="urutan"
            value="{{ old('urutan', $item->urutan ?? 0) }}"
            class="w-full rounded-lg border border-cream-deep bg-cream px-4 py-2.5 text-sm text-ink focus:border-green focus:outline-none focus:ring-1 focus:ring-green"
        />
        <p class="text-xs text-ink-soft mt-1">Angka lebih kecil tampil lebih dulu.</p>
    </div>

    <label class="flex items-center gap-2 mt-6">
        <input
            type="checkbox"
            name="tampilan_besar"
            value="1"
            @checked(old('tampilan_besar', $item->tampilan_besar ?? false))
            class="rounded border-cream-deep text-green focus:ring-green"
        />
        <span class="text-sm text-ink-mid">Tampilkan besar di grid (2 baris)</span>
    </label>

    @if ($item)
        <div>
            <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Status</label>
            <select name="status_aktif" class="w-full rounded-lg border border-cream-deep bg-cream px-4 py-2.5 text-sm text-ink focus:border-green focus:outline-none focus:ring-1 focus:ring-green">
                <option value="1" @selected(old('status_aktif', (int) $item->status_aktif) == 1)>Tampil</option>
                <option value="0" @selected(old('status_aktif', (int) $item->status_aktif) == 0)>Disembunyikan</option>
            </select>
        </div>
    @endif
</div>

<div class="mb-6">
    <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Deskripsi</label>
    <textarea
        name="deskripsi"
        rows="3"
        class="w-full rounded-lg border border-cream-deep bg-cream px-4 py-2.5 text-sm text-ink placeholder:text-ink-soft focus:border-green focus:outline-none focus:ring-1 focus:ring-green"
    >{{ old('deskripsi', $item->deskripsi ?? '') }}</textarea>
    @error('deskripsi')
        <p class="text-xs text-danger mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-6">
    <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Foto</label>

    @if ($item?->foto_url)
        <img src="{{ $item->foto_url }}" alt="{{ $item->judul }}" class="w-40 h-28 object-cover rounded-lg border border-cream-deep mb-2">
    @endif

    <label class="flex items-center gap-2 cursor-pointer rounded-lg border-2 border-dashed border-cream-deep px-4 py-3 text-sm text-ink-mid hover:border-brown-light w-fit">
        <x-icon name="upload" size="16" />
        <span>{{ $item ? 'Ganti foto' : 'Pilih foto' }}</span>
        <input type="file" name="foto" accept="image/*" class="hidden" onchange="this.closest('label').querySelector('span').textContent = this.files[0]?.name ?? 'Pilih foto'">
    </label>
    @error('foto')
        <p class="text-xs text-danger mt-1">{{ $message }}</p>
    @enderror
</div>
