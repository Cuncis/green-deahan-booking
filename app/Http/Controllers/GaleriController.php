<?php

namespace App\Http\Controllers;

use App\Models\GaleriItem;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman /galeri di situs korporat (greendeahan.com), Inertia + React dengan
 * desain template Fuzio (resources/js/pages/Galeri.tsx). Desain Blade lama
 * ada di /v2/galeri (noindex). Datanya dikelola lewat /superadmin/galeri,
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

    /**
     * Proyek yang masih dikerjakan (kota "On Progress") tidak ditampilkan di
     * galeri baru, sama seperti versi Fuzio.
     */
    private const KOTA_DALAM_PENGERJAAN = 'On Progress';

    public function index(): Response
    {
        $items = GaleriItem::where('status_aktif', true)
            ->where('kota', '!=', self::KOTA_DALAM_PENGERJAAN)
            ->orderBy('urutan')
            ->orderByDesc('id')
            ->get()
            ->map(fn (GaleriItem $item) => [
                'id' => $item->id,
                'kategori' => $item->kategori,
                'judul' => $item->judul,
                'kota' => $item->kota,
                'material' => $item->material,
                'deskripsi' => $item->deskripsi,
                'foto' => $item->foto_url,
                'besar' => (bool) $item->tampilan_besar,
            ])
            ->all();

        return Inertia::render('Galeri', [
            'meta' => [
                'title' => 'Galeri Proyek - Green Deahan Sport',
                'description' => 'Portofolio lapangan futsal, mini soccer, padel, dan badminton yang sudah dikerjakan Green Deahan Sport di seluruh Indonesia.',
            ],
            'kategori' => collect(self::KATEGORI_TAB)
                ->map(fn (array $tab, string $id) => ['id' => $id, 'label' => $tab['label']])
                ->values()
                ->all(),
            'items' => $items,
        ]);
    }

    /**
     * Desain Blade lama, dipindah ke /v2/galeri (noindex).
     */
    public function indexV2(): View
    {
        return view('pages.galeri', $this->data());
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
