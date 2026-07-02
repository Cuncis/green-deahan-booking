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
        // Bukan data tenant (tidak ada tenant_id, lihat references/multi-tenant.md),
        // ini portofolio korporat greendeahan.com sendiri, dikelola lewat
        // /superadmin, bukan fitur booking-SaaS per tenant.
        Schema::create('galeri_items', function (Blueprint $table) {
            $table->id();
            $table->enum('kategori', ['futsal', 'minisoccer', 'padel', 'badminton', 'proses']);
            $table->string('judul');
            $table->string('kota');
            $table->string('material');
            $table->text('deskripsi');
            $table->string('foto_url');
            $table->boolean('tampilan_besar')->default(false);
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('galeri_items');
    }
};
