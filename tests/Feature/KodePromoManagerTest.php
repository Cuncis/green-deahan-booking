<?php

namespace Tests\Feature;

use App\Models\KodePromo;
use App\Models\Staf;
use App\Models\Tenant;
use App\Models\TenantFitur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Kelola kode promo lewat PromoAdminController (sebelumnya Livewire
 * KodePromoManager).
 */
class KodePromoManagerTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(string $paket = 'pro'): Tenant
    {
        $tenant = Tenant::where('domain', 'localhost')->firstOrFail();
        TenantFitur::create(array_merge(['tenant_id' => $tenant->id], TenantFitur::presetUntukPaket($paket)));

        return $tenant;
    }

    private function staf(Tenant $tenant): User
    {
        $user = User::factory()->create();

        Staf::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'status_aktif' => true,
        ]);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function dataPromo(array $override = []): array
    {
        return array_merge([
            'kode' => 'sepi20',
            'tipe_diskon' => 'persen',
            'nilai' => 20,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_berakhir' => now()->addMonth()->toDateString(),
            'kuota' => '',
        ], $override);
    }

    public function test_daftar_kode_promo_hanya_milik_tenant_sendiri(): void
    {
        $tenant = $this->tenant();
        $tenantLain = Tenant::factory()->create(['domain' => 'lain.test']);

        KodePromo::factory()->create(['tenant_id' => $tenant->id, 'kode' => 'MILIKKU']);
        KodePromo::factory()->create(['tenant_id' => $tenantLain->id, 'kode' => 'MILIKORANG']);

        $this->actingAs($this->staf($tenant))
            ->get(route('admin.promo'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('promo', 1)
                ->where('promo.0.kode', 'MILIKKU')
            );
    }

    public function test_tambah_kode_promo_baru_berhasil(): void
    {
        $tenant = $this->tenant();

        $this->actingAs($this->staf($tenant))
            ->post(route('admin.promo.store'), $this->dataPromo())
            ->assertRedirect()
            ->assertSessionHas('success', 'Kode promo SEPI20 ditambahkan.');

        $this->assertDatabaseHas('kode_promo', [
            'tenant_id' => $tenant->id,
            'kode' => 'SEPI20',
            'nilai' => 20,
            'kuota' => null,
        ]);
    }

    public function test_tambah_kode_promo_gagal_kalau_kode_sudah_dipakai(): void
    {
        $tenant = $this->tenant();
        KodePromo::factory()->create(['tenant_id' => $tenant->id, 'kode' => 'SEPI20']);

        $this->actingAs($this->staf($tenant))
            ->post(route('admin.promo.store'), $this->dataPromo(['nilai' => 10]))
            ->assertSessionHasErrors(['kode' => 'Kode promo ini sudah dipakai, coba kode lain.']);

        $this->assertDatabaseCount('kode_promo', 1);
    }

    public function test_kode_yang_sama_boleh_dipakai_tenant_lain(): void
    {
        $tenant = $this->tenant();
        $tenantLain = Tenant::factory()->create(['domain' => 'lain.test']);
        KodePromo::factory()->create(['tenant_id' => $tenantLain->id, 'kode' => 'SEPI20']);

        $this->actingAs($this->staf($tenant))
            ->post(route('admin.promo.store'), $this->dataPromo())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('kode_promo', 2);
    }

    public function test_diskon_persen_tidak_boleh_lebih_dari_100_dan_tanggal_berakhir_tidak_sebelum_mulai(): void
    {
        $tenant = $this->tenant();

        $this->actingAs($this->staf($tenant))
            ->post(route('admin.promo.store'), $this->dataPromo([
                'nilai' => 150,
                'tanggal_berakhir' => now()->subDay()->toDateString(),
            ]))
            ->assertSessionHasErrors(['nilai', 'tanggal_berakhir']);

        $this->actingAs($this->staf($tenant))
            ->post(route('admin.promo.store'), $this->dataPromo(['tipe_diskon' => 'nominal', 'nilai' => 150000]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('kode_promo', 1);
    }

    public function test_tenant_basic_tidak_bisa_tambah_kode_promo(): void
    {
        $tenant = $this->tenant('basic');

        $this->actingAs($this->staf($tenant))
            ->post(route('admin.promo.store'), $this->dataPromo())
            ->assertNotFound();

        $this->assertDatabaseCount('kode_promo', 0);
    }
}
