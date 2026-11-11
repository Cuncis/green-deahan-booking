<?php

namespace App\Services;

use App\Models\Tenant;

/**
 * Menu sidebar aplikasi admin React (tenant admin dan superadmin). Dibangun
 * di server supaya menu fitur berbayar tetap dicek lewat punyaFitur() dan
 * tidak pernah terkirim ke tenant yang paketnya tidak punya fitur itu.
 *
 * `inertia` = halaman sudah berupa halaman React (navigasi tanpa reload);
 * false untuk halaman yang masih Blade, supaya dibuka dengan reload biasa.
 */
class NavigasiAdminService
{
    /**
     * Halaman admin tenant yang sudah dipindah ke React (Inertia).
     *
     * @var array<int, string>
     */
    private const RUTE_REACT_ADMIN = ['admin.dashboard', 'admin.booking', 'admin.lapangan', 'admin.jadwal', 'admin.laporan', 'admin.promo', 'admin.pengaturan'];

    /**
     * Halaman superadmin yang sudah dipindah ke React (Inertia).
     *
     * @var array<int, string>
     */
    private const RUTE_REACT_SUPERADMIN = ['superadmin.dashboard', 'superadmin.tenants', 'superadmin.tenants.create', 'superadmin.galeri', 'superadmin.artikel', 'superadmin.media', 'superadmin.seo'];

    /**
     * @return array<int, array{grup: string|null, item: array<int, array{label: string, href: string, ikon: string, rute: string|null, inertia: bool}>}>
     */
    public function untukTenant(Tenant $tenant): array
    {
        $utama = [
            $this->item('Dashboard', 'admin.dashboard', 'layout-dashboard'),
            $this->item('Semua Booking', 'admin.booking', 'clipboard-list'),
            $this->item('Lapangan Saya', 'admin.lapangan', 'goal'),
            $this->item('Jadwal', 'admin.jadwal', 'calendar-clock'),
        ];

        if ($tenant->punyaFitur('laporan_pendapatan')) {
            $utama[] = $this->item('Laporan Pendapatan', 'admin.laporan', 'chart-line');
        }

        if ($tenant->punyaFitur('kode_promo')) {
            $utama[] = $this->item('Promo', 'admin.promo', 'ticket-percent');
        }

        $tambahan = [];
        if ($tenant->punyaFitur('manajemen_staf')) {
            $tambahan[] = $this->bagianDashboard('Staf & Operator', 'staf', 'users');
        }
        if ($tenant->punyaFitur('sistem_membership')) {
            $tambahan[] = $this->bagianDashboard('Member & Loyalti', 'membership', 'crown');
        }
        if ($tenant->punyaFitur('reminder_otomatis')) {
            $tambahan[] = $this->bagianDashboard('Reminder Otomatis', 'reminder', 'bell-ring');
        }
        if ($tenant->punyaFitur('analitik_lanjutan')) {
            $tambahan[] = $this->bagianDashboard('Analitik Antar Cabang', 'analitik', 'chart-column');
        }

        $grup = [['grup' => null, 'item' => $utama]];
        if ($tambahan !== []) {
            $grup[] = ['grup' => 'Fitur '.ucfirst($tenant->paket), 'item' => $tambahan];
        }
        $grup[] = ['grup' => null, 'item' => [
            $this->item('Pengaturan', 'admin.pengaturan', 'settings'),
            ['label' => 'Lihat Halaman Booking', 'href' => route('booking.index'), 'ikon' => 'globe', 'rute' => null, 'inertia' => false],
        ]];

        return $grup;
    }

    /**
     * @return array<int, array{grup: string|null, item: array<int, array{label: string, href: string, ikon: string, rute: string|null, inertia: bool}>}>
     */
    public function untukSuperadmin(): array
    {
        return [
            ['grup' => null, 'item' => [
                $this->item('Dashboard', 'superadmin.dashboard', 'layout-dashboard', self::RUTE_REACT_SUPERADMIN),
                $this->item('Semua Tenant', 'superadmin.tenants', 'building', self::RUTE_REACT_SUPERADMIN),
                $this->item('Tambah Tenant', 'superadmin.tenants.create', 'user-plus', self::RUTE_REACT_SUPERADMIN),
            ]],
            ['grup' => 'Situs Korporat', 'item' => [
                $this->item('Galeri', 'superadmin.galeri', 'images', self::RUTE_REACT_SUPERADMIN),
                $this->item('Blog', 'superadmin.artikel', 'newspaper', self::RUTE_REACT_SUPERADMIN),
                $this->item('Media', 'superadmin.media', 'folder-open', self::RUTE_REACT_SUPERADMIN),
                $this->item('SEO dan Analytics', 'superadmin.seo', 'search-check', self::RUTE_REACT_SUPERADMIN),
            ]],
        ];
    }

    /**
     * @param  array<int, string>  $ruteReact
     * @return array{label: string, href: string, ikon: string, rute: string, inertia: bool}
     */
    private function item(string $label, string $rute, string $ikon, array $ruteReact = self::RUTE_REACT_ADMIN): array
    {
        return [
            'label' => $label,
            'href' => route($rute),
            'ikon' => $ikon,
            'rute' => $rute,
            'inertia' => in_array($rute, $ruteReact, true),
        ];
    }

    /**
     * Menu yang menuju bagian (section) di halaman Dashboard, mis. #staf.
     *
     * @return array{label: string, href: string, ikon: string, rute: null, inertia: bool}
     */
    private function bagianDashboard(string $label, string $anchor, string $ikon): array
    {
        return [
            'label' => $label,
            'href' => route('admin.dashboard').'#'.$anchor,
            'ikon' => $ikon,
            'rute' => null,
            'inertia' => false,
        ];
    }
}
