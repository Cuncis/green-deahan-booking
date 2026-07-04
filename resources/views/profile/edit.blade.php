<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Profil & Kata Sandi, {{ $tenant->nama_bisnis }}</title>

        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Lora:ital,wght@0,600;1,500&display=swap" rel="stylesheet">

        @vite('resources/css/app.css')
        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-cream text-ink flex min-h-screen">

        <x-admin-sidebar :tenant="$tenant" />

        <main class="flex-1 px-6 md:px-8 py-6 max-w-3xl">
            <div class="mb-6">
                <h1 class="font-display text-xl font-semibold text-ink">Profil & Kata Sandi</h1>
                <p class="text-sm text-ink-soft mt-0.5">Kelola informasi akun dan keamanan login kamu.</p>
            </div>

            <div class="space-y-6">
                <x-card>
                    @include('profile.partials.update-profile-information-form')
                </x-card>

                <x-card>
                    @include('profile.partials.update-password-form')
                </x-card>

                <x-card>
                    @include('profile.partials.delete-user-form')
                </x-card>
            </div>
        </main>

        @livewireScripts
    </body>
</html>
