<?php

namespace App\Http\Middleware;

use App\Services\NavigasiAdminService;
use Illuminate\Http\Request;
use Inertia\Middleware;

/**
 * Inertia untuk dua aplikasi React:
 * - halaman publik situs korporat (root view "app", CSS template Fuzio)
 * - aplikasi admin tenant + superadmin (root view "admin", Tailwind +
 *   shadcn/ui, lihat resources/js/admin/).
 * Rute yang tidak memanggil Inertia::render() tidak terpengaruh.
 */
class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     */
    protected $rootView = 'app';

    public function rootView(Request $request): string
    {
        return $this->halamanAdmin($request) ? 'admin' : 'app';
    }

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        if (! $this->halamanAdmin($request)) {
            return parent::share($request);
        }

        $user = $request->user();
        $diSuperadmin = $request->routeIs('superadmin.*');
        $tenant = ! $diSuperadmin && app()->bound('tenant') ? app('tenant') : null;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'name' => $user->name,
                    'email' => $user->email,
                ] : null,
            ],
            'area' => $diSuperadmin ? 'superadmin' : 'admin',
            'tenant' => $tenant ? [
                'nama_bisnis' => $tenant->nama_bisnis,
                'paket' => $tenant->paket,
            ] : null,
            'navigasi' => fn () => $diSuperadmin
                ? app(NavigasiAdminService::class)->untukSuperadmin()
                : ($tenant ? app(NavigasiAdminService::class)->untukTenant($tenant) : []),
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'invitationLink' => $request->session()->get('invitation_link'),
                'invitationTenant' => $request->session()->get('invitation_tenant'),
            ],
        ];
    }

    private function halamanAdmin(Request $request): bool
    {
        return $request->routeIs('admin.*', 'superadmin.*');
    }
}
