<?php

namespace App\Http\Middleware;

use App\Models\Staf;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pastikan user yang login benar-benar staf dari tenant yang sedang
 * diakses (berdasarkan domain). Middleware 'auth' saja tidak cukup di
 * project multi-tenant ini, karena satu akun user bisa saja bukan staf
 * tenant manapun, atau staf tenant lain yang beda domain.
 */
class CheckTenantStaf
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app('tenant');
        $user = $request->user();

        $punyaAkses = Staf::where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->where('status_aktif', true)
            ->exists();

        abort_unless($punyaAkses, 403, 'Kamu tidak memiliki akses ke dashboard ini.');

        return $next($request);
    }
}
