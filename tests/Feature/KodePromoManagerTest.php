<?php

namespace Tests\Feature;

use App\Livewire\KodePromoManager;
use App\Models\KodePromo;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KodePromoManagerTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(): Tenant
    {
        $tenant = Tenant::where('domain', 'localhost')->firstOrFail();
        app()->instance('tenant', $tenant);

        return $tenant;
    }

    public function test_daftar_kode_promo_hanya_milik_tenant_sendiri(): void
    {
        $tenant = $this->tenant();
        $tenantLain = Tenant::factory()->create(['domain' => 'lain.test']);

        KodePromo::factory()->create(['tenant_id' => $tenant->id, 'kode' => 'MILIKKU']);
        KodePromo::factory()->create(['tenant_id' => $tenantLain->id, 'kode' => 'MILIKORANG']);

        Livewire::test(KodePromoManager::class)
            ->assertSee('MILIKKU')
            ->assertDontSee('MILIKORANG');
    }

    public function test_tambah_kode_promo_baru_berhasil(): void
    {
        $tenant = $this->tenant();

        Livewire::test(KodePromoManager::class)
            ->call('bukaForm')
            ->set('kode', 'sepi20')
            ->set('tipeDiskon', 'persen')
            ->set('nilai', 20)
            ->set('tanggalMulai', now()->toDateString())
            ->set('tanggalBerakhir', now()->addMonth()->toDateString())
            ->call('simpan')
            ->assertSet('tampilkanForm', false)
            ->assertSee('SEPI20');

        $this->assertDatabaseHas('kode_promo', [
            'tenant_id' => $tenant->id,
            'kode' => 'SEPI20',
            'nilai' => 20,
        ]);
    }

    public function test_tambah_kode_promo_gagal_kalau_kode_sudah_dipakai(): void
    {
        $tenant = $this->tenant();
        KodePromo::factory()->create(['tenant_id' => $tenant->id, 'kode' => 'SEPI20']);

        Livewire::test(KodePromoManager::class)
            ->call('bukaForm')
            ->set('kode', 'sepi20')
            ->set('nilai', 10)
            ->set('tanggalMulai', now()->toDateString())
            ->set('tanggalBerakhir', now()->addMonth()->toDateString())
            ->call('simpan')
            ->assertSet('pesanError', 'Kode promo ini sudah dipakai, coba kode lain.')
            ->assertSet('tampilkanForm', true);

        $this->assertDatabaseCount('kode_promo', 1);
    }
}
