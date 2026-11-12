<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Pesanan toko online. Uang dalam rupiah (integer).
 */
#[Fillable([
    'code',
    'customer_name',
    'phone',
    'email',
    'address',
    'city',
    'province',
    'postal_code',
    'subtotal',
    'shipping_cost',
    'total',
    'status',
    'mayar_payment_id',
    'mayar_payment_link',
    'paid_at',
    'supplier_order_ref',
    'tracking_number',
    'courier',
    'admin_notes',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * Huruf tanpa yang mirip (0/O, 1/I/L) supaya mudah dibaca ulang pelanggan.
     */
    private const HURUF_KODE = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'integer',
            'shipping_cost' => 'integer',
            'total' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * GD-20261111-K7M2QX: tanggal untuk admin, 6 karakter acak (31^6, sekitar
     * 887 juta kemungkinan per hari) supaya halaman pesanan tidak bisa ditebak.
     */
    public static function buatKode(): string
    {
        do {
            $acak = collect(range(1, 6))->map(fn () => self::HURUF_KODE[random_int(0, strlen(self::HURUF_KODE) - 1)])->implode('');
            $kode = 'GD-'.now('Asia/Jakarta')->format('Ymd').'-'.$acak;
        } while (self::where('code', $kode)->exists());

        return $kode;
    }

    public function totalModal(): int
    {
        return (int) $this->items->sum(fn (OrderItem $item) => $item->unit_cost * $item->qty);
    }

    /**
     * Untung = total dibayar - ongkir - total modal barang.
     */
    public function untung(): int
    {
        return $this->total - $this->shipping_cost - $this->totalModal();
    }

    public function alamatLengkap(): string
    {
        return Str::of("{$this->customer_name}\n{$this->phone}\n{$this->address}\n{$this->city}, {$this->province} {$this->postal_code}")->toString();
    }
}
