<?php

namespace Tests\Feature;

use App\Models\GaleriItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GaleriAdminControllerTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->is_superadmin = true;
        $user->save();

        return $user;
    }

    public function test_non_superadmin_tidak_bisa_akses_galeri_admin(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('superadmin.galeri'));

        $response->assertForbidden();
    }

    public function test_superadmin_bisa_lihat_daftar_galeri(): void
    {
        $superadmin = $this->superadmin();
        GaleriItem::factory()->create();

        $response = $this->actingAs($superadmin)->get(route('superadmin.galeri'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('superadmin/Galeri/Index')
            ->has('item', 1)
            ->has('pilihanKategori', 5)
        );
    }

    public function test_daftar_galeri_bisa_difilter_kategori(): void
    {
        $superadmin = $this->superadmin();
        GaleriItem::factory()->create(['kategori' => 'futsal']);
        GaleriItem::factory()->create(['kategori' => 'padel']);

        $response = $this->actingAs($superadmin)->get(route('superadmin.galeri', ['kategori' => 'padel']));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('filterKategori', 'padel')
            ->has('item', 1)
            ->where('item.0.kategori', 'Padel')
        );
    }

    public function test_superadmin_bisa_tambah_item_galeri_dengan_foto(): void
    {
        Storage::fake('public');
        $superadmin = $this->superadmin();

        $response = $this->actingAs($superadmin)->post(route('superadmin.galeri.store'), [
            'kategori' => 'futsal',
            'judul' => 'Futsal Baru Jakarta',
            'kota' => 'Jakarta',
            'material' => 'Lantai Interlock',
            'deskripsi' => 'Deskripsi proyek futsal baru.',
            'urutan' => 5,
            'tampilan_besar' => '1',
            'foto' => UploadedFile::fake()->image('proyek.jpg'),
        ]);

        $response->assertRedirect(route('superadmin.galeri'));
        $response->assertSessionHas('success');

        $item = GaleriItem::where('judul', 'Futsal Baru Jakarta')->firstOrFail();
        $this->assertSame('futsal', $item->kategori);
        $this->assertTrue($item->tampilan_besar);
        $this->assertTrue($item->status_aktif);
        $this->assertNotNull($item->foto_url);
    }

    public function test_tambah_item_galeri_gagal_tanpa_foto(): void
    {
        $superadmin = $this->superadmin();

        $response = $this->actingAs($superadmin)->post(route('superadmin.galeri.store'), [
            'kategori' => 'futsal',
            'judul' => 'Tanpa Foto',
            'kota' => 'Jakarta',
            'material' => 'Interlock',
            'deskripsi' => 'Deskripsi.',
        ]);

        $response->assertSessionHasErrors('foto');
        $this->assertDatabaseMissing('galeri_items', ['judul' => 'Tanpa Foto']);
    }

    public function test_superadmin_bisa_update_item_galeri(): void
    {
        $superadmin = $this->superadmin();
        $item = GaleriItem::factory()->create(['judul' => 'Judul Lama', 'status_aktif' => true]);

        $response = $this->actingAs($superadmin)->put(route('superadmin.galeri.update', $item), [
            'kategori' => $item->kategori,
            'judul' => 'Judul Baru',
            'kota' => $item->kota,
            'material' => $item->material,
            'deskripsi' => $item->deskripsi,
            'status_aktif' => '0',
        ]);

        $response->assertRedirect(route('superadmin.galeri'));
        $item->refresh();
        $this->assertSame('Judul Baru', $item->judul);
        $this->assertFalse($item->status_aktif);
    }

    public function test_form_tambah_dan_edit_galeri_bisa_dibuka(): void
    {
        $superadmin = $this->superadmin();
        $item = GaleriItem::factory()->create(['judul' => 'Lapangan Padel Bekasi', 'tampilan_besar' => true]);

        $this->actingAs($superadmin)->get(route('superadmin.galeri.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('superadmin/Galeri/Form')->where('item', null));

        $this->actingAs($superadmin)->get(route('superadmin.galeri.edit', $item))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('superadmin/Galeri/Form')
                ->where('item.judul', 'Lapangan Padel Bekasi')
                ->where('item.tampilan_besar', true)
                ->where('item.updateUrl', route('superadmin.galeri.update', $item))
            );
    }

    public function test_update_galeri_lewat_post_method_put_dengan_foto_baru(): void
    {
        Storage::fake('public');
        $superadmin = $this->superadmin();
        $item = GaleriItem::factory()->create(['tampilan_besar' => true]);

        $this->actingAs($superadmin)->post(route('superadmin.galeri.update', $item), [
            '_method' => 'put',
            'kategori' => $item->kategori,
            'judul' => $item->judul,
            'kota' => $item->kota,
            'material' => $item->material,
            'deskripsi' => $item->deskripsi,
            'tampilan_besar' => '0',
            'status_aktif' => '1',
            'foto' => UploadedFile::fake()->image('baru.jpg'),
        ])->assertRedirect(route('superadmin.galeri'));

        $item->refresh();
        $this->assertFalse($item->tampilan_besar);
        $this->assertTrue($item->status_aktif);
        $this->assertStringContainsString('galeri', $item->foto_url);
    }

    public function test_superadmin_bisa_hapus_item_galeri(): void
    {
        $superadmin = $this->superadmin();
        $item = GaleriItem::factory()->create();

        $response = $this->actingAs($superadmin)->delete(route('superadmin.galeri.destroy', $item));

        $response->assertRedirect(route('superadmin.galeri'));
        $this->assertModelMissing($item);
    }
}
