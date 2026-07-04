<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Manual transfer dihapus dari seluruh project (lihat
 * TenantFitur::presetUntukPaket()), jadi tenant Basic yang sudah terlanjur
 * terdaftar sebelum perubahan ini butuh backfill supaya langsung bisa pakai
 * Midtrans juga, bukan cuma tenant baru ke depannya. Pro dan Premium sudah
 * true dari awal, backfill ini aman dijalankan ke semua tenant sekaligus.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('tenant_fitur')->update(['pembayaran_online' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tenantBasicIds = DB::table('tenants')->where('paket', 'basic')->pluck('id');

        DB::table('tenant_fitur')
            ->whereIn('tenant_id', $tenantBasicIds)
            ->update(['pembayaran_online' => false]);
    }
};
