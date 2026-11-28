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
 * Ke pelanggan: pesanan sudah dikirim, berisi semua paket (kurir dan nomor resi).
 * `tambahan` = admin baru mencatat paket lain setelah pesanan berstatus dikirim.
 */
class PesananTokoDikirim extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public bool $tambahan = false) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->tambahan
            ? "Ada paket baru untuk pesanan {$this->order->code}"
            : "Pesanan {$this->order->code} sudah dikirim");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.toko.pesanan-dikirim', with: [
            'order' => $this->order->loadMissing('items', 'shipments'),
            'pengiriman' => $this->order->daftarPengiriman(),
            'tambahan' => $this->tambahan,
            'urlPesanan' => $this->order->urlStatus(),
        ]);
    }
}
