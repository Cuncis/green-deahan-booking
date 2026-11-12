<?php

namespace App\Models;

use Database\Factories\MayarWebhookLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['event', 'payment_id', 'order_id', 'payload', 'result', 'message', 'processed_at'])]
class MayarWebhookLog extends Model
{
    /** @use HasFactory<MayarWebhookLogFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
