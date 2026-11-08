<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Staf;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Staf tenant yang login dari domain platform (bukan domain tenant)
        // akan dikirim ke /admin di host yang sama dan kena 404 dari
        // IdentifikasiTenant, jadi arahkan mereka ke domain bisnisnya.
        if (! $request->user()->isSuperadmin() && ! $this->hostAdalahTenant($request)) {
            return $this->tolakLoginDiDomainPlatform($request);
        }

        $request->session()->regenerate();

        return redirect()->intended(route($request->user()->rutePascaLogin(), absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function hostAdalahTenant(Request $request): bool
    {
        $host = $request->getHost();

        return Tenant::where('status_aktif', true)
            ->where(fn ($query) => $query->where('domain', $host)->orWhere('custom_domain', $host))
            ->exists();
    }

    private function tolakLoginDiDomainPlatform(LoginRequest $request): RedirectResponse
    {
        $staf = Staf::withoutGlobalScopes()
            ->where('user_id', $request->user()->id)
            ->where('status_aktif', true)
            ->whereHas('tenant', fn ($query) => $query->where('status_aktif', true))
            ->with('tenant')
            ->first();

        Auth::guard('web')->logout();

        $pesan = $staf
            ? 'Akun ini terdaftar di dashboard bisnis, bukan di domain platform. Silakan masuk lewat '
                .$request->getScheme().'://'.($staf->tenant->custom_domain ?: $staf->tenant->domain).'/login'
            : 'Akun ini belum terhubung ke bisnis yang aktif. Hubungi admin platform.';

        return back()->withInput($request->only('email'))->withErrors(['email' => $pesan]);
    }
}
