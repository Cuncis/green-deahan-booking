<?php

namespace Tests\Feature;

use App\Models\GaleriItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MediaAdminControllerTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->is_superadmin = true;
        $user->save();

        return $user;
    }

    public function test_non_superadmin_tidak_bisa_akses_media(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('superadmin.media'))->assertForbidden();
    }

    public function test_superadmin_bisa_lihat_semua_gambar_dan_non_gambar_diabaikan(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('galeri/a.jpg', 'x');
        Storage::disk('public')->put('lapangan/b.png', 'x');
        Storage::disk('public')->put('lapangan/catatan.txt', 'x');

        $response = $this->actingAs($this->superadmin())->get(route('superadmin.media'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('superadmin/Media')
            ->where('media.total', 2)
            ->where('totalMedia', 2)
            ->where('daftarFolder', ['galeri', 'lapangan'])
            ->has('media.data.0', fn (Assert $item) => $item->hasAll(['path', 'nama', 'folder', 'url', 'ukuran', 'diubah', 'dipakai']))
        );
    }

    public function test_media_bisa_difilter_folder_dan_nama(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('galeri/a.jpg', 'x');
        Storage::disk('public')->put('lapangan/b.png', 'x');
        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)->get(route('superadmin.media', ['folder' => 'lapangan']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filter.folder', 'lapangan')
                ->where('media.total', 1)
                ->where('media.data.0.nama', 'b.png')
            );

        $this->actingAs($superadmin)->get(route('superadmin.media', ['q' => 'a.jpg']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filter.q', 'a.jpg')
                ->where('media.total', 1)
                ->where('media.data.0.folder', 'galeri')
            );

        $this->actingAs($superadmin)->get(route('superadmin.media', ['folder' => '../rahasia']))
            ->assertInertia(fn (Assert $page) => $page->where('filter.folder', null)->where('media.total', 2));
    }

    public function test_superadmin_bisa_upload_beberapa_gambar(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->superadmin())->post(route('superadmin.media.store'), [
            'files' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')],
        ]);

        $response->assertRedirect(route('superadmin.media'));
        $response->assertSessionHas('success');
        $this->assertCount(2, Storage::disk('public')->files('media'));
    }

    public function test_upload_menolak_file_bukan_gambar(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->superadmin())->post(route('superadmin.media.store'), [
            'files' => [UploadedFile::fake()->create('dokumen.pdf', 10, 'application/pdf')],
        ]);

        $response->assertSessionHasErrors('files.0');
        $this->assertCount(0, Storage::disk('public')->files('media'));
    }

    public function test_superadmin_bisa_hapus_file_yang_tidak_dipakai(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/lepas.jpg', 'x');

        $response = $this->actingAs($this->superadmin())
            ->delete(route('superadmin.media.destroy'), ['path' => 'media/lepas.jpg']);

        $response->assertSessionHas('success');
        Storage::disk('public')->assertMissing('media/lepas.jpg');
    }

    public function test_file_yang_masih_dipakai_tidak_bisa_dihapus(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('galeri/dipakai.jpg', 'x');
        GaleriItem::factory()->create(['foto_url' => asset('storage/galeri/dipakai.jpg')]);

        $response = $this->actingAs($this->superadmin())
            ->delete(route('superadmin.media.destroy'), ['path' => 'galeri/dipakai.jpg']);

        $response->assertSessionHas('error');
        Storage::disk('public')->assertExists('galeri/dipakai.jpg');
    }

    public function test_hapus_menolak_path_traversal_dan_file_tidak_ada(): void
    {
        Storage::fake('public');
        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)
            ->delete(route('superadmin.media.destroy'), ['path' => '../.env'])
            ->assertNotFound();

        $this->actingAs($superadmin)
            ->delete(route('superadmin.media.destroy'), ['path' => 'media/tidak-ada.jpg'])
            ->assertNotFound();
    }
}
