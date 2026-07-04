<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Tenant;
use App\Models\TenantFitur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class TenantRegistrationController extends Controller
{
    public function show(Request $request): View
    {
        return view('pages.register-tenant', [
            'paketTerpilih' => $request->query('paket', 'basic'),
        ]);
    }

    public function store(Request $request): View
    {
        $data = $request->validate([
            'nama_bisnis' => ['required', 'string', 'max:150'],
            'subdomain' => [
                'required', 'string', 'lowercase', 'min:3', 'max:50', 'regex:/^[a-z0-9-]+$/',
                function ($attribute, $value, $fail) {
                    if (Tenant::where('domain', $value.'.greendeahan.com')->exists()) {
                        $fail('Subdomain ini sudah dipakai, coba nama lain.');
                    }
                },
            ],
            'nama_pic' => ['required', 'string', 'max:255'],
            'email_pic' => ['required', 'email', 'max:255', 'unique:users,email'],
            'whatsapp_pic' => ['required', 'string', 'max:20'],
            'paket' => ['required', 'in:basic,pro,premium'],
            'custom_domain_diminta' => [
                'nullable', 'string', 'max:150',
                function ($attribute, $value, $fail) use ($request) {
                    if (! $value) {
                        return;
                    }

                    if ($request->input('paket') === 'basic') {
                        $fail('Custom domain hanya tersedia untuk paket Pro dan Premium, tambah Rp250.000/tahun.');

                        return;
                    }

                    $domain = strtolower(trim($value));

                    if (filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
                        $fail('Format custom domain tidak valid.');

                        return;
                    }

                    if ($domain === 'greendeahan.com' || str_ends_with($domain, '.greendeahan.com')) {
                        $fail('Custom domain tidak boleh berupa subdomain greendeahan.com.');

                        return;
                    }

                    if (Tenant::where('custom_domain', $domain)->orWhere('custom_domain_diminta', $domain)->exists()) {
                        $fail('Custom domain ini sudah dipakai atau sedang diminta tenant lain.');
                    }
                },
            ],
        ]);

        $domain = $data['subdomain'].'.greendeahan.com';
        $customDomainDiminta = $data['custom_domain_diminta'] ?? null;

        $tenant = Tenant::create([
            'nama_bisnis' => $data['nama_bisnis'],
            'domain' => $domain,
            'custom_domain_diminta' => $customDomainDiminta ? strtolower(trim($customDomainDiminta)) : null,
            'paket' => $data['paket'],
            'status_aktif' => false,
            'whatsapp_admin' => $data['whatsapp_pic'],
            'email_admin' => $data['email_pic'],
        ]);

        TenantFitur::create(array_merge(
            ['tenant_id' => $tenant->id],
            TenantFitur::presetUntukPaket($data['paket']),
        ));

        Cabang::create([
            'tenant_id' => $tenant->id,
            'nama_cabang' => 'Cabang Utama',
            'alamat' => 'Alamat belum diisi',
            'kota' => 'Kota belum diisi',
            'jam_buka' => '07:00',
            'jam_tutup' => '21:00',
            'status_aktif' => true,
        ]);

        $this->kirimNotifikasiPendaftaran($tenant, $data['nama_pic'], $data['subdomain']);

        return view('pages.register-tenant-sukses', [
            'tenant' => $tenant,
        ]);
    }

    /**
     * Kirim email ke admin platform kalau ada pendaftar baru. Best-effort
     * saja (sama seperti SuperadminController::kirimEmailUndangan()),
     * kegagalan kirim email tidak boleh menggagalkan pendaftaran tenant.
     */
    private function kirimNotifikasiPendaftaran(Tenant $tenant, string $namaPic, string $subdomain): void
    {
        $adminEmail = config('app.admin_email');

        if (! $adminEmail) {
            return;
        }

        $baris = [
            'Ada pendaftar tenant baru.',
            '',
            "Nama Bisnis: {$tenant->nama_bisnis}",
            "Domain: {$tenant->domain}",
            "Paket: {$tenant->paket}",
            "PIC: {$namaPic}",
            "Email PIC: {$tenant->email_admin}",
            "WhatsApp PIC: {$tenant->whatsapp_admin}",
        ];

        if ($tenant->custom_domain_diminta) {
            $baris[] = '';
            $baris[] = "Custom domain diminta: {$tenant->custom_domain_diminta} (+Rp250.000/tahun, belum dikonfigurasi, tinjau lewat panel superadmin)";
        }

        $baris[] = '';
        $baris[] = "Konfirmasi pembayaran lalu aktifkan lewat: php artisan tenant:activate {$subdomain}";

        try {
            Mail::raw(
                implode("\n", $baris),
                function ($message) use ($adminEmail, $tenant) {
                    $message->to($adminEmail)
                        ->subject("Pendaftar Baru, {$tenant->nama_bisnis}");
                },
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
