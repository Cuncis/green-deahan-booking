<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\PengaturanSitus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pengaturan GA4, Search Console, dan SEO default untuk situs korporat
 * (greendeahan.com). Tidak menyentuh halaman booking tenant, jadi data
 * analytics platform tidak tercampur dengan traffic tenant.
 */
class SeoAdminController extends Controller
{
    public function edit(): View
    {
        return view('pages.superadmin.seo', [
            'pengaturan' => PengaturanSitus::semua(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_situs' => ['required', 'string', 'max:100'],
            'meta_deskripsi' => ['required', 'string', 'max:300'],
            'og_image_url' => ['nullable', 'url', 'max:500'],
            'ga4_measurement_id' => ['nullable', 'regex:/^G-[A-Z0-9]{6,12}$/'],
            'gsc_verifikasi' => ['nullable', 'regex:/^[A-Za-z0-9_-]{20,100}$/'],
        ], [
            'ga4_measurement_id.regex' => 'Format Measurement ID harus seperti G-XXXXXXXXXX.',
            'gsc_verifikasi.regex' => 'Isi hanya kode verifikasinya saja (nilai content), bukan seluruh tag meta.',
        ]);

        $data['indeks_mesin_pencari'] = $request->boolean('indeks_mesin_pencari') ? '1' : '0';

        PengaturanSitus::simpan($data);

        return redirect()->route('superadmin.seo')
            ->with('success', 'Pengaturan SEO dan analytics berhasil disimpan.');
    }
}
