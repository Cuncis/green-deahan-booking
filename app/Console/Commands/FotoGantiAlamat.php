<?php

namespace App\Console\Commands;

use App\Services\FotoAlamatService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('foto:ganti-alamat {dari : Awalan alamat lama, mis. https://greendeahan.com/storage} {ke : Awalan alamat baru, mis. https://foto.greendeahan.com} {--jalankan : Terapkan perubahan (tanpa ini hanya menghitung)}')]
#[Description('Ganti awalan alamat foto di database (artikel, galeri, lapangan, logo, produk, gambar OG)')]
class FotoGantiAlamat extends Command
{
    public function __construct(private readonly FotoAlamatService $alamat)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dari = rtrim((string) $this->argument('dari'), '/');
        $ke = rtrim((string) $this->argument('ke'), '/');

        if ($dari === '' || $ke === '' || $dari === $ke) {
            $this->error('Awalan lama dan baru harus diisi dan tidak boleh sama.');

            return self::FAILURE;
        }

        if (! preg_match('#^https?://#i', $dari) || ! preg_match('#^https?://#i', $ke)) {
            $this->error('Awalan harus berupa alamat lengkap yang diawali http:// atau https://.');

            return self::FAILURE;
        }

        $jalankan = (bool) $this->option('jalankan');
        $hasil = $this->alamat->ganti($dari, $ke, $jalankan);

        if ($hasil === []) {
            $this->warn("Tidak ada alamat foto yang diawali {$dari}.");

            return self::SUCCESS;
        }

        foreach ($hasil as $sumber => $jumlah) {
            $this->line("  {$sumber}: {$jumlah} baris");
        }

        $total = array_sum($hasil);
        $jalankan
            ? $this->info("{$total} alamat diganti dari {$dari} ke {$ke}. File-nya tidak dipindah: pastikan sudah ada di alamat baru.")
            : $this->warn("{$total} alamat akan diganti. Ini baru hitungan, tambahkan --jalankan untuk menerapkan (cadangkan database dulu).");

        return self::SUCCESS;
    }
}
