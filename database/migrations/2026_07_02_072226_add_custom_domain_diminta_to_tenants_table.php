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
        // Domain yang DIMINTA pelanggan saat mendaftar mandiri lewat
        // /daftar, sengaja terpisah dari kolom 'custom_domain' yang berarti
        // domain itu SUDAH dikonfigurasi (DNS + SSL) dan aktif dipakai
        // (lihat IdentifikasiTenant & SuperadminController::setCustomDomain).
        // Superadmin meninjau permintaan ini lalu baru mengisi
        // 'custom_domain' secara manual setelah DNS/SSL benar-benar siap.
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('custom_domain_diminta', 150)->nullable()->after('custom_domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('custom_domain_diminta');
        });
    }
};
