<?php

namespace App\Http\Requests\Superadmin\Toko;

use App\Models\OrderMessage;
use App\Services\PesanPesananTokoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Kabar manual ke pelanggan: email (dikirim sistem) atau WhatsApp (dicatat,
 * admin mengirimnya sendiri lewat tautan wa.me).
 */
class KirimPesanRequest extends FormRequest
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
            'channel' => ['required', Rule::in(OrderMessage::SALURAN)],
            'template' => ['required', Rule::in(array_keys(PesanPesananTokoService::TEMPLATE))],
            'subject' => ['nullable', 'required_if:channel,email', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subject.required_if' => 'Isi subjek email.',
            'body.required' => 'Isi pesan tidak boleh kosong.',
        ];
    }
}
