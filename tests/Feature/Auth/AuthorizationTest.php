<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_admin_routes(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_guest_is_redirected_to_login_for_all_admin_menu_routes(): void
    {
        $routes = [
            '/admin/dashboard',
            '/admin/buku',
            '/admin/kategori',
            '/admin/peminjam',
            '/admin/peminjaman',
            '/admin/pengembalian',
            '/admin/laporan',
            '/admin/profil',
        ];

        foreach ($routes as $route) {
            $this->get($route)->assertRedirect('/login');
        }
    }

    public function test_authenticated_admin_can_access_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertStatus(200);
    }

    public function test_authenticated_admin_can_access_all_admin_menu_placeholders(): void
    {
        $user = User::factory()->create();

        $routes = [
            '/admin/buku',
            '/admin/kategori',
            '/admin/peminjam',
            '/admin/peminjaman',
            '/admin/pengembalian',
            '/admin/laporan',
            '/admin/profil',
        ];

        foreach ($routes as $route) {
            $this->actingAs($user)->get($route)->assertStatus(200);
        }
    }

    public function test_public_home_page_is_accessible_without_login(): void
    {
        $this->get('/')->assertStatus(200);
    }
}
