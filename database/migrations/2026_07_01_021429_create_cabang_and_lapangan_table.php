<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cabang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('nama_cabang');
            $table->string('alamat');
            $table->string('kota');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->time('jam_buka');
            $table->time('jam_tutup');
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('lapangan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('cabang_id')->constrained('cabang')->cascadeOnDelete();
            $table->string('nama');
            $table->string('jenis_olahraga');
            $table->unsignedInteger('harga_per_jam');
            $table->unsignedInteger('harga_jam_sibuk')->nullable();
            $table->string('foto_url')->nullable();
            $table->text('deskripsi')->nullable();
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lapangan');
        Schema::dropIfExists('cabang');
    }
};
