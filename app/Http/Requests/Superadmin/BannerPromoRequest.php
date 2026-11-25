<?php

namespace App\Http\Requests\Superadmin;

use App\Models\BannerPromo;
use App\Services\FotoUploadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BannerPromoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_superadmin;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:150'],
            // Gambar baru dari komputer, atau file yang sudah ada di pustaka Media (media_path).
            'foto' => [$this->route('banner') === null ? 'required_without:media_path' : 'sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'media_path' => ['nullable', 'string', 'max:500', function (string $atribut, mixed $nilai, \Closure $gagal) {
                $ekstensi = strtolower(pathinfo((string) $nilai, PATHINFO_EXTENSION));

                if (str_contains((string) $nilai, '..')
                    || ! in_array($ekstensi, ['jpg', 'jpeg', 'png', 'webp'], true)
                    || ! Storage::disk(FotoUploadService::namaDisk())->exists((string) $nilai)) {
                    $gagal('Gambar dari Media tidak ditemukan, pilih ulang.');
                }
            }],
            // Tautan penuh (http/https) atau alamat di situs ini yang diawali satu garis miring; skema lain (javascript:) ditolak.
            'tautan' => ['nullable', 'string', 'max:500', 'regex:#^(https?://|/(?!/))#i'],
            'label_tombol' => ['nullable', 'string', 'max:40'],
            'tampilkan_tombol' => ['required', 'boolean'],
            'mulai_pada' => ['nullable', 'date'],
            'berakhir_pada' => ['nullable', 'date', 'after:mulai_pada'],
            'target' => ['required', Rule::in(BannerPromo::TARGET)],
            'frekuensi' => ['required', Rule::in(BannerPromo::FREKUENSI)],
            'tunda_detik' => ['required', 'integer', 'min:0', 'max:30'],
            'status_aktif' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tautan.regex' => 'Tautan harus diawali https://, http://, atau satu garis miring untuk halaman di situs ini, misal /toko.',
            'berakhir_pada.after' => 'Waktu berakhir harus setelah waktu mulai.',
            'foto.required_without' => 'Pilih gambar dari komputer atau dari Media.',
            'foto.image' => 'File harus berupa gambar.',
            'foto.mimes' => 'Gambar harus berformat JPG, PNG, atau WebP.',
            'foto.max' => 'Ukuran gambar maksimal 4 MB.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'judul' => 'judul',
            'foto' => 'gambar',
            'tautan' => 'tautan',
            'label_tombol' => 'teks tombol',
            'mulai_pada' => 'waktu mulai',
            'berakhir_pada' => 'waktu berakhir',
            'target' => 'halaman tampil',
            'frekuensi' => 'frekuensi',
            'tunda_detik' => 'jeda tampil',
        ];
    }
}
