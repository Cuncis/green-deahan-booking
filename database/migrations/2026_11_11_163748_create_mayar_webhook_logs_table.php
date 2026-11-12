<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan setiap webhook Mayar yang masuk (toko maupun booking), untuk
 * pelacakan masalah. Token webhook (query string) tidak ikut disimpan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mayar_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event', 60)->nullable();
            $table->string('payment_id', 100)->nullable()->index();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->json('payload');
            $table->string('result', 40)->default('diterima');
            $table->string('message', 255)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mayar_webhook_logs');
    }
};
