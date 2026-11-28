<?php

namespace App\Http\Requests\Superadmin\Toko;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\FotoProdukService;
use App\Services\ImporProdukHtmlService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProdukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_superadmin;
    }

    /**
     * Baris spesifikasi yang nama dan isinya sama-sama kosong dibuang, dan
     * spasi di pinggir dipangkas, supaya baris kosong dari form tidak jadi galat.
     */
    protected function prepareForValidation(): void
    {
        $this->bersihkanVarian();

        $specs = $this->input('specs');

        if (! is_array($specs)) {
            return;
        }

        $this->merge(['specs' => collect($specs)
            ->map(fn ($baris) => is_array($baris) ? ['nama' => trim((string) ($baris['nama'] ?? '')), 'nilai' => trim((string) ($baris['nilai'] ?? ''))] : $baris)
            ->reject(fn ($baris) => is_array($baris) && $baris['nama'] === '' && $baris['nilai'] === '')
            ->values()
            ->all()]);
    }

    /**
     * Baris varian yang namanya kosong dibuang, harga kosong jadi null
     * (berarti sama dengan harga produk), dan "tersedia" dibaca sebagai boolean.
     */
    private function bersihkanVarian(): void
    {
        $varian = $this->input('variants');

        if (! is_array($varian)) {
            return;
        }

        $this->merge(['variants' => collect($varian)
            ->filter(fn ($baris) => is_array($baris))
            ->map(fn (array $baris) => [
                'id' => ($baris['id'] ?? '') === '' ? null : $baris['id'],
                'name' => trim((string) ($baris['name'] ?? '')),
                'sell_price' => ($baris['sell_price'] ?? '') === '' ? null : $baris['sell_price'],
                'cost_price' => ($baris['cost_price'] ?? '') === '' ? null : $baris['cost_price'],
                'is_available' => filter_var($baris['is_available'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ])
            ->reject(fn (array $baris) => $baris['name'] === '')
            ->values()
            ->all()]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $produk = $this->route('product');

        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:170', 'alpha_dash', Rule::unique('products', 'slug')->ignore($produk?->id)],
            'category' => ['required', Rule::in(array_keys(Product::KATEGORI))],
            'unit' => ['required', Rule::in(Product::SATUAN)],
            'badge' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:3000'],
            'variant_label' => ['nullable', 'string', 'max:30'],
            'variants' => ['nullable', 'array', 'max:'.ProductVariant::MAKS],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.name' => ['required', 'string', 'max:80', 'distinct:ignore_case'],
            'variants.*.sell_price' => ['nullable', 'integer', 'min:1000', 'max:100000000'],
            'variants.*.cost_price' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'variants.*.is_available' => ['boolean'],
            'specs' => ['nullable', 'array', 'max:'.Product::MAKS_SPESIFIKASI],
            'specs.*.nama' => ['required', 'string', 'max:60'],
            'specs.*.nilai' => ['required', 'string', 'max:150'],
            'sell_price' => ['required', 'integer', 'min:1000', 'max:100000000'],
            'compare_price' => ['nullable', 'integer', 'gt:sell_price', 'max:100000000'],
            'cost_price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'supplier_product_url' => ['nullable', 'url', 'max:500'],
            'weight_gram' => ['required', 'integer', 'min:0', 'max:100000'],
            'origin_postal_code' => ['nullable', 'digits:5'],
            'is_active' => ['required', 'boolean'],
            'foto' => ['nullable', 'image', 'max:4096'],
            'foto_baru' => ['nullable', 'array', 'max:'.FotoProdukService::MAKS_FOTO],
            'foto_baru.*' => ['image', 'max:4096'],
            'foto_diatur' => ['nullable', 'boolean'],
            'foto_ada' => ['nullable', 'array', 'max:'.FotoProdukService::MAKS_FOTO],
            'foto_ada.*' => ['integer'],
            'gambar_url' => ['nullable', 'string', 'max:500', $this->aturanAlamatGambar()],
            'gambar_urls' => ['nullable', 'array', 'max:'.FotoProdukService::MAKS_FOTO],
            'gambar_urls.*' => ['string', 'max:500', $this->aturanAlamatGambar()],
            // Foto pilihan admin sebagai foto utama: foto lama (id), foto impor, atau unggahan baru (urutan di daftarnya).
            'foto_utama' => ['nullable', 'string', 'regex:/^(ada|impor|baru):\d+$/'],
        ];
    }

    /**
     * Jumlah foto (lama yang dipertahankan, unggahan, dan impor) tidak boleh
     * melebihi batas per produk.
     *
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $jumlah = count($this->input('foto_ada', [])) + count($this->fotoBaru()) + count($this->gambarUrl());

                if ($jumlah > FotoProdukService::MAKS_FOTO) {
                    $validator->errors()->add('foto', 'Foto produk maksimal '.FotoProdukService::MAKS_FOTO.'.');
                }
            },
            // Id varian yang dikirim harus milik produk ini, bukan milik produk lain.
            function (Validator $validator) {
                $dikirim = collect($this->input('variants', []))->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

                if ($dikirim === []) {
                    return;
                }

                $milikProduk = $this->route('product')?->variants()->pluck('id')->all() ?? [];

                if (array_diff($dikirim, $milikProduk) !== []) {
                    $validator->errors()->add('variants', 'Ada varian yang bukan milik produk ini, muat ulang halaman.');
                }
            },
        ];
    }

    /**
     * Foto yang diunggah, termasuk field lama "foto".
     *
     * @return array<int, UploadedFile>
     */
    public function fotoBaru(): array
    {
        return array_values(array_filter([...(array) $this->file('foto_baru'), $this->file('foto')]));
    }

    /**
     * Foto utama pilihan admin, dipetakan ke daftar yang dipakai
     * FotoProdukService::terapkan(): foto lama lewat id-nya, foto baru lewat
     * urutannya di hasil simpanBaru() (impor Shopee dulu, lalu unggahan).
     *
     * @return array{jenis: 'ada'|'baru', nilai: int}|null
     */
    public function fotoUtama(): ?array
    {
        if (! preg_match('/^(ada|impor|baru):(\d+)$/', (string) $this->input('foto_utama'), $cocok)) {
            return null;
        }

        $nilai = (int) $cocok[2];

        return match ($cocok[1]) {
            'ada' => ['jenis' => 'ada', 'nilai' => $nilai],
            'impor' => $nilai < count($this->gambarUrl()) ? ['jenis' => 'baru', 'nilai' => $nilai] : null,
            default => $nilai < count($this->fotoBaru()) ? ['jenis' => 'baru', 'nilai' => count($this->gambarUrl()) + $nilai] : null,
        };
    }

    /**
     * Alamat foto impor Shopee, termasuk field lama "gambar_url".
     *
     * @return array<int, string>
     */
    public function gambarUrl(): array
    {
        return array_values(array_unique(array_filter([...(array) $this->input('gambar_urls', []), $this->input('gambar_url')], 'is_string')));
    }

    /**
     * @return \Closure(string, mixed, \Closure): mixed
     */
    private function aturanAlamatGambar(): \Closure
    {
        return fn (string $atribut, mixed $nilai, \Closure $gagal) => ImporProdukHtmlService::gambarDiizinkan(is_string($nilai) ? $nilai : null) ?: $gagal('Alamat foto impor tidak diizinkan.');
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama produk',
            'sell_price' => 'harga jual',
            'compare_price' => 'harga coret',
            'cost_price' => 'harga modal',
            'supplier_product_url' => 'link produk supplier',
            'weight_gram' => 'berat',
            'origin_postal_code' => 'kode pos asal',
            'variants.*.name' => 'nama varian',
            'variants.*.sell_price' => 'harga jual varian',
            'variants.*.cost_price' => 'harga modal varian',
            'variant_label' => 'judul pilihan varian',
            'specs.*.nama' => 'nama spesifikasi',
            'specs.*.nilai' => 'isi spesifikasi',
        ];
    }
}
