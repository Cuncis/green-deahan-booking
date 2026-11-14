<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Judul dan deskripsi halaman situs korporat harus muat di hasil pencarian
 * Google supaya tidak terpotong: judul sampai 60 karakter, deskripsi sampai
 * 160 karakter. Halaman blog memakai ringkasan artikelnya sendiri, jadi
 * tidak termasuk di sini.
 */
class SeoMetaHalamanTest extends TestCase
{
    use RefreshDatabase;

    private const MAKS_JUDUL = 60;

    private const MAKS_DESKRIPSI = 160;

    /**
     * @return array<string, array{string}>
     */
    public static function halaman(): array
    {
        return [
            'beranda' => ['/'],
            'tentang kami' => ['/tentang-kami'],
            'bangun lapangan' => ['/bangun-lapangan'],
            'perawatan' => ['/perawatan'],
            'upgrade lapangan' => ['/upgrade-lapangan'],
            'galeri' => ['/galeri'],
            'blog' => ['/blog'],
            'kontak' => ['/kontak'],
            'sistem booking' => ['/sistem-booking'],
            'daftar' => ['/daftar'],
            'toko' => ['/toko'],
        ];
    }

    #[DataProvider('halaman')]
    public function test_judul_dan_deskripsi_tidak_terpotong_di_google(string $path): void
    {
        $this->get('http://greendeahan.com'.$path)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('meta.title', fn (string $judul) => mb_strlen($judul) <= self::MAKS_JUDUL)
                ->where('meta.description', fn (string $deskripsi) => mb_strlen($deskripsi) <= self::MAKS_DESKRIPSI)
            );
    }
}
