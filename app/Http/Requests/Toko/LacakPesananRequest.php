<?php

namespace App\Http\Requests\Toko;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form lacak pesanan: kode pesanan plus email atau nomor HP yang dipakai
 * saat checkout, supaya pesanan tidak bisa dibuka hanya dengan kodenya.
 */
class LacakPesananRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'kode' => ['required', 'string', 'max:30'],
            'kontak' => ['required', 'string', 'max:150'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kode.required' => 'Isi kode pesanan.',
            'kontak.required' => 'Isi email atau nomor HP yang dipakai saat checkout.',
        ];
    }

    /**
     * Kode tanpa spasi dan huruf besar; "20261127-ABC123" juga diterima.
     */
    public function kode(): string
    {
        $kode = strtoupper((string) preg_replace('/\s+/', '', (string) $this->input('kode')));

        return str_starts_with($kode, 'GD-') ? $kode : 'GD-'.$kode;
    }
}
