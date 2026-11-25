<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catatan promosi dan iklan untuk superadmin (rencana kampanye, kata kunci,
     * prompt gambar, dan sejenisnya). Bukan data tenant, jadi tanpa tenant_id.
     */
    public function up(): void
    {
        Schema::create('catatan_promosi', function (Blueprint $table) {
            $table->id();
            $table->string('judul', 150);
            $table->string('kategori', 20)->default('lainnya');
            $table->longText('isi');
            $table->boolean('disematkan')->default(false);
            $table->timestamps();

            $table->index(['disematkan', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catatan_promosi');
    }
};
