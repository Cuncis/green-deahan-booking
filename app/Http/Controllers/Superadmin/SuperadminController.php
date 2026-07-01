<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Cabang;
use App\Models\Tenant;
use App\Models\TenantFitur;
use App\Models\TenantInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SuperadminController extends Controller
{
    public function dashboard(): View
    {
        return view('pages.superadmin.dashboard', [
            'totalTenant' => Tenant::count(),
            'tenantAktif' => Tenant::where('status_aktif', true)->count(),
            'bookingHariIni' => Booking::where('status_booking', '!=', 'dibatalkan')
                ->whereHas('slot', fn ($q) => $q->whereDate('tanggal', today()))
                ->count(),
            'tenantTerbaru' => Tenant::latest()->take(5)->get(),
        ]);
    }

    public function tenants(Request $request): View
    {
        $tenants = Tenant::withCount('bookings')
            ->when($request->query('paket'), fn ($q, $paket) => $q->where('paket', $paket))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status_aktif', $status === 'aktif'))
            ->latest()
            ->get();

        return view('pages.superadmin.tenants', [
            'tenants' => $tenants,
            'filterPaket' => $request->query('paket'),
            'filterStatus' => $request->query('status'),
        ]);
    }

    public function create(): View
    {
        return view('superadmin.tenants.create');
    }

    public function store(Request $request): View
    {
        $data = $request->validate([
            'nama_bisnis' => ['required', 'string', 'max:150'],
            'subdomain' => [
                'required', 'string', 'lowercase', 'min:3', 'max:50', 'regex:/^[a-z0-9-]+$/',
                function ($attribute, $value, $fail) {
                    if (Tenant::where('domain', $value.'.greendeahan.com')->exists()) {
                        $fail('Subdomain ini sudah dipakai tenant lain.');
                    }
                },
            ],
            'paket' => ['required', 'in:basic,pro,premium'],
            'kota' => ['required', 'string', 'max:100'],
            'nama_pic' => ['required', 'string', 'max:255'],
            'email_pic' => ['required', 'email', 'unique:users,email'],
            'whatsapp_pic' => ['required', 'string', 'max:20'],
        ]);

        $domain = $data['subdomain'].'.greendeahan.com';

        $tenant = Tenant::create([
            'nama_bisnis' => $data['nama_bisnis'],
            'domain' => $domain,
            'paket' => $data['paket'],
            'status_aktif' => false,
            'tanggal_mulai' => today(),
            'tanggal_berakhir' => today()->addYear(),
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
            'kota' => $data['kota'],
            'jam_buka' => '08:00',
            'jam_tutup' => '22:00',
            'status_aktif' => true,
        ]);

        $tenant->update(['status_aktif' => true]);

        $invitation = TenantInvitation::buatUntuk($tenant, $data['email_pic']);

        $this->kirimEmailUndangan($tenant, $invitation, $data['nama_pic']);

        return view('superadmin.tenants.success', [
            'tenant' => $tenant,
            'namaPic' => $data['nama_pic'],
            'invitationLink' => $invitation->link(),
        ]);
    }

    public function show(Tenant $tenant): View
    {
        return view('superadmin.tenants.show', [
            'tenant' => $tenant,
            'statusCustomDomain' => $this->cekStatusCustomDomain($tenant),
            'perintahServer' => $tenant->custom_domain ? $this->perintahNginxDanSsl($tenant->custom_domain) : [],
        ]);
    }

    public function setCustomDomain(Request $request, Tenant $tenant): RedirectResponse
    {
        if ($tenant->paket === 'basic') {
            return redirect()->route('superadmin.tenants.show', $tenant)
                ->with('error', 'Custom domain hanya tersedia untuk paket Pro dan Premium. Upgrade paket tenant ini dulu, misalnya lewat php artisan tenant:activate --paket=pro.');
        }

        $data = $request->validate([
            'custom_domain' => [
                'required', 'string', 'max:150',
                function ($attribute, $value, $fail) {
                    $domain = strtolower(trim($value));

                    if (filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
                        $fail('Format domain tidak valid.');

                        return;
                    }

                    if ($domain === 'greendeahan.com' || str_ends_with($domain, '.greendeahan.com')) {
                        $fail('Custom domain tidak boleh berupa subdomain greendeahan.com.');
                    }
                },
                Rule::unique('tenants', 'custom_domain')->ignore($tenant->id),
            ],
        ]);

        $tenant->update(['custom_domain' => strtolower(trim($data['custom_domain']))]);

        return redirect()->route('superadmin.tenants.show', $tenant)
            ->with('success', 'Domain disimpan. Sekarang tambahkan domain ini ke Nginx dan generate SSL dengan perintah di bawah.');
    }

    public function removeCustomDomain(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['custom_domain' => null]);

        return redirect()->route('superadmin.tenants.show', $tenant)
            ->with('success', "Custom domain untuk \"{$tenant->nama_bisnis}\" dihapus.");
    }

    public function activate(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['status_aktif' => true]);

        return redirect()->route('superadmin.tenants')
            ->with('success', "Tenant \"{$tenant->nama_bisnis}\" diaktifkan.");
    }

    public function deactivate(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['status_aktif' => false]);

        return redirect()->route('superadmin.tenants')
            ->with('success', "Tenant \"{$tenant->nama_bisnis}\" dinonaktifkan.");
    }

    public function inviteOwner(Tenant $tenant): RedirectResponse
    {
        if (! $tenant->email_admin) {
            return redirect()->route('superadmin.tenants')
                ->with('error', "Tenant \"{$tenant->nama_bisnis}\" belum punya email PIC, tidak bisa kirim invitation.");
        }

        $invitation = TenantInvitation::buatUntuk($tenant, $tenant->email_admin);

        $this->kirimEmailUndangan($tenant, $invitation);

        return redirect()->route('superadmin.tenants')
            ->with('success', "Invitation owner untuk \"{$tenant->nama_bisnis}\" berhasil dibuat.")
            ->with('invitation_link', $invitation->link())
            ->with('invitation_tenant', $tenant->nama_bisnis);
    }

    /**
     * Kirim link undangan lewat email. Best-effort saja, dan link-nya tetap
     * ditampilkan di halaman terlepas email berhasil terkirim atau tidak,
     * karena selama MAIL_MAILER=log (default lokal) "terkirim" cuma berarti
     * masuk ke file log, bukan benar-benar sampai ke inbox.
     */
    private function kirimEmailUndangan(Tenant $tenant, TenantInvitation $invitation, ?string $namaPic = null): void
    {
        $link = $invitation->link();
        $sapaan = $namaPic ? "Halo {$namaPic}," : 'Halo,';

        try {
            Mail::raw(
                "{$sapaan}\n\nKamu diundang jadi owner untuk tenant \"{$tenant->nama_bisnis}\" di Green Deahan Sport Platform.\n\nKlik link berikut untuk membuat akun, berlaku 7 hari:\n{$link}",
                function ($message) use ($invitation, $tenant) {
                    $message->to($invitation->email)
                        ->subject("Undangan jadi Owner, {$tenant->nama_bisnis}");
                },
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Cek langsung ke domainnya, bukan cuma nebak dari kolomnya kosong atau
     * tidak, supaya "Aktif" vs "SSL Pending" mencerminkan kondisi nyata
     * (DNS sudah mengarah dan SSL sudah terpasang, atau belum).
     */
    private function cekStatusCustomDomain(Tenant $tenant): string
    {
        if (! $tenant->custom_domain) {
            return 'belum_dikonfigurasi';
        }

        try {
            Http::timeout(3)->get('https://'.$tenant->custom_domain);

            return 'aktif';
        } catch (\Throwable $e) {
            return 'ssl_pending';
        }
    }

    /**
     * @return array<int, string>
     */
    private function perintahNginxDanSsl(string $domain): array
    {
        return [
            'sudo nano /etc/nginx/sites-available/green-deahan-booking',
            "# Tambahkan domain ini ke server_name di server block yang sudah ada:\nserver_name {$domain} www.{$domain};",
            'sudo nginx -t && sudo systemctl reload nginx',
            "sudo certbot --nginx -d {$domain} -d www.{$domain}",
        ];
    }
}
