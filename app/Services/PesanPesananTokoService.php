<?php

namespace App\Services;

use App\Models\Order;

/**
 * Template kabar status pesanan untuk pelanggan, terisi dari data pesanan.
 * Teks polos yang sama dipakai untuk email dan WhatsApp; admin boleh
 * mengeditnya sebelum dikirim.
 */
class PesanPesananTokoService
{
    /**
     * @var array<string, string>
     */
    public const TEMPLATE = [
        'diterima' => 'Pesanan diterima',
        'diproses' => 'Sedang diproses',
        'dikirim' => 'Sudah dikirim',
        'selesai' => 'Selesai',
        'dibatalkan' => 'Dibatalkan',
        'bebas' => 'Pesan bebas',
    ];

    /**
     * Semua template untuk satu pesanan.
     *
     * @return array<int, array{kunci: string, label: string, subjek: string, isi: string}>
     */
    public function daftar(Order $order): array
    {
        return array_map(fn (string $kunci, string $label) => [
            'kunci' => $kunci,
            'label' => $label,
            'subjek' => $this->subjek($kunci, $order),
            'isi' => $this->isi($kunci, $order),
        ], array_keys(self::TEMPLATE), array_values(self::TEMPLATE));
    }

    private function subjek(string $kunci, Order $order): string
    {
        return match ($kunci) {
            'diterima' => "Pesanan {$order->code} sudah kami terima",
            'diproses' => "Pesanan {$order->code} sedang diproses",
            'dikirim' => "Pesanan {$order->code} sudah dikirim",
            'selesai' => "Pesanan {$order->code} sudah selesai",
            'dibatalkan' => "Pesanan {$order->code} dibatalkan",
            default => "Tentang pesanan {$order->code}",
        };
    }

    private function isi(string $kunci, Order $order): string
    {
        $sapaan = "Halo {$order->customer_name},";
        $status = 'Status pesanan: '.route('toko.pesanan', $order->code);
        $penutup = 'Green Deahan Sport';

        $badan = match ($kunci) {
            'diterima' => "pesanan {$order->code} sudah kami terima dan pembayaranmu sudah masuk. Kami segera memprosesnya.\n\n{$status}",
            'diproses' => "pesanan {$order->code} sedang kami proses. Kami kabari lagi begitu barangnya dikirim.\n\n{$status}",
            'dikirim' => "pesanan {$order->code} sudah dikirim.\n\n{$this->daftarResi($order)}\n\nLacak paket di situs kurir memakai nomor resi di atas.\n{$status}",
            'selesai' => "pesanan {$order->code} sudah selesai. Terima kasih sudah belanja di Green Deahan Sport. Kalau ada kendala dengan barangnya, balas pesan ini.",
            'dibatalkan' => $this->isiDibatalkan($order, $status),
            default => "ada yang ingin kami sampaikan mengenai pesanan {$order->code}.\n\n(Tulis pesanmu di sini.)\n\n{$status}",
        };

        return "{$sapaan} {$badan}\n\n{$penutup}";
    }

    private function daftarResi(Order $order): string
    {
        $paket = $order->daftarPengiriman();

        if ($paket === []) {
            return '(Nomor resi belum diisi di admin, isi dulu sebelum mengirim pesan ini.)';
        }

        $baris = [];

        foreach ($paket as $indeks => $item) {
            $awal = count($paket) > 1 ? 'Paket '.($indeks + 1).': ' : 'Resi: ';
            $dari = $item['dikirimDari'] ? " (dari {$item['dikirimDari']})" : '';
            $baris[] = "{$awal}{$item['kurir']} {$item['resi']}{$dari}";
        }

        $catatan = count($paket) > 1 ? "\n\nPesananmu dikirim dalam ".count($paket).' paket dari lokasi berbeda, jadi paket bisa tiba di hari yang berbeda.' : '';

        return implode("\n", $baris).$catatan;
    }

    private function isiDibatalkan(Order $order, string $status): string
    {
        $alasan = $order->cancel_reason ?: 'terpaksa kami batalkan';
        $dana = $order->paid_at
            ? "\n\nPembayaranmu sebesar Rp".number_format($order->total, 0, ',', '.').' akan kami kembalikan ke metode pembayaran yang kamu pakai. Kalau belum diterima dalam beberapa hari, balas pesan ini dengan menyebut kode pesanan.'
            : '';

        return "mohon maaf, pesanan {$order->code} dibatalkan. Alasan: {$alasan}.{$dana}\n\n{$status}";
    }

    /**
     * Nomor WhatsApp internasional tanpa tanda (08123 jadi 628123), atau null kalau tidak valid.
     */
    public static function nomorWhatsApp(string $telepon): ?string
    {
        $angka = preg_replace('/\D/', '', $telepon) ?? '';

        if (str_starts_with($angka, '0')) {
            $angka = '62'.substr($angka, 1);
        }

        return preg_match('/^62\d{8,13}$/', $angka) ? $angka : null;
    }
}
