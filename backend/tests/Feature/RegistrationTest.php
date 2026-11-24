<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_registers_with_name_email_and_password(): void
    {
        $this->postJson(route('users.store'), [
            'name' => 'Jan Kowalski',
            'email' => 'jan@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])
            ->assertCreated()
            ->assertHeader('Location', route('profile.show'))
            ->assertJsonPath('data.name', 'Jan Kowalski')
            ->assertJsonPath('data.email', 'jan@example.com')
            ->assertJsonMissingPath('data.password');

        $user = User::where('email', 'jan@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('secret-password', $user->password));
    }

    public function test_new_profile_is_private(): void
    {
        $this->postJson(route('users.store'), [
            'name' => 'Jan Kowalski',
            'email' => 'jan@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])
            ->assertCreated()
            ->assertJsonPath('data.is_public', false);

        $this->assertDatabaseHas('users', ['email' => 'jan@example.com', 'is_public' => false]);
    }

    public function test_registration_requires_name_email_and_password(): void
    {
        $this->postJson(route('users.store'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'jan@example.com']);

        $this->postJson(route('users.store'), [
            'name' => 'Jan Kowalski',
            'email' => 'jan@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_password_must_be_confirmed(): void
    {
        $this->postJson(route('users.store'), [
            'name' => 'Jan Kowalski',
            'email' => 'jan@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'other-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_registration_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson(route('users.store'))->assertUnprocessable();
        }

        $this->postJson(route('users.store'))->assertTooManyRequests();
    }
}
