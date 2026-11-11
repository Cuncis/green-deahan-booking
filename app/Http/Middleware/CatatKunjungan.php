<?php

namespace App\Http\Middleware;

use App\Models\KunjunganHalaman;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Mencatat tampilan halaman situs korporat untuk grafik pengunjung di
 * dashboard superadmin. Dijalankan setelah respons terkirim (terminate),
 * jadi tidak memperlambat halaman. Yang dilewati: selain GET, respons
 * selain 200 HTML, bot/crawler, prefetch browser, dan user yang sedang
 * login (tim internal). IP tidak disimpan: pengunjung dikenali lewat
 * hash IP + user agent + tanggal + APP_KEY, yang berganti tiap hari.
 */
class CatatKunjungan
{
    private const POLA_BOT = '/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegrambot|preview|curl|wget|python|headless|lighthouse|pingdom|uptime/i';

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! $this->perluDicatat($request, $response)) {
            return;
        }

        $hariIni = now(KunjunganHalaman::ZONA_WAKTU)->toDateString();

        try {
            KunjunganHalaman::create([
                'path' => $this->pathNormal($request),
                'tanggal' => $hariIni,
                'pengunjung_hash' => hash('sha256', implode('|', [
                    $request->ip(),
                    (string) $request->userAgent(),
                    $hariIni,
                    config('app.key'),
                ])),
                'perangkat' => $this->perangkat((string) $request->userAgent()),
                'sumber_host' => $this->sumberHost($request),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Gagal mencatat kunjungan halaman', ['path' => $request->path(), 'error' => $exception->getMessage()]);
        }
    }

    private function perluDicatat(Request $request, Response $response): bool
    {
        $userAgent = (string) $request->userAgent();

        return $request->isMethod('GET')
            && $response->getStatusCode() === 200
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            && ! $request->header('X-Inertia')
            && ! in_array(strtolower((string) ($request->header('Purpose') ?? $request->header('Sec-Purpose'))), ['prefetch', 'prerender'], true)
            && $userAgent !== ''
            && ! preg_match(self::POLA_BOT, $userAgent)
            && ! $request->user();
    }

    /**
     * "/blog/" dan "/blog" dihitung sebagai halaman yang sama, huruf kecil.
     */
    private function pathNormal(Request $request): string
    {
        $path = '/'.trim(strtolower($request->path()), '/');

        return mb_substr($path, 0, 255);
    }

    private function perangkat(string $userAgent): string
    {
        if (preg_match('/ipad|tablet|(android(?!.*mobile))/i', $userAgent)) {
            return 'tablet';
        }

        if (preg_match('/mobi|iphone|ipod|android/i', $userAgent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Host asal pengunjung (misal www.google.com). Null kalau langsung
     * (ketik alamat/bookmark) atau pindah antar halaman situs sendiri.
     */
    private function sumberHost(Request $request): ?string
    {
        $referer = (string) $request->headers->get('referer');
        $host = $referer !== '' ? parse_url($referer, PHP_URL_HOST) : null;

        if (! is_string($host) || $host === '' || $host === $request->getHost()) {
            return null;
        }

        return mb_substr(strtolower($host), 0, 100);
    }
}
