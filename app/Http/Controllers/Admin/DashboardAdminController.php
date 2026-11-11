<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReminderLog;
use App\Services\DashboardAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dashboard admin tenant (React, resources/js/admin/pages/admin/Dashboard.tsx).
 * Data dihitung di DashboardAdminService; halaman me-refresh datanya tiap
 * 30 detik (pengganti wire:poll).
 */
class DashboardAdminController extends Controller
{
    public function index(Request $request, DashboardAdminService $dashboard): Response
    {
        $tenant = app('tenant');
        $cabangId = $request->filled('cabang') ? (int) $request->query('cabang') : null;

        return Inertia::render('admin/Dashboard', $dashboard->data($tenant, $cabangId));
    }

    /**
     * Tandai satu reminder sudah dikirim manual lewat tombol "Kirim Sekarang"
     * (chat WhatsApp dibuka di browser pada klik yang sama). Guard
     * status='menunggu' bikin aman diklik dua kali, tenant_id eksplisit
     * sesuai references/multi-tenant.md.
     */
    public function tandaiReminderTerkirim(int $reminderLog): RedirectResponse
    {
        abort_unless(app('tenant')->punyaFitur('reminder_otomatis'), 404);

        ReminderLog::where('tenant_id', app('tenant')->id)
            ->where('id', $reminderLog)
            ->where('status', 'menunggu')
            ->update(['status' => 'terkirim']);

        return back();
    }
}
