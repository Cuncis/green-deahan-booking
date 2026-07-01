<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifikasiTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Tenant::where('domain', $request->getHost())
            ->where('status_aktif', true)
            ->with('fitur')
            ->first();

        if (! $tenant) {
            abort(404, 'Website tidak ditemukan');
        }

        if ($tenant->tanggal_berakhir && $tenant->tanggal_berakhir->isPast()) {
            abort(403, 'Masa aktif paket sudah berakhir');
        }

        app()->instance('tenant', $tenant);

        return $next($request);
    }
}
