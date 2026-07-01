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
        $host = $request->getHost();

        // domain/custom_domain harus dikelompokkan dalam satu closure supaya
        // tidak kena jebakan urutan operator SQL: where(A)->orWhere(B)->where(C)
        // tanpa closure akan jadi "A OR (B AND C)", bukan "(A OR B) AND C" yang
        // dimaksud (status_aktif=false bisa lolos lewat kondisi domain saja).
        $tenant = Tenant::where('status_aktif', true)
            ->where(function ($query) use ($host) {
                $query->where('domain', $host)
                    ->orWhere('custom_domain', $host);
            })
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
