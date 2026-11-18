<?php

namespace App\Models;

use Database\Factories\OrderMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu kabar manual ke pelanggan. Email dikirim sistem; WhatsApp dicatat saat
 * admin membuka tautan wa.me (pengiriman sebenarnya dilakukan admin di aplikasi WhatsApp).
 */
#[Fillable(['order_id', 'user_id', 'channel', 'template', 'subject', 'body'])]
class OrderMessage extends Model
{
    /** @use HasFactory<OrderMessageFactory> */
    use HasFactory;

    public const SALURAN = ['email', 'whatsapp'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
