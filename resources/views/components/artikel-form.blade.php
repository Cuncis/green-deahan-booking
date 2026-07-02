@props(['artikel' => null])

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
    <div class="md:col-span-2">
        <x-input label="Judul" name="judul" value="{{ old('judul', $artikel->judul ?? '') }}" required />
    </div>

    <x-input label="Kategori" name="kategori" value="{{ old('kategori', $artikel->kategori ?? '') }}" placeholder="Misal Panduan Bisnis" required />

    <div>
        <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Tanggal Terbit</label>
        <input
            type="date"
            name="tanggal_terbit"
            value="{{ old('tanggal_terbit', isset($artikel) ? $artikel->tanggal_terbit->toDateString() : now()->toDateString()) }}"
            class="w-full rounded-lg border border-cream-deep bg-cream px-4 py-2.5 text-sm text-ink focus:border-green focus:outline-none focus:ring-1 focus:ring-green"
        />
        @error('tanggal_terbit')
            <p class="text-xs text-danger mt-1">{{ $message }}</p>
        @enderror
    </div>

    @if ($artikel)
        <div>
            <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Status</label>
            <select name="status_aktif" class="w-full rounded-lg border border-cream-deep bg-cream px-4 py-2.5 text-sm text-ink focus:border-green focus:outline-none focus:ring-1 focus:ring-green">
                <option value="1" @selected(old('status_aktif', (int) $artikel->status_aktif) == 1)>Terbit</option>
                <option value="0" @selected(old('status_aktif', (int) $artikel->status_aktif) == 0)>Draft (disembunyikan)</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Slug</label>
            <input type="text" value="{{ $artikel->slug }}" disabled class="w-full rounded-lg border border-cream-deep bg-cream-dark px-4 py-2.5 text-sm text-ink-soft" />
            <p class="text-xs text-ink-soft mt-1">URL: /blog/{{ $artikel->slug }}, dibuat otomatis dari judul saat dibuat.</p>
        </div>
    @endif
</div>

<div class="mb-5">
    <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Ringkasan</label>
    <textarea
        name="ringkasan"
        rows="3"
        class="w-full rounded-lg border border-cream-deep bg-cream px-4 py-2.5 text-sm text-ink placeholder:text-ink-soft focus:border-green focus:outline-none focus:ring-1 focus:ring-green"
        placeholder="Ringkasan singkat yang tampil di daftar artikel"
    >{{ old('ringkasan', $artikel->ringkasan ?? '') }}</textarea>
    @error('ringkasan')
        <p class="text-xs text-danger mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-6">
    <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Konten</label>
    <textarea
        name="konten"
        rows="14"
        class="w-full rounded-lg border border-cream-deep bg-cream px-4 py-2.5 text-sm text-ink placeholder:text-ink-soft focus:border-green focus:outline-none focus:ring-1 focus:ring-green font-mono"
        placeholder="Boleh pakai tag HTML dasar, misal <p>, <h2>, <ul><li>, <strong>"
    >{{ old('konten', $artikel->konten ?? '') }}</textarea>
    <p class="text-xs text-ink-soft mt-1">Mendukung tag HTML dasar untuk format teks (paragraf, heading, list, bold).</p>
    @error('konten')
        <p class="text-xs text-danger mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-6">
    <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Foto Unggulan</label>

    @if ($artikel?->foto_url)
        <img src="{{ $artikel->foto_url }}" alt="{{ $artikel->judul }}" class="w-40 h-28 object-cover rounded-lg border border-cream-deep mb-2">
    @endif

    <label class="flex items-center gap-2 cursor-pointer rounded-lg border-2 border-dashed border-cream-deep px-4 py-3 text-sm text-ink-mid hover:border-brown-light w-fit">
        <x-icon name="upload" size="16" />
        <span>{{ $artikel ? 'Ganti foto' : 'Pilih foto' }}</span>
        <input type="file" name="foto" accept="image/*" class="hidden" onchange="this.closest('label').querySelector('span').textContent = this.files[0]?.name ?? 'Pilih foto'">
    </label>
    @error('foto')
        <p class="text-xs text-danger mt-1">{{ $message }}</p>
    @enderror
</div>
