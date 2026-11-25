<?php

namespace App\Http\Requests\Superadmin;

use App\Models\BannerPromo;
use Illuminate\Foundation\Http\FormRequest;
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
            'foto' => [$this->route('banner') === null ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            // Tautan penuh (http/https) atau alamat di situs ini yang diawali satu garis miring; skema lain (javascript:) ditolak.
            'tautan' => ['nullable', 'string', 'max:500', 'regex:#^(https?://|/(?!/))#i'],
            'label_tombol' => ['nullable', 'string', 'max:40'],
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
