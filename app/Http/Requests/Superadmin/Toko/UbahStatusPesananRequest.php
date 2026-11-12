<?php

namespace App\Http\Requests\Superadmin\Toko;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Alur status pesanan: paid -> ordered_to_supplier (isi ref supplier) ->
 * shipped (isi kurir dan resi) -> completed. Lompatan lain ditolak.
 */
class UbahStatusPesananRequest extends FormRequest
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
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'supplier_order_ref' => ['nullable', 'required_if:status,ordered_to_supplier', 'string', 'max:150'],
            'courier' => ['nullable', 'required_if:status,shipped', 'string', 'max:50'],
            'tracking_number' => ['nullable', 'required_if:status,shipped', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $tujuan = OrderStatus::tryFrom((string) $this->input('status'));

                if ($tujuan && ! $this->route('order')->status->bisaMenjadi($tujuan)) {
                    $validator->errors()->add('status', "Pesanan berstatus \"{$this->route('order')->status->label()}\" tidak bisa diubah menjadi \"{$tujuan->label()}\".");
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'supplier_order_ref.required_if' => 'Isi nomor pesanan di supplier.',
            'courier.required_if' => 'Isi nama kurir.',
            'tracking_number.required_if' => 'Isi nomor resi.',
        ];
    }
}
