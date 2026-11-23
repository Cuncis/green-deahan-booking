<?php

namespace App\Console\Commands;

use App\Services\FotoAlamatService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

#[Signature('foto:pulihkan-lokal {--jalankan : Salin filenya (tanpa ini hanya menghitung)}')]
#[Description('Salin foto yang tersimpan di storage/app/private (disk "local") ke storage/app/public supaya alamat /storage/... bisa dibuka')]
class FotoPulihkanLokal extends Command
{
    public function __construct(private readonly FotoAlamatService $alamat)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $jalankan = (bool) $this->option('jalankan');
        $publik = Storage::disk('public');
        $privat = Storage::disk('local');
        $sudahAda = 0;
        $dipulihkan = [];
        $hilang = [];
        $gagal = [];

        foreach ($this->alamat->jalurStorage() as $foto) {
            if ($publik->exists($foto['path'])) {
                $sudahAda++;

                continue;
            }

            if (! $privat->exists($foto['path'])) {
                $hilang[$foto['path']] = $foto['sumber'];

                continue;
            }

            if ($jalankan) {
                try {
                    $publik->writeStream($foto['path'], $privat->readStream($foto['path']));
                } catch (Throwable $galat) {
                    $gagal[$foto['path']] = $galat->getMessage();

                    continue;
                }
            }

            $dipulihkan[$foto['path']] = $foto['sumber'];
        }

        $this->line("Sudah ada di storage/app/public: {$sudahAda}");
        $this->line(($jalankan ? 'Disalin dari storage/app/private: ' : 'Akan disalin dari storage/app/private: ').count($dipulihkan));

        foreach (array_slice($dipulihkan, 0, 20, true) as $path => $sumber) {
            $this->line("  {$path} ({$sumber})");
        }

        if ($hilang !== []) {
            $this->error('Tidak ditemukan di disk manapun ('.count($hilang).'), unggah ulang fotonya:');

            foreach (array_slice($hilang, 0, 20, true) as $path => $sumber) {
                $this->line("  {$path} ({$sumber})");
            }
        }

        foreach ($gagal as $path => $pesan) {
            $this->error("Gagal menyalin {$path}: {$pesan}");
        }

        if (! $jalankan && $dipulihkan !== []) {
            $this->warn('Ini baru hitungan. Tambahkan --jalankan untuk menyalin (file asli di private tidak dihapus).');
        }

        return $hilang === [] && $gagal === [] ? self::SUCCESS : self::FAILURE;
    }
}
