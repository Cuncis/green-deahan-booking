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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('no_telepon')->unique();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('membership', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->enum('tier', ['bronze', 'silver', 'gold'])->default('bronze');
            $table->unsignedInteger('total_booking')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'customer_id']);
        });

        Schema::create('kode_promo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('kode');
            $table->enum('tipe_diskon', ['persen', 'nominal']);
            $table->unsignedInteger('nilai');
            $table->date('tanggal_mulai');
            $table->date('tanggal_berakhir');
            $table->unsignedInteger('kuota')->nullable();
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'kode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kode_promo');
        Schema::dropIfExists('membership');
        Schema::dropIfExists('customers');
    }
};
