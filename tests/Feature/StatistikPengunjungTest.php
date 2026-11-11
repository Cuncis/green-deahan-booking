<?php

namespace Tests\Feature;

use App\Models\Artikel;
use App\Models\KunjunganHalaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StatistikPengunjungTest extends TestCase
{
    use RefreshDatabase;

    private const UA_DESKTOP = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

    private const UA_IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1';

    private const UA_IPAD = 'Mozilla/5.0 (iPad; CPU OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1';

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->is_superadmin = true;
        $user->save();

        return $user;
    }

    public function test_kunjungan_halaman_situs_korporat_dicatat_tanpa_menyimpan_ip(): void
    {
        $this->withHeaders(['User-Agent' => self::UA_DESKTOP])
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
            ->get('http://greendeahan.com/kontak/')
            ->assertOk();

        $kunjungan = KunjunganHalaman::sole();
        $this->assertSame('/kontak', $kunjungan->path);
        $this->assertSame('desktop', $kunjungan->perangkat);
        $this->assertSame(now(KunjunganHalaman::ZONA_WAKTU)->toDateString(), $kunjungan->tanggal);
        $this->assertSame(64, strlen($kunjungan->pengunjung_hash));
        $this->assertStringNotContainsString('203.0.113.7', json_encode($kunjungan->toArray()));
        $this->assertNull($kunjungan->sumber_host);
    }

    public function test_pengunjung_sama_di_hari_yang_sama_punya_hash_sama(): void
    {
        $this->withHeaders(['User-Agent' => self::UA_DESKTOP])->get('http://greendeahan.com/kontak');
        $this->withHeaders(['User-Agent' => self::UA_DESKTOP])->get('http://greendeahan.com/tentang-kami');
        $this->withHeaders(['User-Agent' => self::UA_IPHONE])->get('http://greendeahan.com/kontak');

        $this->assertSame(3, KunjunganHalaman::count());
        $this->assertSame(2, KunjunganHalaman::distinct()->count('pengunjung_hash'));
    }

    public function test_perangkat_dan_sumber_terdeteksi(): void
    {
        $this->withHeaders(['User-Agent' => self::UA_IPHONE, 'Referer' => 'https://www.google.com/'])->get('http://greendeahan.com/kontak');
        $this->withHeaders(['User-Agent' => self::UA_IPAD, 'Referer' => 'http://greendeahan.com/'])->get('http://greendeahan.com/kontak');

        $this->assertSame(['mobile', 'tablet'], KunjunganHalaman::orderBy('id')->pluck('perangkat')->all());
        $this->assertSame(['www.google.com', null], KunjunganHalaman::orderBy('id')->pluck('sumber_host')->all());
    }

    public function test_bot_prefetch_404_dan_user_login_tidak_dicatat(): void
    {
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'])->get('http://greendeahan.com/kontak');
        $this->withHeaders(['User-Agent' => 'WhatsApp/2.24.1 A'])->get('http://greendeahan.com/kontak');
        $this->withHeaders(['User-Agent' => self::UA_DESKTOP, 'Sec-Purpose' => 'prefetch'])->get('http://greendeahan.com/kontak');
        $this->withHeaders(['User-Agent' => self::UA_DESKTOP])->get('http://greendeahan.com/blog/tidak-ada')->assertNotFound();
        $this->withHeaders(['User-Agent' => self::UA_DESKTOP])->get('http://greendeahan.com/sitemap.xml')->assertOk();
        $this->actingAs(User::factory()->create())->withHeaders(['User-Agent' => self::UA_DESKTOP])->get('http://greendeahan.com/kontak');

        $this->assertSame(0, KunjunganHalaman::count());
    }

    public function test_halaman_tenant_tidak_dicatat(): void
    {
        $this->withHeaders(['User-Agent' => self::UA_DESKTOP])->get('http://localhost/');

        $this->assertSame(0, KunjunganHalaman::count());
    }

    public function test_dashboard_superadmin_meringkas_kunjungan(): void
    {
        $hariIni = now(KunjunganHalaman::ZONA_WAKTU)->startOfDay();
        Artikel::factory()->create(['slug' => 'cara-hitung-roi', 'judul' => 'Cara Hitung ROI']);

        KunjunganHalaman::factory()->count(3)->sequence(
            ['pengunjung_hash' => str_repeat('a', 64)],
            ['pengunjung_hash' => str_repeat('a', 64)],
            ['pengunjung_hash' => str_repeat('b', 64)],
        )->create(['path' => '/', 'tanggal' => $hariIni->toDateString(), 'perangkat' => 'mobile', 'sumber_host' => 'wa.me']);
        KunjunganHalaman::factory()->create(['path' => '/blog/cara-hitung-roi', 'tanggal' => $hariIni->toDateString(), 'pengunjung_hash' => str_repeat('c', 64), 'perangkat' => 'desktop', 'sumber_host' => 'www.pinterest.com']);
        KunjunganHalaman::factory()->create(['path' => '/kontak', 'tanggal' => $hariIni->copy()->subDays(10)->toDateString(), 'pengunjung_hash' => str_repeat('d', 64), 'perangkat' => 'desktop', 'sumber_host' => null]);

        $this->actingAs($this->superadmin())
            ->get(route('superadmin.dashboard', ['rentang' => 7]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('superadmin/Dashboard')->where('statistikPengunjung', function ($statistik): bool {
                $statistik = collect($statistik)->toArray();
                $this->assertSame(7, $statistik['rentang']);
                $this->assertCount(7, $statistik['harian']);
                $this->assertSame(['pengunjung' => 3, 'tampilan' => 4, 'pengunjungHariIni' => 3], array_intersect_key($statistik['ringkasan'], array_flip(['pengunjung', 'tampilan', 'pengunjungHariIni'])));
                $this->assertSame('Beranda', $statistik['halamanTeratas'][0]['nama']);
                $this->assertSame(3, $statistik['halamanTeratas'][0]['tampilan']);
                $this->assertSame('Cara Hitung ROI', $statistik['halamanTeratas'][1]['nama']);
                $this->assertSame(['WhatsApp', 'pinterest.com'], array_column($statistik['sumber'], 'nama'));
                $this->assertSame(['Mobile', 'Desktop'], array_column($statistik['perangkat'], 'nama'));

                return true;
            }));

        $this->actingAs($this->superadmin())
            ->get(route('superadmin.dashboard', ['rentang' => 30]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('statistikPengunjung.ringkasan.tampilan', 5)
                ->where('statistikPengunjung.halamanTeratas', fn ($halaman) => in_array('Kontak', collect($halaman)->pluck('nama')->all(), true)));
    }

    public function test_rentang_tidak_valid_kembali_ke_30_hari(): void
    {
        $this->actingAs($this->superadmin())
            ->get(route('superadmin.dashboard', ['rentang' => 365]))
            ->assertInertia(fn (Assert $page) => $page
                ->where('statistikPengunjung.rentang', 30)
                ->has('statistikPengunjung.harian', 30));
    }

    public function test_dashboard_tetap_hanya_untuk_superadmin(): void
    {
        $this->actingAs(User::factory()->create())->get(route('superadmin.dashboard'))->assertForbidden();
    }
}
