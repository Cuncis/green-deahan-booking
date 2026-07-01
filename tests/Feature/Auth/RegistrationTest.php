<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Registrasi publik dimatikan (lihat routes/auth.php): siapapun bisa
 * mendaftar lalu mengakses dashboard admin tenant manapun kalau dibiarkan
 * terbuka. Staf tenant sekarang hanya dibuat lewat undangan, lihat
 * InvitationAcceptTest.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_tidak_lagi_tersedia(): void
    {
        $response = $this->get('/register');

        $response->assertNotFound();
    }

    public function test_register_endpoint_tidak_lagi_bisa_dipakai(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertNotFound();
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }
}
