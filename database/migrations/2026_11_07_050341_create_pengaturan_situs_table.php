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
        // Pengaturan situs korporat (GA4, SEO), bukan data tenant, jadi
        // tidak ada tenant_id. Key-value supaya tambah pengaturan baru
        // tidak perlu migration lagi.
        Schema::create('pengaturan_situs', function (Blueprint $table) {
            $table->string('kunci')->primary();
            $table->text('nilai')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaturan_situs');
    }
};
