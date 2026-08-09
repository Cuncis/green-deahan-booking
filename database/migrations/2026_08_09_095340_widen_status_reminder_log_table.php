<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * status sebelumnya default 'terkirim' sejak dibuat, padahal belum ada
 * satupun kode yang benar-benar mengirim reminder-nya (lihat widget
 * "Reminder Otomatis" di DashboardAdmin), jadi setiap baris SELALU tampak
 * sudah terkirim sejak awal. 'menunggu' jadi status awal yang benar,
 * berubah jadi 'terkirim' hanya lewat DashboardAdmin::tandaiTerkirim()
 * begitu staf klik kirim.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reminder_log', function (Blueprint $table) {
            $table->enum('status', ['menunggu', 'terkirim', 'gagal'])->default('menunggu')->change();
        });

        DB::table('reminder_log')->update(['status' => 'menunggu']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reminder_log', function (Blueprint $table) {
            $table->enum('status', ['terkirim', 'gagal'])->default('terkirim')->change();
        });
    }
};
