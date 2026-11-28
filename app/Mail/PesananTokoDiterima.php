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
 * Ke pelanggan: pembayaran sudah masuk. Berisi rincian pesanan dan link
 * halaman status, karena toko tidak punya akun pelanggan.
 */
class PesananTokoDiterima extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Pembayaran diterima, pesanan {$this->order->code} segera kami proses");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.toko.pesanan-diterima', with: [
            'order' => $this->order->loadMissing('items'),
            'layanan' => $this->order->layananPengiriman(),
            'alamat' => $this->order->alamatPengiriman(),
            'urlPesanan' => $this->order->urlStatus(),
            'urlLacak' => route('toko.lacak'),
        ]);
    }
}
