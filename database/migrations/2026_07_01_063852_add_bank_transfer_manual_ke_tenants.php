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
            $table->string('bank_nama')->nullable()->after('email_admin');
            $table->string('bank_no_rekening')->nullable()->after('bank_nama');
            $table->string('bank_pemilik_rekening')->nullable()->after('bank_no_rekening');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['bank_nama', 'bank_no_rekening', 'bank_pemilik_rekening']);
        });
    }
};
