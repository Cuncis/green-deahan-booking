<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Ke admin: pesanan toko sudah dibayar dan perlu dibeli ke supplier.
 */
class PesananTokoDibayar extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Pesanan baru perlu dibeli ke supplier: {$this->order->code}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.toko.pesanan-dibayar', with: [
            'order' => $this->order->loadMissing('items'),
            'urlAdmin' => route('superadmin.toko.pesanan.show', $this->order),
        ]);
    }
}
