<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Banyak foto per produk toko. Foto pertama (urutan terkecil) adalah foto
 * utama dan disalin ke products.image_path, jadi daftar produk, keranjang,
 * dan meta SEO tetap memakai satu kolom itu.
 */
class FotoProdukService
{
    public const MAKS_FOTO = 8;

    public function __construct(
        private readonly FotoUploadService $fotoUploadService,
        private readonly UnduhGambarProdukService $unduhGambarProdukService,
    ) {}

    /**
     * Simpan foto baru ke storage: unggahan dulu, lalu foto impor dari CDN
     * Shopee. Kalau satu gagal, yang sudah tersimpan dihapus lagi dan
     * RuntimeException dilempar, supaya produk tidak setengah jadi.
     *
     * @param  array<int, UploadedFile>  $files
     * @param  array<int, string>  $urls
     * @return array<int, string> alamat foto yang tersimpan, urutannya sama
     */
    public function simpanBaru(array $files, array $urls): array
    {
        $tersimpan = [];

        try {
            foreach ($urls as $url) {
                $hasil = $this->unduhGambarProdukService->simpan($url, 'produk');

                if ($hasil === null) {
                    throw new RuntimeException('Foto dari Shopee tidak bisa diunduh. Unggah foto sendiri.');
                }

                $tersimpan[] = $hasil;
            }

            foreach ($files as $file) {
                $tersimpan[] = $this->fotoUploadService->simpan($file, 'produk');
            }
        } catch (RuntimeException $galat) {
            $this->hapusFile($tersimpan);

            throw $galat;
        }

        return $tersimpan;
    }

    /**
     * Susun ulang foto produk. $idUrut berisi id foto lama yang dipertahankan
     * sesuai urutan baru (id yang bukan milik produk ini diabaikan), foto lama
     * lainnya dihapus. Foto baru ditaruh setelahnya.
     *
     * @param  array<int, int>|null  $idUrut  null = tidak mengubah foto lama
     * @param  array<int, string>  $alamatBaru
     */
    public function terapkan(Product $product, ?array $idUrut, array $alamatBaru): void
    {
        $dihapus = [];

        DB::transaction(function () use ($product, $idUrut, $alamatBaru, &$dihapus) {
            $lama = $product->images()->get()->keyBy('id');

            $urutan = $idUrut === null
                ? $lama->keys()->all()
                : array_values(array_unique(array_filter(array_map('intval', $idUrut), fn (int $id) => $lama->has($id))));

            foreach ($lama as $id => $foto) {
                if (! in_array($id, $urutan, true)) {
                    $dihapus[] = $foto->path;
                    $foto->delete();
                }
            }

            $nomor = 0;

            foreach ($urutan as $id) {
                $lama[$id]->update(['urutan' => $nomor++]);
            }

            foreach ($alamatBaru as $alamat) {
                $product->images()->create(['path' => $alamat, 'urutan' => $nomor++]);
            }

            $product->forceFill(['image_path' => $product->images()->first()?->path])->save();
        });

        $this->hapusFile($dihapus);
        $product->unsetRelation('images');
    }

    /**
     * Hapus semua foto produk (file dan baris), dipakai saat produk dihapus.
     */
    public function hapusSemua(Product $product): void
    {
        $this->hapusFile(array_unique([...$product->images()->pluck('path')->all(), $product->image_path]));
        $product->images()->delete();
    }

    /**
     * @param  array<int, string|null>  $alamat
     */
    private function hapusFile(array $alamat): void
    {
        foreach ($alamat as $url) {
            $this->fotoUploadService->hapus($url);
        }
    }
}
