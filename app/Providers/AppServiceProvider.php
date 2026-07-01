<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Supaya resources/views/layouts/*.blade.php bisa dipakai sebagai
        // <x-layouts.nama>, bukan cuma lewat @extends/@include klasik.
        Blade::anonymousComponentPath(resource_path('views/layouts'), 'layouts');
    }
}
