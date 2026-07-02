<?php

namespace Tests\Feature;

use App\Models\Artikel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArtikelAdminControllerTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->is_superadmin = true;
        $user->save();

        return $user;
    }

    public function test_non_superadmin_tidak_bisa_akses_artikel_admin(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('superadmin.artikel'));

        $response->assertForbidden();
    }

    public function test_superadmin_bisa_lihat_daftar_artikel(): void
    {
        $superadmin = $this->superadmin();
        Artikel::factory()->create();

        $response = $this->actingAs($superadmin)->get(route('superadmin.artikel'));

        $response->assertOk();
        $response->assertViewHas('artikel', fn ($artikel) => $artikel->count() === 1);
    }

    public function test_daftar_artikel_bisa_dicari_lewat_judul(): void
    {
        $superadmin = $this->superadmin();
        Artikel::factory()->create(['judul' => 'Cara Merawat Rumput Sintetis']);
        Artikel::factory()->create(['judul' => 'Panduan Investasi Padel']);

        $response = $this->actingAs($superadmin)->get(route('superadmin.artikel', ['cari' => 'Rumput']));

        $response->assertViewHas('artikel', fn ($artikel) => $artikel->count() === 1
            && $artikel->first()->judul === 'Cara Merawat Rumput Sintetis');
    }

    public function test_superadmin_bisa_tulis_artikel_baru_dengan_slug_otomatis(): void
    {
        Storage::fake('public');
        $superadmin = $this->superadmin();

        $response = $this->actingAs($superadmin)->post(route('superadmin.artikel.store'), [
            'judul' => 'Cara Membangun Lapangan Futsal yang Awet',
            'kategori' => 'Panduan Bisnis',
            'ringkasan' => 'Ringkasan singkat artikel.',
            'konten' => '<p>Isi lengkap artikel.</p>',
            'tanggal_terbit' => now()->toDateString(),
            'foto' => UploadedFile::fake()->image('artikel.jpg'),
        ]);

        $response->assertRedirect(route('superadmin.artikel'));
        $response->assertSessionHas('success');

        $artikel = Artikel::where('judul', 'Cara Membangun Lapangan Futsal yang Awet')->firstOrFail();
        $this->assertSame('cara-membangun-lapangan-futsal-yang-awet', $artikel->slug);
        $this->assertTrue($artikel->status_aktif);
        $this->assertNotNull($artikel->foto_url);
    }

    public function test_slug_otomatis_tetap_unik_kalau_judul_sama(): void
    {
        $superadmin = $this->superadmin();
        Artikel::factory()->create(['slug' => 'tips-lapangan-futsal']);

        $response = $this->actingAs($superadmin)->post(route('superadmin.artikel.store'), [
            'judul' => 'Tips Lapangan Futsal',
            'kategori' => 'Tips Perawatan',
            'ringkasan' => 'Ringkasan.',
            'konten' => '<p>Konten.</p>',
            'tanggal_terbit' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('superadmin.artikel'));
        $this->assertDatabaseHas('artikel', ['slug' => 'tips-lapangan-futsal-1']);
    }

    public function test_superadmin_bisa_update_artikel(): void
    {
        $superadmin = $this->superadmin();
        $artikel = Artikel::factory()->create(['judul' => 'Judul Lama', 'status_aktif' => true]);

        $response = $this->actingAs($superadmin)->put(route('superadmin.artikel.update', $artikel), [
            'judul' => 'Judul Baru',
            'kategori' => $artikel->kategori,
            'ringkasan' => $artikel->ringkasan,
            'konten' => $artikel->konten,
            'tanggal_terbit' => $artikel->tanggal_terbit->toDateString(),
            'status_aktif' => '0',
        ]);

        $response->assertRedirect(route('superadmin.artikel'));
        $artikel->refresh();
        $this->assertSame('Judul Baru', $artikel->judul);
        $this->assertFalse($artikel->status_aktif);
    }

    public function test_superadmin_bisa_hapus_artikel(): void
    {
        $superadmin = $this->superadmin();
        $artikel = Artikel::factory()->create();

        $response = $this->actingAs($superadmin)->delete(route('superadmin.artikel.destroy', $artikel));

        $response->assertRedirect(route('superadmin.artikel'));
        $this->assertModelMissing($artikel);
    }
}
