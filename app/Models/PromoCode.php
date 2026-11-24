<?php

namespace App\Models;

use Database\Factories\PromoCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Kode promo toko online. `value` berarti persen (1-100) untuk tipe percent,
 * atau rupiah untuk tipe fixed. Uang dalam rupiah (integer).
 */
#[Fillable([
    'code',
    'description',
    'type',
    'value',
    'max_discount',
    'min_subtotal',
    'quota',
    'starts_on',
    'ends_on',
    'is_active',
])]
class PromoCode extends Model
{
    /** @use HasFactory<PromoCodeFactory> */
    use HasFactory;

    public const PERCENT = 'percent';

    public const FIXED = 'fixed';

    public const TIPE = [self::PERCENT, self::FIXED];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'max_discount' => 'integer',
            'min_subtotal' => 'integer',
            'quota' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Kode selalu disimpan dan dicari dalam huruf besar tanpa spasi.
     */
    public static function normalisasi(string $kode): string
    {
        return strtoupper(preg_replace('/\s+/', '', $kode) ?? '');
    }

    /**
     * Potongan untuk subtotal ini sebelum dibatasi sisa bayar minimal.
     */
    public function hitungPotongan(int $subtotal): int
    {
        if ($this->type === self::PERCENT) {
            $potongan = intdiv($subtotal * $this->value, 100);

            return $this->max_discount === null ? $potongan : min($potongan, $this->max_discount);
        }

        return $this->value;
    }

    /**
     * Ringkasan aturan untuk pelanggan, misal "10% (maks Rp50.000), min. belanja Rp200.000".
     */
    public function ringkasan(): string
    {
        $rupiah = fn (int $nilai) => 'Rp'.number_format($nilai, 0, ',', '.');
        $bagian = [$this->type === self::PERCENT
            ? "{$this->value}%".($this->max_discount !== null ? ' (maks '.$rupiah($this->max_discount).')' : '')
            : 'potongan '.$rupiah($this->value)];

        if ($this->min_subtotal > 0) {
            $bagian[] = 'min. belanja '.$rupiah($this->min_subtotal);
        }

        return implode(', ', $bagian);
    }
}
