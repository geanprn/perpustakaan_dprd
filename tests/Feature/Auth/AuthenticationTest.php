<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('admin|127.0.0.1');
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_username_and_password(): void
    {
        $user = User::factory()->create([
            'username' => 'petugas',
            'password' => 'password',
        ]);

        $response = $this->post('/login', [
            'username' => 'petugas',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        User::factory()->create([
            'username' => 'petugas',
            'password' => 'password',
        ]);

        $response = $this->from('/login')->post('/login', [
            'username' => 'petugas',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('username');
    }

    public function test_login_error_message_is_in_indonesian_and_stays_on_login_page(): void
    {
        User::factory()->create([
            'username' => 'petugas',
            'password' => 'password',
        ]);

        $response = $this->from('/login')->post('/login', [
            'username' => 'petugas',
            'password' => 'salah',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'username' => 'Username atau password yang Anda masukkan salah.',
        ]);
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        User::factory()->create([
            'username' => 'petugas',
            'password' => 'password',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', [
                'username' => 'petugas',
                'password' => 'salah',
            ]);
        }

        $response = $this->from('/login')->post('/login', [
            'username' => 'petugas',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
        $this->assertStringContainsString(
            'Terlalu banyak percobaan login',
            session('errors')->first('username'),
        );
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    public function test_registration_is_not_available(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Hacker',
            'username' => 'hacker',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();
    }

    public function test_password_reset_via_email_is_not_available(): void
    {
        $this->get('/forgot-password')->assertNotFound();
        $this->post('/forgot-password', ['email' => 'admin@example.com'])
            ->assertNotFound();
    }
}
