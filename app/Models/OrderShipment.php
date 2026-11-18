<?php

namespace App\Models;

use Database\Factories\OrderShipmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu paket pengiriman milik pesanan: kurir, nomor resi, dan cabang/kota
 * pengirim (dicatat admin dari supplier).
 */
#[Fillable(['order_id', 'courier', 'tracking_number', 'shipped_from'])]
class OrderShipment extends Model
{
    /** @use HasFactory<OrderShipmentFactory> */
    use HasFactory;

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
