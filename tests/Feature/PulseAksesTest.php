<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NavigasiAdminService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use stdClass;
use Tests\TestCase;

/**
 * Dashboard Pulse (Monitoring Server) hanya untuk superadmin dan bisa dibuka
 * dari domain mana pun (tidak terikat tenant), plus menu Pengaturan di sidebar.
 */
class PulseAksesTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->is_superadmin = true;
        $user->save();

        return $user;
    }

    public function test_gate_view_pulse_hanya_untuk_superadmin(): void
    {
        $this->assertTrue(Gate::forUser($this->superadmin())->allows('viewPulse'));
        $this->assertFalse(Gate::forUser(User::factory()->create())->allows('viewPulse'));
        $this->assertFalse(Gate::allows('viewPulse'));
    }

    public function test_tamu_dan_pengguna_biasa_ditolak_di_pulse(): void
    {
        $this->get('/pulse')->assertForbidden();
        $this->actingAs(User::factory()->create())->get('/pulse')->assertForbidden();
    }

    public function test_superadmin_bisa_membuka_pulse_dari_domain_yang_bukan_tenant(): void
    {
        $this->actingAs($this->superadmin())->get('http://domain-bukan-tenant.test/pulse')->assertOk()->assertSee('pulse.exceptions', false);
    }

    public function test_kartu_exceptions_pulse_menautkan_ke_log_error(): void
    {
        // Kartu dirender oleh komponen Livewire Pulse, jadi yang dicek: tampilan yang menimpa bawaan Pulse memakai rute Log Error.
        $sumber = file_get_contents(resource_path('views/vendor/pulse/livewire/exceptions.blade.php'));

        $this->assertStringContainsString("route('superadmin.log-error', ['q' => \$exception->class, 'level' => 'semua'], false)", $sumber);
        $this->assertSame('/superadmin/log-error?q=App%5CExceptions%5CMayarException&level=semua', route('superadmin.log-error', ['q' => 'App\\Exceptions\\MayarException', 'level' => 'semua'], false));
    }

    public function test_cache_file_dan_database_bisa_menyimpan_hasil_query_kartu_pulse(): void
    {
        // Kartu Pulse menyimpan Collection stdClass dengan tanggal Carbon. Cache yang menserialisasi
        // (file, database) hanya membangun ulang kelas yang diizinkan di config/cache.php.
        $nilai = collect([(object) ['class' => 'App\Exceptions\MayarException', 'latest' => CarbonImmutable::parse('2026-11-23 10:00:00'), 'count' => 3]]);
        $toko = Cache::store('file');
        $toko->put('uji-pulse-cache', [$nilai, 12.5, '2026-11-23 10:00:00'], 60);

        [$hasil] = $toko->get('uji-pulse-cache');
        $toko->forget('uji-pulse-cache');

        $this->assertInstanceOf(Collection::class, $hasil);
        $this->assertInstanceOf(stdClass::class, $hasil->first());
        $this->assertInstanceOf(CarbonImmutable::class, $hasil->first()->latest);
        $this->assertSame(3, $hasil->first()->count);
    }

    public function test_menu_pengaturan_superadmin_berisi_monitoring_log_error_dan_notifikasi(): void
    {
        $grup = collect(app(NavigasiAdminService::class)->untukSuperadmin())->firstWhere('grup', 'Pengaturan');

        $this->assertNotNull($grup);
        $this->assertSame(
            ['Monitoring Server' => false, 'Log Error' => true, 'Email & Notifikasi' => true],
            collect($grup['item'])->pluck('inertia', 'label')->all(),
        );
        $this->assertSame(['/pulse', '/superadmin/log-error', '/superadmin/pengaturan/notifikasi'], collect($grup['item'])->map(fn ($item) => parse_url($item['href'], PHP_URL_PATH))->all());
    }
}
