<?php

namespace App\Http\Requests\Superadmin\Toko;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Menambah satu paket (kurir dan resi) ke pesanan yang sudah berstatus dikirim,
 * misalnya paket kedua dari supplier lain yang baru berangkat.
 */
class TambahResiRequest extends FormRequest
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
            'courier' => ['required', 'string', 'max:50'],
            'tracking_number' => ['required', 'string', 'max:100'],
            'shipped_from' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'courier.required' => 'Isi nama kurir.',
            'tracking_number.required' => 'Isi nomor resi.',
        ];
    }
}
