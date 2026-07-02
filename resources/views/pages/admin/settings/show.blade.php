<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Pengaturan, {{ $tenant->nama_bisnis }}</title>

        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Lora:ital,wght@0,600;1,500&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-cream text-ink flex min-h-screen">

        <x-admin-sidebar :tenant="$tenant" />

        <main class="flex-1 px-6 md:px-8 py-6 max-w-3xl">
            <div class="mb-6">
                <h1 class="font-display text-xl font-semibold text-ink">Pengaturan</h1>
                <p class="text-sm text-ink-soft mt-0.5">Atur profil bisnis dan preferensi {{ $tenant->nama_bisnis }}.</p>
            </div>

            @if (session('success'))
                <div class="mb-5 rounded-lg border border-green/30 bg-green-pale px-4 py-3 text-sm text-green">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.pengaturan.update') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <x-card>
                    <div class="text-xs font-bold uppercase tracking-wide text-green mb-4">Profil Bisnis</div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Nama Bisnis" name="nama_bisnis" value="{{ old('nama_bisnis', $tenant->nama_bisnis) }}" required />
                        <x-input label="WhatsApp Admin" name="whatsapp_admin" value="{{ old('whatsapp_admin', $tenant->whatsapp_admin) }}" required />
                        <x-input label="Email Admin" name="email_admin" type="email" value="{{ old('email_admin', $tenant->email_admin) }}" />
                    </div>
                    @error('nama_bisnis') <p class="text-xs text-danger mt-2">{{ $message }}</p> @enderror
                    @error('whatsapp_admin') <p class="text-xs text-danger mt-2">{{ $message }}</p> @enderror
                    @error('email_admin') <p class="text-xs text-danger mt-2">{{ $message }}</p> @enderror
                </x-card>

                <x-card>
                    <div class="text-xs font-bold uppercase tracking-wide text-green mb-4">Branding</div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Logo</label>

                            @if ($tenant->logo_url)
                                <img src="{{ $tenant->logo_url }}" alt="Logo {{ $tenant->nama_bisnis }}" class="w-20 h-20 object-cover rounded-lg border border-cream-deep mb-2">
                            @endif

                            <label class="flex items-center gap-2 cursor-pointer rounded-lg border-2 border-dashed border-cream-deep px-4 py-3 text-sm text-ink-mid hover:border-brown-light w-fit">
                                <x-icon name="upload" size="16" />
                                <span>Pilih logo baru</span>
                                <input type="file" name="logo" accept="image/*" class="hidden" onchange="this.closest('label').querySelector('span').textContent = this.files[0]?.name ?? 'Pilih logo baru'">
                            </label>
                            @error('logo') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wide text-ink-soft mb-1">Warna Utama</label>
                            <input
                                type="color"
                                name="warna_utama"
                                value="{{ old('warna_utama', $tenant->warna_utama ?? '#3A6B4A') }}"
                                class="h-11 w-20 rounded-lg border border-cream-deep bg-cream p-1 cursor-pointer"
                            />
                            @error('warna_utama') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </x-card>

                @if ($tenant->punyaFitur('multi_cabang'))
                    @foreach ($daftarCabang as $cabang)
                        <x-card>
                            <div class="text-xs font-bold uppercase tracking-wide text-green mb-4">Cabang, {{ $cabang->nama_cabang }}</div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <x-input
                                    label="Nama Cabang"
                                    name="cabang[{{ $cabang->id }}][nama_cabang]"
                                    value="{{ old("cabang.{$cabang->id}.nama_cabang", $cabang->nama_cabang) }}"
                                    required
                                />
                                <x-input
                                    label="Kota"
                                    name="cabang[{{ $cabang->id }}][kota]"
                                    value="{{ old("cabang.{$cabang->id}.kota", $cabang->kota) }}"
                                    required
                                />
                                <div class="md:col-span-2">
                                    <x-input
                                        label="Alamat"
                                        name="cabang[{{ $cabang->id }}][alamat]"
                                        value="{{ old("cabang.{$cabang->id}.alamat", $cabang->alamat) }}"
                                        required
                                    />
                                </div>
                                <x-input
                                    label="Jam Buka"
                                    name="cabang[{{ $cabang->id }}][jam_buka]"
                                    type="time"
                                    value="{{ old("cabang.{$cabang->id}.jam_buka", substr($cabang->jam_buka, 0, 5)) }}"
                                    required
                                />
                                <x-input
                                    label="Jam Tutup"
                                    name="cabang[{{ $cabang->id }}][jam_tutup]"
                                    type="time"
                                    value="{{ old("cabang.{$cabang->id}.jam_tutup", substr($cabang->jam_tutup, 0, 5)) }}"
                                    required
                                />
                            </div>
                            @error("cabang.{$cabang->id}.nama_cabang") <p class="text-xs text-danger mt-2">{{ $message }}</p> @enderror
                            @error("cabang.{$cabang->id}.alamat") <p class="text-xs text-danger mt-2">{{ $message }}</p> @enderror
                            @error("cabang.{$cabang->id}.kota") <p class="text-xs text-danger mt-2">{{ $message }}</p> @enderror
                            @error("cabang.{$cabang->id}.jam_buka") <p class="text-xs text-danger mt-2">{{ $message }}</p> @enderror
                            @error("cabang.{$cabang->id}.jam_tutup") <p class="text-xs text-danger mt-2">{{ $message }}</p> @enderror
                        </x-card>
                    @endforeach
                @else
                    <x-card>
                        <div class="text-xs font-bold uppercase tracking-wide text-green mb-4">Jam Operasional Lapangan</div>

                        @php $cabangDefault = $daftarCabang->first(); @endphp

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-input
                                label="Jam Buka"
                                name="jam_buka"
                                type="time"
                                value="{{ old('jam_buka', $cabangDefault ? substr($cabangDefault->jam_buka, 0, 5) : '08:00') }}"
                                required
                            />
                            <x-input
                                label="Jam Tutup"
                                name="jam_tutup"
                                type="time"
                                value="{{ old('jam_tutup', $cabangDefault ? substr($cabangDefault->jam_tutup, 0, 5) : '22:00') }}"
                                required
                            />
                        </div>
                        @error('jam_buka') <p class="text-xs text-danger mt-2">{{ $message }}</p> @enderror
                        @error('jam_tutup') <p class="text-xs text-danger mt-2">{{ $message }}</p> @enderror
                    </x-card>
                @endif

                <x-button type="submit">Simpan Pengaturan</x-button>
            </form>
        </main>

        @livewireScripts
    </body>
</html>
