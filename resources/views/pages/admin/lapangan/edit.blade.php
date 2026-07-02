<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Edit Lapangan, {{ $tenant->nama_bisnis }}</title>

        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Lora:ital,wght@0,600;1,500&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-cream text-ink flex min-h-screen">

        <x-admin-sidebar :tenant="$tenant" />

        <main class="flex-1 px-6 md:px-8 py-6 max-w-3xl">
            <div class="mb-6">
                <h1 class="font-display text-xl font-semibold text-ink">Edit Lapangan</h1>
                <p class="text-sm text-ink-soft mt-0.5">{{ $lapangan->nama }}</p>
            </div>

            <x-card>
                <form method="POST" action="{{ route('admin.lapangan.update', $lapangan) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <x-lapangan-form
                        :lapangan="$lapangan"
                        :daftar-cabang="$daftarCabang"
                        :jenis-olahraga="$jenisOlahraga"
                        :tenant="$tenant"
                    />

                    <div class="flex gap-2">
                        <x-button type="submit">Simpan Perubahan</x-button>
                        <a href="{{ route('admin.lapangan') }}" class="inline-flex items-center justify-center rounded-lg font-sans font-semibold text-sm px-5 py-2.5 border-2 border-sand text-ink-mid hover:border-brown-light hover:text-brown transition-colors">
                            Batal
                        </a>
                    </div>
                </form>
            </x-card>
        </main>

        @livewireScripts
    </body>
</html>
