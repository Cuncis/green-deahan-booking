<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pembatalan pesanan: alasan (ditampilkan ke pelanggan), waktu batal, dan
 * waktu refund selesai. Pesanan batal yang sudah dibayar dan belum punya
 * refunded_at berarti masih perlu di-refund manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('cancel_reason', 255)->nullable()->after('admin_notes');
            $table->timestamp('cancelled_at')->nullable()->after('cancel_reason');
            $table->timestamp('refunded_at')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['cancel_reason', 'cancelled_at', 'refunded_at']);
        });
    }
};
