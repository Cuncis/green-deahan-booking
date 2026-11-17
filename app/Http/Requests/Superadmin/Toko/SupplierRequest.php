<?php

namespace App\Http\Requests\Superadmin\Toko;

use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150'],
            'platform' => ['required', Rule::in(Supplier::PLATFORM)],
            'shop_url' => ['nullable', 'url', 'max:500'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'origin_postal_code' => ['nullable', 'digits:5'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['origin_postal_code' => 'kode pos asal'];
    }
}
