<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_administrator(): void
    {
        $this->artisan('app:create-admin', ['name' => 'Admin User', 'email' => 'admin@example.com'])
            ->expectsQuestion('Password', 'admin-password-1')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('admin-password-1', $admin->password));
    }

    public function test_command_rejects_taken_email(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        $this->artisan('app:create-admin', ['name' => 'Admin User', 'email' => 'admin@example.com'])
            ->expectsQuestion('Password', 'admin-password-1')
            ->assertFailed();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseMissing('users', ['role' => 'admin']);
    }

    public function test_command_rejects_short_password(): void
    {
        $this->artisan('app:create-admin', ['name' => 'Admin User', 'email' => 'admin@example.com'])
            ->expectsQuestion('Password', 'short')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }
}
