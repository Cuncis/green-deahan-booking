<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Lonceng notifikasi superadmin: buka satu notifikasi (ditandai dibaca lalu
 * diarahkan ke tautannya) dan tandai semuanya dibaca. Hanya notifikasi milik
 * pengguna yang sedang masuk yang bisa disentuh.
 */
class NotifikasiController extends Controller
{
    public function buka(Request $request, string $id): RedirectResponse
    {
        $notifikasi = $request->user()->notifications()->findOrFail($id);
        $notifikasi->markAsRead();

        $tujuan = (string) ($notifikasi->data['url'] ?? '');

        // Hanya jalur di situs ini (diawali satu garis miring), bukan alamat luar.
        $aman = str_starts_with($tujuan, '/') && ! str_starts_with($tujuan, '//');

        return redirect($aman ? $tujuan : route('superadmin.log-error', [], false));
    }

    public function bacaSemua(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
