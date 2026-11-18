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
 * Ke pelanggan: pesanan yang sudah dibayar dibatalkan (misalnya stok supplier
 * habis), berisi alasan dan nominal yang akan dikembalikan.
 */
class PesananTokoDibatalkan extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Pesanan {$this->order->code} dibatalkan");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.toko.pesanan-dibatalkan', with: [
            'order' => $this->order,
            'urlPesanan' => route('toko.pesanan', $this->order->code),
        ]);
    }
}
