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
 * Ke pelanggan: pesanan sudah dikirim, berisi kurir dan nomor resi.
 */
class PesananTokoDikirim extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Pesanan {$this->order->code} sudah dikirim");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.toko.pesanan-dikirim', with: [
            'order' => $this->order->loadMissing('items'),
            'urlPesanan' => route('toko.pesanan', $this->order->code),
        ]);
    }
}
