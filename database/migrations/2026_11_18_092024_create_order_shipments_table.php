<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Satu pesanan bisa dikirim dalam beberapa paket (supplier atau cabang
 * berbeda), tiap paket punya kurir dan resi sendiri. Resi lama di kolom
 * orders dipindahkan ke tabel ini lalu kolomnya dibuang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('courier', 50);
            $table->string('tracking_number', 100);
            $table->string('shipped_from', 120)->nullable();
            $table->timestamps();
        });

        DB::table('orders')->whereNotNull('tracking_number')->orderBy('id')->each(function (object $order): void {
            DB::table('order_shipments')->insert([
                'order_id' => $order->id,
                'courier' => $order->courier ?? '-',
                'tracking_number' => $order->tracking_number,
                'shipped_from' => $order->shipped_from,
                'created_at' => $order->updated_at,
                'updated_at' => $order->updated_at,
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['courier', 'tracking_number', 'shipped_from']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('courier', 50)->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->string('shipped_from', 120)->nullable();
        });

        DB::table('order_shipments')->orderBy('id')->each(function (object $paket): void {
            DB::table('orders')->where('id', $paket->order_id)->whereNull('tracking_number')->update([
                'courier' => $paket->courier,
                'tracking_number' => $paket->tracking_number,
                'shipped_from' => $paket->shipped_from,
            ]);
        });

        Schema::dropIfExists('order_shipments');
    }
};
