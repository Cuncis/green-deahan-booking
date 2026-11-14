<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Tenant;
use App\Models\TenantFitur;
use App\Services\PaketSistemBookingService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Pendaftaran tenant baru (/daftar), React di resources/js/pages/Daftar.tsx
 * dan DaftarSukses.tsx. Total yang tampil di form hanya estimasi; jumlah
 * invoice selalu dihitung ulang di server lewat Tenant::hitungHargaLangganan().
 */
class TenantRegistrationController extends Controller
{
    public function show(Request $request, PaketSistemBookingService $paket): Response
    {
        $paketDiminta = $request->query('paket');

        return Inertia::render('Daftar', [
            'paket' => $paket->semua(),
            'paketTerpilih' => is_string($paketDiminta) && $paket->ada($paketDiminta) ? $paketDiminta : 'basic',
            'hargaAddonDomain' => Tenant::HARGA_ADDON_CUSTOM_DOMAIN,
            'meta' => [
                'title' => 'Daftar Sistem Booking Lapangan - Green Deahan Sport',
                'description' => 'Daftarkan lapangan Anda dan mulai terima booking online. Bayar lewat QRIS, VA, atau e-wallet, akun aktif otomatis setelah pembayaran diterima.',
                'noindex' => ! config('situs.sistem_booking_publik'),
            ],
        ]);
    }

    /**
     * Halaman yang dilihat customer setelah kembali dari Mayar (lihat
     * redirectUrl di PaymentService::createLanggananTransaction()).
     * Aktivasi sesungguhnya terjadi lewat webhook
     * (PembayaranController::prosesWebhookLangganan()), yang bisa datang
     * beberapa saat SETELAH browser kembali ke halaman ini, jadi view-nya
     * WAJIB baca status tenant yang sebenarnya (dibayar_at/status_aktif),
     * tidak boleh asal anggap sudah aktif cuma karena sampai di halaman ini.
     */
    public function sukses(Request $request): Response
    {
        $tenant = Tenant::where('kode_pendaftaran', $request->query('kode_pendaftaran'))->firstOrFail();

        return $this->halamanSukses($tenant);
    }

    public function store(Request $request): Response|SymfonyResponse
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
        ], [
            'required' => ':Attribute wajib diisi.',
            'email' => 'Format email belum benar, misal nama@email.com.',
            'max' => ':Attribute terlalu panjang (maksimal :max karakter).',
            'subdomain.min' => 'Alamat website minimal :min karakter.',
            'subdomain.lowercase' => 'Alamat website hanya boleh huruf kecil.',
            'subdomain.regex' => 'Alamat website hanya boleh huruf kecil, angka, dan tanda hubung.',
            'email_pic.unique' => 'Email ini sudah terdaftar. Pakai email lain atau hubungi kami lewat WhatsApp.',
            'paket.in' => 'Pilih paket Basic, Pro, atau Premium.',
        ], [
            'nama_bisnis' => 'nama bisnis',
            'subdomain' => 'alamat website',
            'nama_pic' => 'nama lengkap',
            'email_pic' => 'email',
            'whatsapp_pic' => 'nomor WhatsApp',
            'paket' => 'paket',
            'custom_domain_diminta' => 'domain sendiri',
        ]);

        $domain = $data['subdomain'].'.greendeahan.com';
        $customDomainDiminta = $data['custom_domain_diminta'] ?? null;

        $tenant = Tenant::create([
            'nama_bisnis' => $data['nama_bisnis'],
            'domain' => $domain,
            'kode_pendaftaran' => Tenant::generateKodePendaftaran(),
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

        $jumlah = Tenant::hitungHargaLangganan($data['paket'], (bool) $tenant->custom_domain_diminta);

        // Panggilan ke Mayar sengaja setelah semua insert di atas selesai,
        // supaya tenant/fitur/cabang tetap tersimpan walau pembuatan invoice
        // gagal (fallback di bawah), sama seperti pola di
        // BookingController::buatBooking().
        try {
            $finishRedirectUrl = route('daftar.sukses', ['kode_pendaftaran' => $tenant->kode_pendaftaran]);

            $response = app(PaymentService::class)->createLanggananTransaction($tenant, $jumlah, $finishRedirectUrl);

            if ($response['redirect_url']) {
                // Inertia::location: form dikirim lewat Inertia, jadi redirect ke
                // domain Mayar harus berupa kunjungan penuh, bukan XHR.
                return Inertia::location($response['redirect_url']);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $this->halamanSukses($tenant, 'Pendaftaran Anda tersimpan, tapi link pembayaran online gagal dibuat. Tim kami sudah diberi tahu dan akan segera menghubungi Anda.');
    }

    /**
     * Status dibaca dari tenant yang sebenarnya (dibayar_at/status_aktif),
     * karena webhook Mayar bisa datang setelah browser kembali ke sini.
     */
    private function halamanSukses(Tenant $tenant, ?string $pembayaranError = null): Response
    {
        $status = match (true) {
            $pembayaranError !== null => 'gagal_bayar',
            $tenant->status_aktif && $tenant->dibayar_at !== null => 'aktif',
            default => 'menunggu',
        };

        return Inertia::render('DaftarSukses', [
            'pendaftaran' => [
                'status' => $status,
                'namaBisnis' => $tenant->nama_bisnis,
                'domain' => $tenant->domain,
                'email' => $tenant->email_admin,
                'customDomainDiminta' => $tenant->custom_domain_diminta,
                'pesan' => $pembayaranError,
            ],
            'meta' => ['title' => 'Status Pendaftaran - Green Deahan Sport', 'noindex' => true],
        ]);
    }

    /**
     * Kirim email ke admin platform kalau ada pendaftar baru. Informasional
     * saja, TIDAK memblokir apapun, tenant otomatis aktif begitu invoice
     * Mayar dibayar (lihat PembayaranController::prosesWebhookLangganan()).
     * Best-effort, kegagalan kirim email tidak boleh menggagalkan
     * pendaftaran tenant.
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
        $baris[] = "Tenant ini otomatis aktif begitu pembayaran Mayar diterima (kode: {$tenant->kode_pendaftaran}).";
        $baris[] = "Kalau perlu override manual (komplimenter, diskon, dst): php artisan tenant:activate {$subdomain}";

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
