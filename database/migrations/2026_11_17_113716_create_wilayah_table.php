<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wilayah administrasi Indonesia (kode Kemendagri) untuk dropdown alamat di
 * checkout toko: provinsi, kabupaten/kota, kecamatan, kelurahan/desa.
 * Isinya diimpor dengan `php artisan wilayah:impor`, bukan lewat migrasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wilayah', function (Blueprint $table) {
            $table->string('kode', 13)->primary();
            $table->string('nama', 120);
            $table->unsignedTinyInteger('level');
            $table->string('induk', 13)->nullable();
            $table->string('kodepos', 5)->nullable();

            $table->index(['induk', 'nama']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wilayah');
    }
};
