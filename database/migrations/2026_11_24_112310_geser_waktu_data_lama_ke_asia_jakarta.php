<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Zona waktu aplikasi berpindah dari UTC ke Asia/Jakarta (WIB, UTC+7). Waktu
 * yang sudah tersimpan di database adalah UTC, jadi semua kolom datetime dan
 * timestamp digeser +7 jam supaya tetap menunjuk detik yang sama (kolom date
 * dan time tidak disentuh, begitu juga kolom angka seperti sesi dan antrean).
 *
 * Instalasi baru (belum ada satu pun pengguna) dilewati: tidak ada data lama,
 * dan baris yang dibuat migrasi lain sudah dalam WIB. Migrasi ini hanya boleh
 * dijalankan SEKALI per database; down() menggesernya kembali -7 jam.
 */
return new class extends Migration
{
    private const JAM = 7;

    public function up(): void
    {
        $this->geser(self::JAM);
    }

    public function down(): void
    {
        $this->geser(-self::JAM);
    }

    private function geser(int $jam): void
    {
        if (! DB::table('users')->exists()) {
            return;
        }

        DB::transaction(function () use ($jam) {
            foreach (Schema::getTables() as $tabel) {
                foreach (Schema::getColumns($tabel['name']) as $kolom) {
                    if (in_array($kolom['type_name'], ['datetime', 'timestamp'], true)) {
                        $this->geserKolom($tabel['name'], $kolom['name'], $jam);
                    }
                }
            }
        });
    }

    private function geserKolom(string $tabel, string $kolom, int $jam): void
    {
        $tata = DB::connection()->getQueryGrammar();
        $t = $tata->wrapTable($tabel);
        $k = $tata->wrap($kolom);
        $abs = abs($jam);

        $ekspresi = match (DB::getDriverName()) {
            'sqlite' => "datetime({$k}, '".($jam >= 0 ? '+' : '-')."{$abs} hours')",
            'pgsql' => "{$k} ".($jam >= 0 ? '+' : '-')." interval '{$abs} hours'",
            default => ($jam >= 0 ? 'DATE_ADD' : 'DATE_SUB')."({$k}, INTERVAL {$abs} HOUR)",
        };

        DB::update("UPDATE {$t} SET {$k} = {$ekspresi} WHERE {$k} IS NOT NULL");
    }
};
