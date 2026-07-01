<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $tenant->nama_bisnis }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50">
        <main class="max-w-3xl mx-auto px-4 py-10">
            <h1 class="text-2xl font-semibold text-gray-900">{{ $tenant->nama_bisnis }}</h1>
            <p class="mt-1 text-sm text-gray-500">Pilih lapangan untuk mulai booking.</p>

            <div class="mt-8 grid gap-4 sm:grid-cols-2">
                @forelse ($lapangan as $item)
                    <div class="rounded-lg border border-gray-200 bg-white p-5">
                        <h2 class="font-semibold text-gray-900">{{ $item->nama }}</h2>
                        <p class="text-sm text-gray-500">{{ $item->jenis_olahraga }}</p>
                        <p class="mt-2 text-sm text-gray-700">Rp{{ number_format($item->harga_per_jam, 0, ',', '.') }} / jam</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Belum ada lapangan tersedia.</p>
                @endforelse
            </div>
        </main>
    </body>
</html>
