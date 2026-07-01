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
        Schema::table('tenants', function (Blueprint $table) {
            // 'domain' tetap dipakai untuk subdomain default
            // (namaklien.greendeahan.com). 'custom_domain' untuk domain
            // tambahan milik klien (namadomain.com), keduanya bisa aktif
            // bersamaan, lihat App\Http\Middleware\IdentifikasiTenant.
            $table->string('custom_domain', 150)->nullable()->unique()->after('domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('custom_domain');
        });
    }
};
