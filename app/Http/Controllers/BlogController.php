<?php

namespace App\Http\Controllers;

use App\Models\Artikel;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman /blog (Blade) dan /v2/blog (Inertia + React) di situs korporat
 * (greendeahan.com). Datanya dikelola lewat /superadmin/artikel, lihat
 * Superadmin\ArtikelAdminController.
 */
class BlogController extends Controller
{
    public function index(): View
    {
        return view('pages.blog', [
            'artikel' => $this->artikelAktif(),
        ]);
    }

    public function v2(): Response
    {
        return Inertia::render('V2Blog', [
            'artikel' => $this->artikelAktif(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function artikelAktif(): array
    {
        return Artikel::where('status_aktif', true)
            ->orderByDesc('tanggal_terbit')
            ->get()
            ->map(fn (Artikel $item) => [
                'judul' => $item->judul,
                'slug' => $item->slug,
                'kategori' => $item->kategori,
                'ringkasan' => $item->ringkasan,
                'fotoUrl' => $item->foto_url,
                'tanggal' => $item->tanggal_terbit->translatedFormat('d F Y'),
            ])
            ->all();
    }

    public function show(string $slug): View
    {
        $artikel = Artikel::where('slug', $slug)->where('status_aktif', true)->firstOrFail();

        $terkait = Artikel::where('status_aktif', true)
            ->where('kategori', $artikel->kategori)
            ->where('id', '!=', $artikel->id)
            ->orderByDesc('tanggal_terbit')
            ->take(3)
            ->get();

        return view('pages.blog-show', [
            'artikel' => $artikel,
            'terkait' => $terkait,
        ]);
    }
}
