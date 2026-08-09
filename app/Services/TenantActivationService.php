<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantFitur;

/**
 * Logika aktivasi/perpanjangan masa aktif tenant, dipakai bareng oleh
 * AktifkanTenant (override manual/dukungan) dan
 * PembayaranController::prosesWebhookLangganan() (otomatis lewat Mayar),
 * supaya perhitungan tanggal_berakhir selalu konsisten di kedua jalur.
 * Validasi nilai $paket jadi tanggung jawab caller (AktifkanTenant sudah
 * validasi sebelum sampai sini), service ini asumsikan input sudah benar.
 */
class TenantActivationService
{
    public function aktifkan(Tenant $tenant, ?string $paket = null, int $hariPerpanjang = 365): Tenant
    {
        if ($paket !== null && $paket !== $tenant->paket) {
            $tenant->update(['paket' => $paket]);

            TenantFitur::updateOrCreate(
                ['tenant_id' => $tenant->id],
                TenantFitur::presetUntukPaket($paket),
            );
        }

        // Perpanjang dari tanggal berakhir yang lama kalau masih aktif
        // (menambah sisa waktu), atau dari hari ini kalau sudah lewat/belum
        // pernah aktif sama sekali, supaya klien tidak dirugikan dihitung
        // dari tanggal kadaluarsa.
        $dasarPerpanjangan = $tenant->tanggal_berakhir && $tenant->tanggal_berakhir->isFuture()
            ? $tenant->tanggal_berakhir->copy()
            : today();

        $tenant->update([
            'status_aktif' => true,
            'tanggal_mulai' => $tenant->tanggal_mulai ?? today(),
            'tanggal_berakhir' => $dasarPerpanjangan->addDays($hariPerpanjang),
        ]);

        return $tenant->refresh();
    }
}
