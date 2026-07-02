<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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

        // Di belakang load balancer/proxy (Nginx, Laravel Cloud, dst), request
        // yang sampai ke aplikasi biasanya sudah http meski customer mengakses
        // https, jadi URL::forceScheme mencegah asset()/route() menghasilkan
        // link http:// yang salah di production.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $this->registerRateLimiters();
    }

    /**
     * Rate limit khusus endpoint booking publik (routes/api.php), supaya
     * satu IP tidak bisa spam cek slot atau coba booking berkali-kali.
     * Response saat kena limit sengaja JSON berbahasa Indonesia karena
     * endpoint ini dipanggil dari halaman booking (fetch/AJAX), bukan
     * dibuka langsung di browser.
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('booking-slot', fn (Request $request) => Limit::perMinute(60)
            ->by($request->ip())
            ->response(fn (Request $request, array $headers) => response()->json([
                'message' => 'Terlalu banyak permintaan, coba lagi sebentar lagi.',
            ], 429, $headers)));

        RateLimiter::for('booking-hold', fn (Request $request) => Limit::perMinute(10)
            ->by($request->ip())
            ->response(fn (Request $request, array $headers) => response()->json([
                'message' => 'Terlalu banyak percobaan pilih jam, tunggu sebentar sebelum coba lagi.',
            ], 429, $headers)));

        RateLimiter::for('booking-store', fn (Request $request) => Limit::perMinute(5)
            ->by($request->ip())
            ->response(fn (Request $request, array $headers) => response()->json([
                'message' => 'Terlalu banyak percobaan booking, tunggu sebentar sebelum coba lagi.',
            ], 429, $headers)));
    }
}
