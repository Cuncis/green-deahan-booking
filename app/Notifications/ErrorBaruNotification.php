<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Lonceng superadmin: ada error baru di aplikasi. Hanya disimpan di
 * database (notifikasi email dikirim terpisah oleh PeringatanErrorService,
 * ke penerima yang dipilih).
 */
class ErrorBaruNotification extends Notification
{
    /**
     * @param  array{level: string, pesan: string, kelas: string|null, lokasi: string|null, waktu: string, lingkungan: string, url: string}  $info
     */
    public function __construct(private readonly array $info) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tipe' => 'error_baru',
            'judul' => 'Error baru: '.($this->info['kelas'] ? class_basename($this->info['kelas']) : strtoupper($this->info['level'])),
            'pesan' => mb_substr($this->info['pesan'], 0, 160),
            'lokasi' => $this->info['lokasi'],
            'level' => $this->info['level'],
            'waktu' => $this->info['waktu'],
            'url' => $this->info['url'],
        ];
    }
}
