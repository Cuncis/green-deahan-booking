<?php

namespace App\Http\Controllers;

use App\Models\GaleriItem;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman /galeri (Blade) dan /v2/galeri (Inertia + React) di situs
 * korporat (greendeahan.com). Datanya dikelola lewat /superadmin/galeri,
 * lihat Superadmin\GaleriAdminController.
 */
class GaleriController extends Controller
{
    /**
     * @var array<string, array{label: string, icon: string, badgeClass: string}>
     */
    private const KATEGORI_TAB = [
        'semua' => ['label' => 'Semua', 'icon' => 'stadium', 'badgeClass' => 'text-brand bg-brand-50 border-brand-200'],
        'futsal' => ['label' => 'Futsal', 'icon' => 'futsal-goal', 'badgeClass' => 'text-brand bg-brand-50 border-brand-200'],
        'minisoccer' => ['label' => 'Mini Soccer', 'icon' => 'soccer-ball', 'badgeClass' => 'text-blue-700 bg-blue-50 border-blue-200'],
        'padel' => ['label' => 'Padel', 'icon' => 'padel-racket', 'badgeClass' => 'text-orange-700 bg-orange-50 border-orange-200'],
        'badminton' => ['label' => 'Badminton', 'icon' => 'shuttlecock', 'badgeClass' => 'text-purple-700 bg-purple-50 border-purple-200'],
        'proses' => ['label' => 'Proses Konstruksi', 'icon' => 'build-hammer', 'badgeClass' => 'text-stone-700 bg-stone-50 border-stone-200'],
    ];

    public function index(): View
    {
        return view('pages.galeri', $this->data());
    }

    public function v2(): Response
    {
        return Inertia::render('V2Galeri', $this->data());
    }

    /**
     * @return array{kategoriTab: array<string, array{label: string, icon: string, badgeClass: string}>, items: array<int, array<string, mixed>>}
     */
    private function data(): array
    {
        $items = GaleriItem::where('status_aktif', true)
            ->orderBy('urutan')
            ->orderByDesc('id')
            ->get()
            ->map(fn (GaleriItem $item) => [
                'id' => $item->id,
                'cat' => $item->kategori,
                'tall' => $item->tampilan_besar,
                'title' => $item->judul,
                'kota' => $item->kota,
                'material' => $item->material,
                'desc' => $item->deskripsi,
                'src' => $item->foto_url,
                'badgeLabel' => self::KATEGORI_TAB[$item->kategori]['label'],
                'badgeClass' => self::KATEGORI_TAB[$item->kategori]['badgeClass'],
            ])
            ->all();

        return [
            'kategoriTab' => self::KATEGORI_TAB,
            'items' => $items,
        ];
    }
}
