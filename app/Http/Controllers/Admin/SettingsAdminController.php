<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SettingsAdminController extends Controller
{
    public function show(): View
    {
        $tenant = app('tenant');

        return view('pages.admin.settings.show', [
            'tenant' => $tenant,
            'daftarCabang' => Cabang::where('tenant_id', $tenant->id)->orderBy('nama_cabang')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = app('tenant');

        $data = $request->validate([
            'nama_bisnis' => ['required', 'string', 'max:150'],
            'whatsapp_admin' => ['required', 'string', 'max:20'],
            'email_admin' => ['nullable', 'email', 'max:255'],
            'warna_utama' => ['nullable', 'string', 'max:9', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $tenant->update([
            'nama_bisnis' => $data['nama_bisnis'],
            'whatsapp_admin' => $data['whatsapp_admin'],
            'email_admin' => $data['email_admin'] ?? null,
            'warna_utama' => $data['warna_utama'] ?? $tenant->warna_utama,
        ]);

        if ($request->hasFile('logo')) {
            $this->hapusFileLama($tenant->logo_url);
            $path = $request->file('logo')->store('logo', 'public');

            // Sengaja pakai asset() (resolve dari host request saat ini), BUKAN
            // Storage::disk('public')->url() yang selalu balik ke APP_URL statis.
            // Tenant diakses dari macam-macam domain (subdomain, custom domain),
            // jadi URL logo yang di-hardcode ke satu domain akan rusak/404 di
            // domain tenant manapun selain APP_URL itu sendiri.
            $tenant->update(['logo_url' => asset('storage/'.$path)]);
        }

        if ($tenant->punyaFitur('multi_cabang')) {
            $this->updateSemuaCabang($request, $tenant);
        } else {
            $this->updateCabangDefault($request, $tenant);
        }

        return redirect()->route('admin.pengaturan')
            ->with('success', 'Pengaturan berhasil disimpan.');
    }

    private function updateCabangDefault(Request $request, Tenant $tenant): void
    {
        $data = $request->validate([
            'jam_buka' => ['required', 'date_format:H:i'],
            'jam_tutup' => ['required', 'date_format:H:i', 'after:jam_buka'],
        ]);

        Cabang::where('tenant_id', $tenant->id)->first()?->update($data);
    }

    private function updateSemuaCabang(Request $request, Tenant $tenant): void
    {
        $data = $request->validate([
            'cabang' => ['required', 'array'],
            'cabang.*.nama_cabang' => ['required', 'string', 'max:150'],
            'cabang.*.alamat' => ['required', 'string', 'max:255'],
            'cabang.*.kota' => ['required', 'string', 'max:100'],
            'cabang.*.jam_buka' => ['required', 'date_format:H:i'],
            'cabang.*.jam_tutup' => ['required', 'date_format:H:i', 'after:cabang.*.jam_buka'],
        ]);

        foreach ($data['cabang'] as $cabangId => $fields) {
            Cabang::where('tenant_id', $tenant->id)->where('id', $cabangId)->update($fields);
        }
    }

    private function hapusFileLama(?string $url): void
    {
        if (! $url) {
            return;
        }

        $path = Str::after($url, '/storage/');

        if ($path && $path !== $url) {
            Storage::disk('public')->delete($path);
        }
    }
}
