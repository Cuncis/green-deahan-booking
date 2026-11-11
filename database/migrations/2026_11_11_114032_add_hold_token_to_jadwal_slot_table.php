<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Token rahasia milik customer yang sedang menahan slot. Booking dan
     * pelepasan hold wajib menyertakan token ini, supaya slot yang sedang
     * ditahan satu customer tidak bisa dibooking orang lain.
     */
    public function up(): void
    {
        Schema::table('jadwal_slot', function (Blueprint $table) {
            $table->string('hold_token', 64)->nullable()->after('hold_sampai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jadwal_slot', function (Blueprint $table) {
            $table->dropColumn('hold_token');
        });
    }
};
