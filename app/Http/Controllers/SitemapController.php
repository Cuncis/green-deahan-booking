<?php

namespace App\Http\Controllers;

use App\Models\Artikel;
use Illuminate\Http\Response;

/**
 * Sitemap situs korporat (greendeahan.com). Halaman /v2 (homepage lama)
 * sengaja tidak dimasukkan, lihat juga meta noindex di
 * resources/views/pages/home.blade.php. Host diambil dari request, jadi
 * www.greendeahan.com menghasilkan URL www dan greendeahan.com menghasilkan
 * URL tanpa www. /sistem-booking dan /daftar hanya masuk selama SISTEM_BOOKING_PUBLIK
 * aktif (config/situs.php); halaman toko belum dimasukkan.
 */
class SitemapController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const HALAMAN_STATIS = ['/', '/tentang-kami', '/bangun-lapangan', '/perawatan', '/upgrade-lapangan', '/galeri', '/blog', '/kontak'];

    public function __invoke(): Response
    {
        $artikel = Artikel::where('status_aktif', true)
            ->orderByDesc('tanggal_terbit')
            ->get(['slug', 'updated_at']);

        $xml = view('sitemap', [
            'halamanStatis' => collect(self::HALAMAN_STATIS)
                ->when(config('situs.sistem_booking_publik'), fn ($halaman) => $halaman->push('/sistem-booking', '/daftar'))
                ->map(fn (string $path) => url($path)),
            'artikel' => $artikel,
        ])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }
}
