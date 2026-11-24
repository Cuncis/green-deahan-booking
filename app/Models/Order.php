<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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
    'district',
    'village',
    'province',
    'postal_code',
    'wilayah_kode',
    'subtotal',
    'shipping_cost',
    'promo_code',
    'discount',
    'courier_code',
    'courier_service_code',
    'courier_service_name',
    'shipping_duration',
    'total',
    'status',
    'mayar_payment_id',
    'mayar_payment_link',
    'paid_at',
    'supplier_order_ref',
    'admin_notes',
    'cancel_reason',
    'cancelled_at',
    'refunded_at',
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
            'discount' => 'integer',
            'total' => 'integer',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    /**
     * Dana pelanggan: null kalau tidak ada yang perlu dikembalikan (pesanan
     * tidak batal atau belum pernah dibayar), "diproses" sampai admin menandai
     * refund selesai, lalu "selesai".
     */
    public function statusRefund(): ?string
    {
        if ($this->status !== OrderStatus::Cancelled || $this->paid_at === null) {
            return null;
        }

        return $this->refunded_at === null ? 'diproses' : 'selesai';
    }

    public function perluRefund(): bool
    {
        return $this->statusRefund() === 'diproses';
    }

    /**
     * Pesanan batal yang sudah dibayar dan belum ditandai refund selesai.
     *
     * @param  Builder<Order>  $query
     */
    #[Scope]
    protected function menungguRefund(Builder $query): void
    {
        $query->where('status', OrderStatus::Cancelled)->whereNotNull('paid_at')->whereNull('refunded_at');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Riwayat kabar manual ke pelanggan, terbaru dulu.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(OrderMessage::class)->latest('id');
    }

    /**
     * Paket pengiriman (kurir dan resi), urut sesuai waktu dicatat.
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(OrderShipment::class)->orderBy('id');
    }

    /**
     * Paket pengiriman sebagai array siap tampil (admin, halaman pesanan, email).
     *
     * @return array<int, array{id: int, kurir: string, resi: string, dikirimDari: ?string}>
     */
    public function daftarPengiriman(): array
    {
        return $this->shipments->map(fn (OrderShipment $paket) => [
            'id' => $paket->id,
            'kurir' => $paket->courier,
            'resi' => $paket->tracking_number,
            'dikirimDari' => $paket->shipped_from,
        ])->all();
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

    /**
     * Layanan kurir yang dipilih pelanggan, misal "JNE Reguler (2 - 3 hari)". Null untuk pesanan dengan ongkir flat.
     */
    public function layananPengiriman(): ?string
    {
        if (! $this->courier_service_name) {
            return null;
        }

        return $this->shipping_duration ? "{$this->courier_service_name} ({$this->shipping_duration})" : $this->courier_service_name;
    }

    /**
     * Pesanan memakai ongkir tetap karena tarif Biteship gagal diambil saat checkout.
     */
    public function ongkirDarurat(): bool
    {
        return $this->courier_code === 'darurat';
    }

    /**
     * Alamat tujuan dalam satu baris. Kelurahan dan kecamatan dilewati untuk pesanan lama yang belum menyimpannya.
     */
    public function alamatPengiriman(): string
    {
        return collect([
            $this->address,
            $this->village ? "Kel. {$this->village}" : null,
            $this->district ? "Kec. {$this->district}" : null,
            $this->city,
            "{$this->province} {$this->postal_code}",
        ])->filter()->implode(', ');
    }

    public function alamatLengkap(): string
    {
        return Str::of("{$this->customer_name}\n{$this->phone}\n{$this->alamatPengiriman()}")->toString();
    }
}
