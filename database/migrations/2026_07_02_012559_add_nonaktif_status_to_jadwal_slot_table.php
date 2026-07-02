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
        Schema::table('jadwal_slot', function (Blueprint $table) {
            $table->enum('status', ['kosong', 'hold', 'booked', 'nonaktif'])->default('kosong')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jadwal_slot', function (Blueprint $table) {
            $table->enum('status', ['kosong', 'hold', 'booked'])->default('kosong')->change();
        });
    }
};
