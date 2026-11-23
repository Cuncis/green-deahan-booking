<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Email peringatan: ada error baru di aplikasi. Dikirim langsung (tidak lewat
 * antrean) supaya tetap sampai walau antrean sedang bermasalah.
 */
class ErrorBaruMail extends Mailable
{
    /**
     * @param  array<string, mixed>  $info
     */
    public function __construct(public array $info) {}

    public function envelope(): Envelope
    {
        $judul = ! empty($this->info['uji'])
            ? 'Email uji peringatan error'
            : 'Error baru: '.($this->info['kelas'] ? class_basename($this->info['kelas']) : strtoupper((string) $this->info['level']));

        return new Envelope(subject: '['.config('app.name').'] '.$judul);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.monitoring.error-baru', with: [
            'info' => $this->info,
            'urlLog' => url((string) $this->info['url']),
        ]);
    }
}
