<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Jalan tiap hari (lihat routes/console.php), tangani tenant aktif yang
 * tanggal_berakhir-nya sudah lewat. Masa tenggang HARI_MASA_TENGGANG hari
 * sejak tanggal_berakhir sebelum benar-benar dinonaktifkan, dengan satu
 * email peringatan terakhir begitu masa tenggang mulai. Dihitung dari
 * tanggal_berakhir di tabel tenants (sumber kebenaran akses sesungguhnya),
 * bukan dari kapan tagihan perpanjangan dibuat/dikirim.
 */
#[Signature('tenant:nonaktifkan-tenant-kadaluarsa')]
#[Description('Kirim peringatan terakhir lalu nonaktifkan tenant yang tidak bayar perpanjangan setelah masa tenggang')]
class NonaktifkanTenantKadaluarsa extends Command
{
    private const HARI_MASA_TENGGANG = 7;

    public function handle(): int
    {
        $tenantTerlambat = Tenant::where('status_aktif', true)
            ->whereNotNull('tanggal_berakhir')
            ->where('tanggal_berakhir', '<', today())
            ->get();

        $dinonaktifkan = 0;
        $diperingatkan = 0;

        foreach ($tenantTerlambat as $tenant) {
            $hariTerlambat = $tenant->tanggal_berakhir->diffInDays(today());

            if ($hariTerlambat >= self::HARI_MASA_TENGGANG) {
                $tenant->update(['status_aktif' => false]);
                $dinonaktifkan++;

                continue;
            }

            $tagihanAktif = $tenant->tagihanPerpanjangan()
                ->where('status', 'menunggu')
                ->latest('dikirim_pada')
                ->first();

            if ($tagihanAktif && $tagihanAktif->peringatan_terakhir_terkirim_at === null) {
                $sisaHari = self::HARI_MASA_TENGGANG - $hariTerlambat;
                $tagihanAktif->kirimPeringatanTerakhir($sisaHari);
                $tagihanAktif->update(['peringatan_terakhir_terkirim_at' => now()]);
                $diperingatkan++;
            }
        }

        $this->info("Dinonaktifkan: {$dinonaktifkan}, peringatan terakhir terkirim: {$diperingatkan}.");

        return self::SUCCESS;
    }
}
