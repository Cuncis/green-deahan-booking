<?php

namespace App\Http\Requests\Toko;

use App\Exceptions\BiteshipException;
use App\Models\PromoCode;
use App\Models\Wilayah;
use App\Services\KeranjangService;
use App\Services\OngkirTokoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Data pengiriman pelanggan. Sengaja TIDAK ada field harga atau total:
 * semua dihitung ulang di server dari database. Alamat wilayah juga tidak
 * dipercaya dari browser: yang dikirim hanya kode kelurahan, lalu provinsi,
 * kota, kecamatan, kelurahan, dan kode pos diambil dari tabel wilayah.
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
            'promo_code' => PromoCode::normalisasi((string) $this->input('promo_code')),
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
            'wilayah_kode' => ['required', 'string', Rule::exists('wilayah', 'kode')->where('level', Wilayah::LEVEL_KELURAHAN)],
            // Dipakai hanya kalau kelurahan yang dipilih belum punya kode pos di data.
            'postal_code' => ['nullable', 'digits:5'],
            // "kodekurir:kodelayanan" dari daftar ongkir; dicek ulang ke Biteship di after().
            'layanan' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9_]+:[a-z0-9_]+$/i'],
            // Diperiksa dan dihitung ulang di CheckoutController saat pesanan dibuat.
            'promo_code' => ['nullable', 'string', 'max:40'],
        ];
    }

    /**
     * Layanan kurir terpilih hasil hitung ulang server (null kalau ongkir flat).
     *
     * @var array<string, mixed>|null
     */
    private ?array $layananTerpilih = null;

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('wilayah_kode')) {
                    return;
                }

                if ($this->wilayah()?->kodepos === null && ! preg_match('/^\d{5}$/', (string) $this->input('postal_code'))) {
                    $validator->errors()->add('postal_code', 'Isi kode pos 5 angka, kode pos kelurahan ini belum ada di data kami.');

                    return;
                }

                $this->validasiLayanan($validator);
            },
        ];
    }

    /**
     * Ongkir dihitung ulang dari Biteship di server. Yang diterima dari browser
     * hanya kode layanan, bukan harganya.
     */
    private function validasiLayanan(Validator $validator): void
    {
        $ongkir = app(OngkirTokoService::class);

        // Ongkir flat (Biteship belum dikonfigurasi) atau keranjang kosong (controller yang mengalihkan).
        if (! $ongkir->aktif() || app(KeranjangService::class)->isi()->isEmpty()) {
            return;
        }

        if (! $this->filled('layanan')) {
            $validator->errors()->add('layanan', 'Pilih layanan pengiriman.');

            return;
        }

        try {
            $this->layananTerpilih = $ongkir->pilih((string) $this->input('layanan'), $this->kodePosTujuan());
        } catch (BiteshipException $galat) {
            report($galat);
            $validator->errors()->add('layanan', 'Ongkir belum bisa dihitung, coba lagi sebentar lagi.');

            return;
        }

        if ($this->layananTerpilih === null) {
            $validator->errors()->add('layanan', 'Pilihan pengiriman ini sudah tidak tersedia, pilih ulang.');
        }
    }

    private function kodePosTujuan(): string
    {
        return $this->wilayah()?->kodepos ?? (string) $this->input('postal_code');
    }

    /**
     * Kelurahan yang dipilih pelanggan.
     */
    public function wilayah(): ?Wilayah
    {
        return Wilayah::query()->where('level', Wilayah::LEVEL_KELURAHAN)->find((string) $this->input('wilayah_kode'));
    }

    /**
     * Data yang masuk ke pesanan: isian pelanggan ditambah alamat wilayah
     * hasil pencarian di database (bukan kiriman browser).
     *
     * @return array<string, string|int>
     */
    public function dataPesanan(): array
    {
        $kelurahan = $this->wilayah();
        [$kodeProvinsi, $kodeKota, $kodeKecamatan] = $this->kodeInduk($kelurahan->kode);
        $nama = Wilayah::query()->whereIn('kode', [$kodeProvinsi, $kodeKota, $kodeKecamatan])->pluck('nama', 'kode');

        return [
            ...$this->safe()->only(['customer_name', 'phone', 'email', 'address']),
            'province' => $nama->get($kodeProvinsi, ''),
            'city' => $nama->get($kodeKota, ''),
            'district' => $nama->get($kodeKecamatan, ''),
            'village' => $kelurahan->nama,
            'postal_code' => $kelurahan->kodepos ?? (string) $this->input('postal_code'),
            'wilayah_kode' => $kelurahan->kode,
            ...($this->layananTerpilih === null ? [] : [
                'shipping_cost' => (int) $this->layananTerpilih['price'],
                'courier_code' => $this->layananTerpilih['courier_code'],
                'courier_service_code' => $this->layananTerpilih['service_code'],
                'courier_service_name' => $this->layananTerpilih['label'],
                'shipping_duration' => $this->layananTerpilih['duration'],
            ]),
        ];
    }

    /**
     * Kode provinsi, kota, dan kecamatan dari kode kelurahan.
     *
     * @return array<int, string>
     */
    private function kodeInduk(string $kode): array
    {
        $bagian = explode('.', $kode);

        return [$bagian[0], "{$bagian[0]}.{$bagian[1]}", "{$bagian[0]}.{$bagian[1]}.{$bagian[2]}"];
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
            'layanan.regex' => 'Pilihan pengiriman tidak valid.',
            'wilayah_kode.required' => 'Pilih provinsi, kota/kabupaten, kecamatan, dan kelurahan.',
            'wilayah_kode.exists' => 'Kelurahan yang dipilih tidak dikenal, pilih ulang dari daftar.',
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
            'wilayah_kode' => 'kelurahan',
            'layanan' => 'layanan pengiriman',
            'postal_code' => 'kode pos',
            'promo_code' => 'kode promo',
        ];
    }
}
