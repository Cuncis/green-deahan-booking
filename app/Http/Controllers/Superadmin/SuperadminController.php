<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Cabang;
use App\Models\Tenant;
use App\Models\TenantFitur;
use App\Models\TenantInvitation;
use App\Services\RingkasanTokoService;
use App\Services\StatistikPengunjungService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SuperadminController extends Controller
{
    public function dashboard(Request $request, StatistikPengunjungService $statistikPengunjung, RingkasanTokoService $ringkasanToko): Response
    {
        return Inertia::render('superadmin/Dashboard', [
            'toko' => $ringkasanToko->ringkasan(),
            'pesananTokoUrl' => route('superadmin.toko.pesanan'),
            'statistikPengunjung' => $statistikPengunjung->ringkasan((int) $request->query('rentang', 30)),
            'ringkasan' => [
                'totalTenant' => Tenant::count(),
                'tenantAktif' => Tenant::where('status_aktif', true)->count(),
                'bookingHariIni' => Booking::where('status_booking', '!=', 'dibatalkan')
                    ->whereHas('slot', fn ($q) => $q->whereDate('tanggal', today()))
                    ->count(),
            ],
            'tenantTerbaru' => Tenant::latest()->take(5)->get()->map(fn (Tenant $tenant) => [
                'id' => $tenant->id,
                'namaBisnis' => $tenant->nama_bisnis,
                'domain' => $tenant->domain,
                'paket' => $tenant->paket,
                'aktif' => (bool) $tenant->status_aktif,
                'url' => route('superadmin.tenants.show', $tenant),
            ])->all(),
            'semuaTenantUrl' => route('superadmin.tenants'),
        ]);
    }

    public function tenants(Request $request): Response
    {
        $filterPaket = in_array($request->query('paket'), ['basic', 'pro', 'premium'], true) ? $request->query('paket') : null;
        $filterStatus = in_array($request->query('status'), ['aktif', 'nonaktif'], true) ? $request->query('status') : null;

        return Inertia::render('superadmin/Tenants/Index', [
            'daftarTenant' => Tenant::withCount('bookings')
                ->when($filterPaket, fn ($q, $paket) => $q->where('paket', $paket))
                ->when($filterStatus, fn ($q, $status) => $q->where('status_aktif', $status === 'aktif'))
                ->latest()
                ->latest('id')
                ->get()
                ->map(fn (Tenant $tenant) => [
                    'id' => $tenant->id,
                    'namaBisnis' => $tenant->nama_bisnis,
                    'domain' => $tenant->domain,
                    'paket' => $tenant->paket,
                    'aktif' => (bool) $tenant->status_aktif,
                    'berakhir' => $tenant->tanggal_berakhir?->format('d/m/Y'),
                    'jumlahBooking' => $tenant->bookings_count,
                    'punyaEmail' => (bool) $tenant->email_admin,
                    ...$this->urlAksi($tenant),
                ])
                ->all(),
            'filter' => ['paket' => $filterPaket, 'status' => $filterStatus],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('superadmin/Tenants/Form', [
            'pilihanPaket' => [
                ['nilai' => 'basic', 'nama' => 'Basic', 'harga' => 'Rp299rb', 'deskripsi' => 'Booking online dasar, 1 lapangan, notifikasi WhatsApp manual.'],
                ['nilai' => 'pro', 'nama' => 'Pro', 'harga' => 'Rp599rb', 'deskripsi' => 'Sampai 3 lapangan, pembayaran online, kode promo, laporan pendapatan.'],
                ['nilai' => 'premium', 'nama' => 'Premium', 'harga' => 'Rp1.199rb', 'deskripsi' => 'Lapangan tanpa batas, multi cabang, membership, staf, reminder otomatis.'],
            ],
            'simpanUrl' => route('superadmin.tenants.store'),
            'kembaliUrl' => route('superadmin.tenants'),
        ]);
    }

    public function store(Request $request): RedirectResponse
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
            'jam_buka' => '07:00',
            'jam_tutup' => '21:00',
            'status_aktif' => true,
        ]);

        $tenant->update(['status_aktif' => true]);

        $invitation = TenantInvitation::buatUntuk($tenant, $data['email_pic']);

        $invitation->kirimEmail($data['nama_pic']);

        return redirect()->route('superadmin.tenants.show', $tenant)
            ->with('success', "Tenant \"{$tenant->nama_bisnis}\" berhasil dibuat. Kirim link undangan ke {$data['nama_pic']}.")
            ->with('invitation_link', $invitation->link())
            ->with('invitation_tenant', $tenant->nama_bisnis);
    }

    /**
     * Status custom domain dikirim sebagai deferred prop: pengecekannya
     * memanggil domain tenant (timeout 3 detik), jadi halaman tampil dulu.
     */
    public function show(Tenant $tenant): Response
    {
        return Inertia::render('superadmin/Tenants/Show', [
            'detail' => [
                'id' => $tenant->id,
                'namaBisnis' => $tenant->nama_bisnis,
                'domain' => $tenant->domain,
                'paket' => $tenant->paket,
                'aktif' => (bool) $tenant->status_aktif,
                'mulai' => $tenant->tanggal_mulai?->format('d/m/Y'),
                'berakhir' => $tenant->tanggal_berakhir?->format('d/m/Y'),
                'whatsapp' => $tenant->whatsapp_admin,
                'email' => $tenant->email_admin,
                'customDomain' => $tenant->custom_domain,
                'customDomainDiminta' => $tenant->custom_domain_diminta,
                ...$this->urlAksi($tenant),
                'simpanDomainUrl' => route('superadmin.tenants.custom-domain.store', $tenant),
                'hapusDomainUrl' => route('superadmin.tenants.custom-domain.destroy', $tenant),
            ],
            'statusCustomDomain' => Inertia::defer(fn () => $this->cekStatusCustomDomain($tenant)),
            'perintahServer' => $tenant->custom_domain ? $this->perintahNginxDanSsl($tenant->custom_domain) : [],
            'kembaliUrl' => route('superadmin.tenants'),
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

        return back(fallback: route('superadmin.tenants'))
            ->with('success', "Tenant \"{$tenant->nama_bisnis}\" diaktifkan.");
    }

    public function deactivate(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['status_aktif' => false]);

        return back(fallback: route('superadmin.tenants'))
            ->with('success', "Tenant \"{$tenant->nama_bisnis}\" dinonaktifkan.");
    }

    public function inviteOwner(Tenant $tenant): RedirectResponse
    {
        if (! $tenant->email_admin) {
            return back(fallback: route('superadmin.tenants'))
                ->with('error', "Tenant \"{$tenant->nama_bisnis}\" belum punya email PIC, tidak bisa kirim invitation.");
        }

        $invitation = TenantInvitation::buatUntuk($tenant, $tenant->email_admin);

        $invitation->kirimEmail();

        return back(fallback: route('superadmin.tenants'))
            ->with('success', "Invitation owner untuk \"{$tenant->nama_bisnis}\" berhasil dibuat.")
            ->with('invitation_link', $invitation->link())
            ->with('invitation_tenant', $tenant->nama_bisnis);
    }

    /**
     * @return array{url: string, aktifkanUrl: string, nonaktifkanUrl: string, undangUrl: string}
     */
    private function urlAksi(Tenant $tenant): array
    {
        return [
            'url' => route('superadmin.tenants.show', $tenant),
            'aktifkanUrl' => route('superadmin.tenants.activate', $tenant),
            'nonaktifkanUrl' => route('superadmin.tenants.deactivate', $tenant),
            'undangUrl' => route('superadmin.tenants.invite', $tenant),
        ];
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
