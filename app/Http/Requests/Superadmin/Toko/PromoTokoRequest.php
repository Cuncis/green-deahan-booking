<?php

namespace App\Http\Requests\Superadmin\Toko;

use App\Models\PromoCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PromoTokoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_superadmin;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => PromoCode::normalisasi((string) $this->input('code'))]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $tipe = $this->input('type');

        return [
            // Saat edit, kode lama dipertahankan (lihat PromoTokoController::update), jadi hanya dicek pada pembuatan.
            'code' => $this->route('promo') === null
                ? ['required', 'string', 'min:3', 'max:40', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('promo_codes', 'code')]
                : ['nullable', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:150'],
            'type' => ['required', Rule::in(PromoCode::TIPE)],
            'value' => ['required', 'integer', 'min:1', $tipe === PromoCode::PERCENT ? 'max:100' : 'max:100000000'],
            'max_discount' => ['exclude_unless:type,percent', 'nullable', 'integer', 'min:1', 'max:100000000'],
            'min_subtotal' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'quota' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * Batas maksimal diskon hanya bermakna untuk tipe persen.
     *
     * @return array<string, mixed>
     */
    public function dataPromo(): array
    {
        $data = $this->validated();
        $data['min_subtotal'] = (int) ($data['min_subtotal'] ?? 0);

        if ($data['type'] !== PromoCode::PERCENT) {
            $data['max_discount'] = null;
        }

        return $data;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => 'Kode hanya boleh huruf, angka, strip, dan garis bawah, tanpa spasi.',
            'code.unique' => 'Kode ini sudah dipakai promo lain.',
            'value.max' => 'Persen diskon maksimal 100.',
            'ends_on.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code' => 'kode',
            'description' => 'keterangan',
            'type' => 'jenis',
            'value' => 'nilai',
            'max_discount' => 'maksimal diskon',
            'min_subtotal' => 'minimal belanja',
            'quota' => 'kuota',
            'starts_on' => 'tanggal mulai',
            'ends_on' => 'tanggal berakhir',
        ];
    }
}
