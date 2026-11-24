<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Zona waktu aplikasi Asia/Jakarta (WIB): data baru tersimpan dalam WIB, dan
 * migrasi menggeser data lama (UTC) +7 jam sekali saja, hanya kolom datetime
 * dan timestamp, serta dilewati pada instalasi baru.
 */
class ZonaWaktuTest extends TestCase
{
    use RefreshDatabase;

    private function migrasi(): Migration
    {
        return require glob(database_path('migrations/*geser_waktu_data_lama_ke_asia_jakarta.php'))[0];
    }

    private function pesananLama(): Order
    {
        $order = Order::factory()->create();
        DB::table('orders')->where('id', $order->id)->update([
            'created_at' => '2026-11-20 03:00:00',
            'updated_at' => '2026-11-20 04:30:00',
            'paid_at' => '2026-11-20 03:10:00',
            'cancelled_at' => null,
        ]);

        return $order;
    }

    public function test_aplikasi_memakai_asia_jakarta(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('Asia/Jakarta', now()->getTimezone()->getName());
        $this->assertSame('Asia/Jakarta', date_default_timezone_get());
    }

    public function test_data_baru_tersimpan_dalam_wib(): void
    {
        Carbon::setTestNow('2026-11-24 10:00:00');

        $order = Order::factory()->create();

        $this->assertSame('2026-11-24 10:00:00', DB::table('orders')->where('id', $order->id)->value('created_at'));
        $this->assertSame('2026-11-24 10:00:00', $order->fresh()->created_at->format('Y-m-d H:i:s'));
    }

    public function test_migrasi_menggeser_semua_kolom_waktu_data_lama_tujuh_jam(): void
    {
        $pengguna = User::factory()->create(['email_verified_at' => null]);
        DB::table('users')->where('id', $pengguna->id)->update(['email_verified_at' => '2026-11-20 00:00:00']);
        $order = $this->pesananLama();
        DB::table('sessions')->insert(['id' => 'abc', 'payload' => 'x', 'last_activity' => 1_700_000_000]);

        $this->migrasi()->up();

        $baris = DB::table('orders')->where('id', $order->id)->first();
        $this->assertSame('2026-11-20 10:00:00', $baris->created_at);
        $this->assertSame('2026-11-20 11:30:00', $baris->updated_at);
        $this->assertSame('2026-11-20 10:10:00', $baris->paid_at);
        $this->assertNull($baris->cancelled_at);
        $this->assertSame('2026-11-20 07:00:00', DB::table('users')->where('id', $pengguna->id)->value('email_verified_at'));
        $this->assertSame(1_700_000_000, (int) DB::table('sessions')->where('id', 'abc')->value('last_activity'));
    }

    public function test_migrasi_tidak_menyentuh_kolom_tanggal_saja(): void
    {
        User::factory()->create();
        DB::table('tenants')->update(['tanggal_berakhir' => '2026-12-31']);

        $this->migrasi()->up();

        $this->assertSame(['2026-12-31'], DB::table('tenants')->pluck('tanggal_berakhir')->unique()->map(fn ($nilai) => substr((string) $nilai, 0, 10))->values()->all());
    }

    public function test_down_mengembalikan_waktu_ke_utc(): void
    {
        User::factory()->create();
        $order = $this->pesananLama();

        $this->migrasi()->up();
        $this->migrasi()->down();

        $baris = DB::table('orders')->where('id', $order->id)->first();
        $this->assertSame(['2026-11-20 03:00:00', '2026-11-20 04:30:00', '2026-11-20 03:10:00'], [$baris->created_at, $baris->updated_at, $baris->paid_at]);
    }

    public function test_instalasi_baru_tanpa_pengguna_dilewati(): void
    {
        $this->assertSame(0, DB::table('users')->count());
        $order = $this->pesananLama();

        $this->migrasi()->up();

        $this->assertSame('2026-11-20 03:00:00', DB::table('orders')->where('id', $order->id)->value('created_at'));
    }
}
