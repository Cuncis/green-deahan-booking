<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\GaleriItem;
use App\Services\FotoUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kelola portofolio proyek di halaman /galeri (situs korporat
 * greendeahan.com). Bukan fitur tenant, jadi tidak ada tenant_id sama
 * sekali di sini, lihat references/multi-tenant.md.
 */
class GaleriAdminController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const KATEGORI = ['futsal', 'minisoccer', 'padel', 'badminton', 'proses'];

    /**
     * @var array<string, string>
     */
    private const LABEL_KATEGORI = [
        'futsal' => 'Futsal',
        'minisoccer' => 'Mini Soccer',
        'padel' => 'Padel',
        'badminton' => 'Badminton',
        'proses' => 'Proses Konstruksi',
    ];

    public function __construct(private readonly FotoUploadService $fotoUploadService) {}

    public function index(Request $request): Response
    {
        $filterKategori = in_array($request->query('kategori'), self::KATEGORI, true) ? $request->query('kategori') : null;

        return Inertia::render('superadmin/Galeri/Index', [
            'item' => GaleriItem::orderBy('urutan')
                ->orderByDesc('id')
                ->when($filterKategori, fn ($q, $kategori) => $q->where('kategori', $kategori))
                ->get()
                ->map(fn (GaleriItem $item) => [
                    'id' => $item->id,
                    'judul' => $item->judul,
                    'kategori' => self::LABEL_KATEGORI[$item->kategori] ?? $item->kategori,
                    'kota' => $item->kota,
                    'fotoUrl' => $item->foto_url,
                    'tampil' => (bool) $item->status_aktif,
                    'besar' => (bool) $item->tampilan_besar,
                    'editUrl' => route('superadmin.galeri.edit', $item),
                    'hapusUrl' => route('superadmin.galeri.destroy', $item),
                ])
                ->all(),
            'filterKategori' => $filterKategori,
            'pilihanKategori' => $this->pilihanKategori(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('superadmin/Galeri/Form', [
            'item' => null,
            ...$this->dataForm(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request, fotoWajib: true);
        $data['foto_url'] = $this->fotoUploadService->simpan($request->file('foto'), 'galeri');
        $data['tampilan_besar'] = $request->boolean('tampilan_besar');
        $data['status_aktif'] = true;

        $item = GaleriItem::create($data);

        return redirect()->route('superadmin.galeri')
            ->with('success', "Item galeri \"{$item->judul}\" berhasil ditambahkan.");
    }

    public function edit(GaleriItem $galeri): Response
    {
        return Inertia::render('superadmin/Galeri/Form', [
            'item' => [
                'judul' => $galeri->judul,
                'kategori' => $galeri->kategori,
                'kota' => $galeri->kota,
                'material' => $galeri->material,
                'deskripsi' => $galeri->deskripsi,
                'urutan' => (int) $galeri->urutan,
                'tampilan_besar' => (bool) $galeri->tampilan_besar,
                'status_aktif' => (bool) $galeri->status_aktif,
                'fotoUrl' => $galeri->foto_url,
                'updateUrl' => route('superadmin.galeri.update', $galeri),
            ],
            ...$this->dataForm(),
        ]);
    }

    public function update(Request $request, GaleriItem $galeri): RedirectResponse
    {
        $data = $this->validateData($request, fotoWajib: false);
        $data['tampilan_besar'] = $request->boolean('tampilan_besar');
        $data['status_aktif'] = $request->boolean('status_aktif');

        if ($request->hasFile('foto')) {
            $this->fotoUploadService->hapus($galeri->foto_url);
            $data['foto_url'] = $this->fotoUploadService->simpan($request->file('foto'), 'galeri');
        }

        $galeri->update($data);

        return redirect()->route('superadmin.galeri')
            ->with('success', "Item galeri \"{$galeri->judul}\" berhasil diperbarui.");
    }

    public function destroy(GaleriItem $galeri): RedirectResponse
    {
        $this->fotoUploadService->hapus($galeri->foto_url);
        $judul = $galeri->judul;
        $galeri->delete();

        return redirect()->route('superadmin.galeri')
            ->with('success', "Item galeri \"{$judul}\" berhasil dihapus.");
    }

    /**
     * @return array<int, array{nilai: string, label: string}>
     */
    private function pilihanKategori(): array
    {
        return collect(self::KATEGORI)->map(fn (string $kategori) => ['nilai' => $kategori, 'label' => self::LABEL_KATEGORI[$kategori]])->all();
    }

    /**
     * @return array{pilihanKategori: array<int, array{nilai: string, label: string}>, simpanUrl: string, kembaliUrl: string}
     */
    private function dataForm(): array
    {
        return [
            'pilihanKategori' => $this->pilihanKategori(),
            'simpanUrl' => route('superadmin.galeri.store'),
            'kembaliUrl' => route('superadmin.galeri'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request, bool $fotoWajib): array
    {
        return $request->validate([
            'kategori' => ['required', Rule::in(self::KATEGORI)],
            'judul' => ['required', 'string', 'max:150'],
            'kota' => ['required', 'string', 'max:150'],
            'material' => ['required', 'string', 'max:150'],
            'deskripsi' => ['required', 'string', 'max:1000'],
            'urutan' => ['nullable', 'integer', 'min:0'],
            'foto' => [$fotoWajib ? 'required' : 'nullable', 'image', 'max:4096'],
        ]);
    }
}
