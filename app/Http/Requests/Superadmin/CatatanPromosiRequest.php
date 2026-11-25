<?php

namespace App\Http\Requests\Superadmin;

use App\Models\CatatanPromosi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatatanPromosiRequest extends FormRequest
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
            'kategori' => ['required', Rule::in(array_keys(CatatanPromosi::KATEGORI))],
            'isi' => ['required', 'string', 'max:30000'],
            'disematkan' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'isi.max' => 'Isi catatan terlalu panjang (maksimal 30.000 karakter). Pecah jadi dua catatan.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['isi' => 'isi catatan'];
    }
}
