<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\EmailAlreadyRegistered;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_user_registers_and_gets_verification_link(): void
    {
        $this->postJson(route('users.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])
            ->assertNoContent(202);

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame('Test User', $user->name);
        $this->assertTrue(Hash::check('secret-password', $user->password));
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verification_link_opens_the_web_app(): void
    {
        $this->postJson(route('users.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertAccepted();

        $user = User::where('email', 'test@example.com')->firstOrFail();
        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user): bool {
            $url = $notification->toMail($user)->actionUrl;

            return str_starts_with($url, "http://localhost:5173/email-verifications/{$user->id}/");
        });
    }

    public function test_registered_email_gets_a_notice_instead_of_a_new_account(): void
    {
        $user = User::factory()->create(['name' => 'Other User', 'email' => 'test@example.com']);

        $this->postJson(route('users.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])
            ->assertNoContent(202);

        $this->assertDatabaseCount('users', 1);
        $user->refresh();
        $this->assertSame('Other User', $user->name);
        $this->assertTrue(Hash::check('password', $user->password));
        Notification::assertSentTo($user, EmailAlreadyRegistered::class);
        Notification::assertNotSentTo($user, VerifyEmail::class);
    }

    public function test_unverified_account_takes_the_new_name_and_password(): void
    {
        $user = User::factory()->unverified()->create(['name' => 'Other User', 'email' => 'test@example.com']);

        $this->postJson(route('users.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertAccepted();

        $this->assertDatabaseCount('users', 1);
        $user->refresh();
        $this->assertSame('Test User', $user->name);
        $this->assertTrue(Hash::check('secret-password', $user->password));
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_new_profile_is_private_and_uses_polish_and_light_theme(): void
    {
        $this->postJson(route('users.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertAccepted();

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'is_public' => false,
            'locale' => 'pl',
            'theme' => 'light',
        ]);
    }

    public function test_new_user_has_user_role(): void
    {
        $this->postJson(route('users.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => 'admin',
        ])->assertAccepted();

        $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'role' => 'user']);
    }

    public function test_registration_requires_name_email_and_password(): void
    {
        $this->postJson(route('users.store'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_password_must_be_confirmed(): void
    {
        $this->postJson(route('users.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
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
