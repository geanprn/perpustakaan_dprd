<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_seeder_creates_an_admin_account_from_env(): void
    {
        $username = env('ADMIN_USERNAME', 'admin');
        $password = env('ADMIN_PASSWORD', 'password');

        $this->seed(AdminSeeder::class);

        $user = User::query()->where('username', $username)->first();

        $this->assertNotNull($user);
        $this->assertNotSame($password, $user->password);
        $this->assertTrue(Hash::check($password, $user->password));
    }

    public function test_admin_seeder_creates_only_one_account(): void
    {
        $this->seed(AdminSeeder::class);

        $this->assertSame(1, User::query()->count());
    }

    public function test_admin_can_login_with_seeded_credentials(): void
    {
        $username = env('ADMIN_USERNAME', 'admin');
        $password = env('ADMIN_PASSWORD', 'password');

        $this->seed(AdminSeeder::class);

        $response = $this->post('/login', [
            'username' => $username,
            'password' => $password,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }
}
