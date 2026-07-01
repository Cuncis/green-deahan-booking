<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Staf;
use App\Models\TenantInvitation;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class InvitationController extends Controller
{
    /**
     * Tampilkan form terima undangan, email sudah terisi otomatis dari
     * baris tenant_invitations supaya tidak bisa diganti user.
     */
    public function showInvite(string $token): View
    {
        $invitation = $this->ambilInvitationValid($token);

        return view('auth.accept-invite', [
            'invitation' => $invitation,
            'token' => $token,
        ]);
    }

    /**
     * Terima undangan: buat (atau pakai) akun user untuk email tersebut,
     * pasang baris staf sesuai role di undangan, lalu tandai token dipakai.
     */
    public function acceptInvite(Request $request, string $token): RedirectResponse
    {
        $invitation = $this->ambilInvitationValid($token);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::firstOrCreate(
            ['email' => $invitation->email],
            ['name' => $data['name'], 'password' => Hash::make($data['password'])],
        );

        // Token undangan hanya bisa didapat dari admin tenant yang
        // mengundang lewat command tenant:invite, jadi memegang token ini
        // sudah setingkat bukti kepemilikan email seperti klik link
        // verifikasi biasa. Langsung tandai terverifikasi supaya user baru
        // tidak terjebak di halaman verify-email padahal MAIL_MAILER=log.
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        Staf::firstOrCreate(
            ['tenant_id' => $invitation->tenant_id, 'user_id' => $user->id],
            ['role' => $invitation->role, 'status_aktif' => true],
        );

        $invitation->update(['used_at' => now()]);

        if ($user->wasRecentlyCreated) {
            event(new Registered($user));
        }

        Auth::login($user);

        return redirect()->route('admin.dashboard');
    }

    private function ambilInvitationValid(string $token): TenantInvitation
    {
        $invitation = TenantInvitation::where('token', $token)->first();

        abort_if(
            ! $invitation || ! $invitation->masihValid(),
            404,
            'Link undangan tidak valid atau sudah kadaluarsa.',
        );

        return $invitation;
    }
}
