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
        Schema::create('tenant_tagihan_perpanjangan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('kode')->unique();
            $table->unsignedInteger('jumlah');
            $table->string('link_pembayaran')->nullable();
            $table->enum('status', ['menunggu', 'dibayar'])->default('menunggu');
            $table->timestamp('dikirim_pada');
            $table->timestamp('peringatan_terakhir_terkirim_at')->nullable();
            $table->timestamp('dibayar_pada')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_tagihan_perpanjangan');
    }
};
