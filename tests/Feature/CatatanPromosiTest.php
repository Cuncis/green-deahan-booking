<?php

namespace Tests\Feature;

use App\Models\CatatanPromosi;
use App\Models\User;
use Database\Seeders\CatatanPromosiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Catatan promosi dan iklan di superadmin: daftar (disematkan dulu), tambah,
 * ubah, hapus, validasi, dan akses hanya untuk superadmin.
 */
class CatatanPromosiTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->is_superadmin = true;
        $user->save();

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function data(array $override = []): array
    {
        return array_merge(['judul' => 'Kampanye Google Ads', 'kategori' => 'google-ads', 'isi' => "Baris satu\nBaris dua", 'disematkan' => 0], $override);
    }

    public function test_daftar_menampilkan_yang_disematkan_dulu_lalu_yang_terbaru(): void
    {
        $lama = CatatanPromosi::factory()->create(['judul' => 'Lama', 'updated_at' => now()->subDays(3)]);
        $baru = CatatanPromosi::factory()->create(['judul' => 'Baru', 'updated_at' => now()]);
        $sematan = CatatanPromosi::factory()->disematkan()->create(['judul' => 'Sematan', 'updated_at' => now()->subDays(10)]);

        $this->actingAs($this->superadmin())->get('/superadmin/catatan-promosi')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('superadmin/CatatanPromosi')
            ->where('catatan.0.id', $sematan->id)
            ->where('catatan.1.id', $baru->id)
            ->where('catatan.2.id', $lama->id)
            ->has('kategori', 5));
    }

    public function test_superadmin_menambah_mengubah_dan_menghapus_catatan(): void
    {
        $admin = $this->superadmin();

        $this->actingAs($admin)->post('/superadmin/catatan-promosi', $this->data(['disematkan' => 1]))->assertSessionHasNoErrors();
        $catatan = CatatanPromosi::firstOrFail();
        $this->assertSame("Baris satu\nBaris dua", $catatan->isi);
        $this->assertTrue($catatan->disematkan);

        $this->actingAs($admin)->put("/superadmin/catatan-promosi/{$catatan->id}", $this->data(['judul' => 'Prompt Popup', 'kategori' => 'prompt-gambar', 'isi' => 'Isi baru', 'disematkan' => 0]))->assertSessionHasNoErrors();
        $catatan->refresh();
        $this->assertSame('Prompt Popup', $catatan->judul);
        $this->assertSame('prompt-gambar', $catatan->kategori);
        $this->assertFalse($catatan->disematkan);

        $this->actingAs($admin)->delete("/superadmin/catatan-promosi/{$catatan->id}")->assertRedirect();
        $this->assertSame(0, CatatanPromosi::count());
    }

    public function test_isi_yang_panjang_dan_berisi_html_disimpan_apa_adanya_sebagai_teks(): void
    {
        $isi = str_repeat('Kata kunci: kontraktor lapangan futsal. ', 500).'<script>alert(1)</script>';

        $this->actingAs($this->superadmin())->post('/superadmin/catatan-promosi', $this->data(['isi' => $isi]))->assertSessionHasNoErrors();

        $this->assertSame($isi, CatatanPromosi::firstOrFail()->isi);
    }

    public function test_validasi_form_catatan(): void
    {
        $admin = $this->superadmin();

        $this->actingAs($admin)->post('/superadmin/catatan-promosi', $this->data(['judul' => '']))->assertSessionHasErrors('judul');
        $this->actingAs($admin)->post('/superadmin/catatan-promosi', $this->data(['isi' => '']))->assertSessionHasErrors('isi');
        $this->actingAs($admin)->post('/superadmin/catatan-promosi', $this->data(['kategori' => 'asing']))->assertSessionHasErrors('kategori');
        $this->actingAs($admin)->post('/superadmin/catatan-promosi', $this->data(['isi' => str_repeat('a', 30001)]))->assertSessionHasErrors('isi');
        $this->actingAs($admin)->post('/superadmin/catatan-promosi', $this->data(['disematkan' => 'mungkin']))->assertSessionHasErrors('disematkan');
        $this->assertSame(0, CatatanPromosi::count());
    }

    public function test_menu_catatan_promosi_ada_di_grup_situs_korporat(): void
    {
        $this->actingAs($this->superadmin())->get('/superadmin/catatan-promosi')->assertInertia(function (Assert $page) {
            $grup = collect($page->toArray()['props']['navigasi'])->firstWhere('grup', 'Situs Korporat');
            $label = collect($grup['item'])->pluck('label');

            $this->assertContains('Catatan Promosi', $label->all());
            $this->assertContains('Banner Promo', $label->all());
        });
    }

    public function test_bukan_superadmin_tidak_bisa_mengakses_catatan(): void
    {
        $catatan = CatatanPromosi::factory()->create();
        $biasa = User::factory()->create();

        $this->get('/superadmin/catatan-promosi')->assertRedirect();
        $this->actingAs($biasa)->get('/superadmin/catatan-promosi')->assertForbidden();
        $this->actingAs($biasa)->post('/superadmin/catatan-promosi', $this->data())->assertForbidden();
        $this->actingAs($biasa)->put("/superadmin/catatan-promosi/{$catatan->id}", $this->data())->assertForbidden();
        $this->actingAs($biasa)->delete("/superadmin/catatan-promosi/{$catatan->id}")->assertForbidden();
        $this->assertSame(1, CatatanPromosi::count());
    }

    public function test_seeder_membuat_catatan_awal_dan_aman_dijalankan_ulang(): void
    {
        $this->seed(CatatanPromosiSeeder::class);

        $this->assertSame(9, CatatanPromosi::count());
        $this->assertSame(['google-ads', 'google-ads', 'google-ads', 'kampanye', 'meta-ads', 'meta-ads', 'meta-ads', 'prompt-gambar', 'prompt-gambar'], CatatanPromosi::orderBy('kategori')->orderBy('id')->pluck('kategori')->all());
        $this->assertStringContainsString('NEGATIVE PROMPT', CatatanPromosi::where('kategori', 'prompt-gambar')->firstOrFail()->isi);
        $this->assertStringContainsString('Click-to-WhatsApp', CatatanPromosi::where('judul', 'like', 'Meta Ads (Instagram%')->firstOrFail()->isi);
        $this->assertStringContainsString('kontraktor lapangan futsal', CatatanPromosi::where('kategori', 'google-ads')->firstOrFail()->isi);
        $this->assertTrue(CatatanPromosi::where('kategori', 'google-ads')->firstOrFail()->disematkan);

        $catatan = CatatanPromosi::where('kategori', 'google-ads')->firstOrFail();
        $catatan->update(['isi' => 'Sudah saya ubah']);

        $this->seed(CatatanPromosiSeeder::class);

        $this->assertSame(9, CatatanPromosi::count());
        $this->assertSame('Sudah saya ubah', $catatan->fresh()->isi);
    }

    public function test_isi_seeder_tidak_memakai_tanda_strip_panjang_dan_muat_di_batas_form(): void
    {
        $this->seed(CatatanPromosiSeeder::class);

        foreach (CatatanPromosi::all() as $catatan) {
            $this->assertStringNotContainsString("\u{2014}", $catatan->isi.$catatan->judul);
            $this->assertLessThanOrEqual(30000, mb_strlen($catatan->isi));
        }
    }

    public function test_nomor_whatsapp_kampanye_memakai_nomor_baru_dan_tidak_ada_nomor_lama(): void
    {
        $this->seed(CatatanPromosiSeeder::class);

        $semua = CatatanPromosi::pluck('isi')->implode("\n");
        $this->assertStringContainsString('0821357570064', $semua);
        $this->assertStringContainsString('wa.me/62821357570064', $semua);
        $this->assertStringNotContainsString('81357570063', $semua);
    }

    public function test_seeder_mengganti_nomor_lama_di_catatan_hasil_versi_sebelumnya_tanpa_menyentuh_isi_lain(): void
    {
        CatatanPromosi::factory()->create(['judul' => 'Catatan versi lama', 'isi' => "Chat ke 081357570063 atau https://wa.me/6281357570063?text=Halo\nCatatan pribadi saya"]);

        $this->seed(CatatanPromosiSeeder::class);

        $this->assertSame("Chat ke 0821357570064 atau https://wa.me/62821357570064?text=Halo\nCatatan pribadi saya", CatatanPromosi::where('judul', 'Catatan versi lama')->firstOrFail()->isi);
    }

    public function test_prompt_popup_tidak_meminta_sudut_membulat_di_dalam_gambar(): void
    {
        $this->seed(CatatanPromosiSeeder::class);

        $prompt = CatatanPromosi::where('judul', 'like', 'Prompt gambar popup:%')->firstOrFail()->isi;
        $this->assertStringNotContainsString('sudut membulat halus', $prompt);
        $this->assertStringNotContainsString('softly rounded corners', $prompt);
        $this->assertStringContainsString('sudut kotak tajam', $prompt);
        $this->assertStringContainsString('rounded corners, border, frame', $prompt);
    }

    public function test_seeder_memperbaiki_prompt_lama_yang_meminta_sudut_membulat(): void
    {
        CatatanPromosi::factory()->create(['judul' => 'Prompt lama', 'isi' => "Desain popup (rasio 4:5) dengan sudut membulat halus, gaya foto komersial.\nEnglish: popup (4:5 ratio) with softly rounded corners, minimalist.\nCatatan saya"]);

        $this->seed(CatatanPromosiSeeder::class);

        $isi = CatatanPromosi::where('judul', 'Prompt lama')->firstOrFail()->isi;
        $this->assertStringContainsString('sudut kotak tajam', $isi);
        $this->assertStringContainsString('sharp square corners', $isi);
        $this->assertStringContainsString('Catatan saya', $isi);
    }

    public function test_catatan_meta_ads_punya_tab_sendiri_dan_catatan_lama_dipindahkan(): void
    {
        CatatanPromosi::factory()->create(['judul' => 'Meta Ads (Instagram dan Facebook): Promo Akhir Tahun 2026', 'kategori' => 'kampanye']);
        CatatanPromosi::factory()->create(['judul' => 'Rencana lain', 'kategori' => 'kampanye']);

        $this->seed(CatatanPromosiSeeder::class);

        $this->assertSame(['meta-ads'], CatatanPromosi::where('judul', 'Meta Ads (Instagram dan Facebook): Promo Akhir Tahun 2026')->pluck('kategori')->all());
        $this->assertSame('kampanye', CatatanPromosi::where('judul', 'Rencana lain')->firstOrFail()->kategori);
        $this->assertSame(3, CatatanPromosi::where('kategori', 'meta-ads')->count());
        $this->assertContains('Meta Ads', array_values(CatatanPromosi::KATEGORI));
    }

    public function test_seeder_umum_memuat_dua_lini_bisnis_dengan_nomor_dan_link_whatsapp_baru(): void
    {
        $this->seed(CatatanPromosiSeeder::class);

        $kontraktor = CatatanPromosi::where('judul', 'like', 'Google Ads: Kontraktor Lapangan Olahraga%')->firstOrFail()->isi;
        $booking = CatatanPromosi::where('judul', 'like', 'Google Ads: Sistem Booking%')->firstOrFail()->isi;

        $this->assertStringContainsString('Banyak Jenis Lapangan', $kontraktor);
        $this->assertStringContainsString('wa.me/62821357570064?text=Halo%20Green%20Deahan%20Sport%2C%20saya%20ingin%20konsultasi', $kontraktor);
        $this->assertStringContainsString('wa.me/62821357570064?text=Halo%20Green%20Deahan%20Sport%2C%20saya%20tertarik%20dengan%20sistem%20booking', $booking);
        $this->assertStringContainsString('terdekat', $booking);
    }
}
