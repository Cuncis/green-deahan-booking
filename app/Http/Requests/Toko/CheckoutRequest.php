<?php

namespace App\Http\Requests\Toko;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Data pengiriman pelanggan. Sengaja TIDAK ada field harga atau total:
 * semua dihitung ulang di server dari database.
 */
class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => preg_replace('/[^\d+]/', '', (string) $this->input('phone')),
            'postal_code' => preg_replace('/\D/', '', (string) $this->input('postal_code')),
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'min:2', 'max:150'],
            'phone' => ['required', 'regex:/^(\+62|62|0)8\d{7,12}$/'],
            'email' => ['required', 'email', 'max:150'],
            'address' => ['required', 'string', 'min:10', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'digits:5'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':Attribute wajib diisi.',
            'email' => 'Format email belum benar, misal nama@email.com.',
            'max' => ':Attribute terlalu panjang (maksimal :max karakter).',
            'customer_name.min' => 'Isi nama lengkap.',
            'phone.regex' => 'Isi nomor HP yang aktif, misal 081234567890.',
            'postal_code.digits' => 'Kode pos terdiri dari 5 angka.',
            'address.min' => 'Tulis alamat lengkap (jalan, nomor rumah, RT/RW, kelurahan).',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'customer_name' => 'nama',
            'phone' => 'nomor HP',
            'address' => 'alamat',
            'city' => 'kota',
            'province' => 'provinsi',
            'postal_code' => 'kode pos',
        ];
    }
}
