<?php

namespace App\Http\Controllers;

use App\Models\Artikel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman /blog dan /blog/{slug} di situs korporat (greendeahan.com),
 * Inertia + React dengan desain template Fuzio (resources/js/pages/Blog.tsx
 * dan BlogShow.tsx). Desain Blade lama ada di /v2/blog (noindex). Datanya
 * dikelola lewat /superadmin/artikel, lihat Superadmin\ArtikelAdminController.
 */
class BlogController extends Controller
{
    /**
     * Kecepatan baca rata-rata untuk estimasi "x Menit Baca".
     */
    private const KATA_PER_MENIT = 200;

    public function index(): Response
    {
        $artikel = Artikel::where('status_aktif', true)
            ->orderByDesc('tanggal_terbit')
            ->get();

        return Inertia::render('Blog', [
            'meta' => [
                'title' => 'Blog - Green Deahan Sport',
                'description' => 'Tips membangun lapangan, panduan memilih material, estimasi biaya, dan insight bisnis lapangan olahraga di Indonesia.',
            ],
            'artikel' => $artikel->map(fn (Artikel $item) => $this->kartu($item))->all(),
        ]);
    }

    public function show(string $slug): Response
    {
        $artikel = Artikel::where('slug', $slug)->where('status_aktif', true)->firstOrFail();

        $terkait = $this->terkait($artikel);

        return Inertia::render('BlogShow', [
            'meta' => [
                'title' => $artikel->judul.' - Green Deahan Sport',
                'description' => $artikel->ringkasan,
                'image' => $artikel->foto_url,
                'type' => 'article',
            ],
            'artikel' => [
                ...$this->kartu($artikel),
                'konten' => $artikel->konten,
                'tanggal' => $artikel->tanggal_terbit->locale('id')->translatedFormat('j F Y'),
            ],
            'terkait' => $terkait->map(fn (Artikel $item) => $this->kartu($item))->all(),
        ]);
    }

    /**
     * Desain Blade lama, dipindah ke /v2/blog (noindex).
     */
    public function indexV2(): View
    {
        $artikel = Artikel::where('status_aktif', true)
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

        return view('pages.blog', ['artikel' => $artikel]);
    }

    /**
     * Desain Blade lama untuk detail artikel, dipindah ke /v2/blog/{slug} (noindex).
     */
    public function showV2(string $slug): View
    {
        $artikel = Artikel::where('slug', $slug)->where('status_aktif', true)->firstOrFail();

        return view('pages.blog-show', [
            'artikel' => $artikel,
            'terkait' => $this->terkait($artikel),
        ]);
    }

    /**
     * Sampai 3 artikel terbit lain dengan kategori yang sama, terbaru dulu.
     *
     * @return Collection<int, Artikel>
     */
    private function terkait(Artikel $artikel): Collection
    {
        return Artikel::where('status_aktif', true)
            ->where('kategori', $artikel->kategori)
            ->where('id', '!=', $artikel->id)
            ->orderByDesc('tanggal_terbit')
            ->take(3)
            ->get();
    }

    /**
     * Data kartu artikel (dipakai di daftar blog dan "Artikel Terkait").
     *
     * @return array{id: int, judul: string, slug: string, kategori: string, ringkasan: string, fotoUrl: string|null, menitBaca: int}
     */
    private function kartu(Artikel $item): array
    {
        return [
            'id' => $item->id,
            'judul' => $item->judul,
            'slug' => $item->slug,
            'kategori' => $item->kategori,
            'ringkasan' => $item->ringkasan,
            'fotoUrl' => $item->foto_url,
            'menitBaca' => $this->menitBaca((string) $item->konten),
        ];
    }

    /**
     * Estimasi waktu baca: jumlah kata / 200, dibulatkan, minimal 1 menit.
     */
    private function menitBaca(string $konten): int
    {
        $jumlahKata = str_word_count(strip_tags($konten));

        return max(1, (int) round($jumlahKata / self::KATA_PER_MENIT));
    }
}
