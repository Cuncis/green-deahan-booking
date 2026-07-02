@props(['lapangan' => null, 'daftarCabang', 'jenisOlahraga', 'tenant'])

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
    <x-input label="Nama Lapangan" name="nama" value="{{ old('nama', $lapangan->nama ?? '') }}" required />

    <div>
        <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Jenis Olahraga</label>
        <select name="jenis_olahraga" class="w-full rounded-lg border border-cream-deep bg-cream px-4 py-2.5 text-sm text-ink focus:border-green focus:outline-none focus:ring-1 focus:ring-green">
            @foreach ($jenisOlahraga as $jenis)
                <option value="{{ $jenis }}" @selected(old('jenis_olahraga', $lapangan->jenis_olahraga ?? '') === $jenis)>{{ ucfirst($jenis) }}</option>
            @endforeach
        </select>
        @error('jenis_olahraga')
            <p class="text-xs text-danger mt-1">{{ $message }}</p>
        @enderror
    </div>

    <x-input
        label="Harga Normal per Jam (Rp)"
        name="harga_per_jam"
        type="number"
        value="{{ old('harga_per_jam', $lapangan->harga_per_jam ?? '') }}"
        required
    />

    <x-input
        label="Harga Jam Sibuk (Rp, opsional)"
        name="harga_jam_sibuk"
        type="number"
        value="{{ old('harga_jam_sibuk', $lapangan->harga_jam_sibuk ?? '') }}"
    />

    @if ($daftarCabang->isNotEmpty())
        <div>
            <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Cabang</label>
            <select name="cabang_id" class="w-full rounded-lg border border-cream-deep bg-cream px-4 py-2.5 text-sm text-ink focus:border-green focus:outline-none focus:ring-1 focus:ring-green">
                @foreach ($daftarCabang as $cabang)
                    <option value="{{ $cabang->id }}" @selected((int) old('cabang_id', $lapangan->cabang_id ?? 0) === $cabang->id)>{{ $cabang->nama_cabang }}</option>
                @endforeach
            </select>
            @error('cabang_id')
                <p class="text-xs text-danger mt-1">{{ $message }}</p>
            @enderror
        </div>
    @endif

    @if ($lapangan)
        <div>
            <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Status</label>
            <select name="status_aktif" class="w-full rounded-lg border border-cream-deep bg-cream px-4 py-2.5 text-sm text-ink focus:border-green focus:outline-none focus:ring-1 focus:ring-green">
                <option value="1" @selected(old('status_aktif', (int) $lapangan->status_aktif) == 1)>Aktif</option>
                <option value="0" @selected(old('status_aktif', (int) $lapangan->status_aktif) == 0)>Nonaktif</option>
            </select>
        </div>
    @endif
</div>

<div class="mb-5">
    <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Deskripsi</label>
    <textarea
        name="deskripsi"
        rows="4"
        class="w-full rounded-lg border border-cream-deep bg-cream px-4 py-2.5 text-sm text-ink placeholder:text-ink-soft focus:border-green focus:outline-none focus:ring-1 focus:ring-green"
        placeholder="Ceritakan fasilitas dan keunggulan lapangan ini"
    >{{ old('deskripsi', $lapangan->deskripsi ?? '') }}</textarea>
    @error('deskripsi')
        <p class="text-xs text-danger mt-1">{{ $message }}</p>
    @enderror
</div>

<div class="mb-6">
    <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Foto Lapangan</label>

    @if ($lapangan?->foto_url)
        <img src="{{ $lapangan->foto_url }}" alt="{{ $lapangan->nama }}" class="w-40 h-28 object-cover rounded-lg border border-cream-deep mb-2">
    @endif

    <label class="flex items-center gap-2 cursor-pointer rounded-lg border-2 border-dashed border-cream-deep px-4 py-3 text-sm text-ink-mid hover:border-brown-light w-fit">
        <x-icon name="upload" size="16" />
        <span>Pilih foto baru</span>
        <input type="file" name="foto" accept="image/*" class="hidden" onchange="this.closest('label').querySelector('span').textContent = this.files[0]?.name ?? 'Pilih foto baru'">
    </label>
    @error('foto')
        <p class="text-xs text-danger mt-1">{{ $message }}</p>
    @enderror
</div>
