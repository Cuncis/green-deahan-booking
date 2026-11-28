<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Token acak untuk link halaman status pesanan pelanggan. Kode pesanan
     * (GD-...) sering dibagikan ke CS, jadi tidak lagi dipakai di URL.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status_token', 40)->nullable()->after('code');
        });

        DB::table('orders')->whereNull('status_token')->orderBy('id')->each(function (object $order) {
            DB::table('orders')->where('id', $order->id)->update(['status_token' => Str::random(40)]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unique('status_token');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['status_token']);
            $table->dropColumn('status_token');
        });
    }
};
