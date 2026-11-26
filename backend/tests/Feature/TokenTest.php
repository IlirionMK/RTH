<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_logs_in_and_gets_token(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $this->postJson(route('tokens.store'), [
            'email' => 'test@example.com',
            'password' => 'password',
            'device_name' => 'phone',
        ])
            ->assertCreated()
            ->assertHeader('Location', route('tokens.destroy'))
            ->assertJsonPath('data.user.email', 'test@example.com')
            ->assertJsonPath('data.user.locale', 'pl')
            ->assertJsonPath('data.user.theme', 'light')
            ->assertJsonStructure(['data' => ['token']]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'phone',
        ]);
    }

    public function test_token_gives_access_to_protected_resources(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $token = $this->postJson(route('tokens.store'), [
            'email' => 'test@example.com',
            'password' => 'password',
            'device_name' => 'phone',
        ])->json('data.token');

        $this->getJson(route('profile.show'), ['Authorization' => "Bearer $token"])
            ->assertOk()
            ->assertJsonPath('data.email', 'test@example.com');
    }

    public function test_wrong_password_and_unknown_email_give_the_same_message(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $wrongPassword = $this->postJson(route('tokens.store'), [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
            'device_name' => 'phone',
        ])
            ->assertUnprocessable()
            ->assertJsonMissingValidationErrors('password')
            ->json('errors.email.0');

        $unknownEmail = $this->postJson(route('tokens.store'), [
            'email' => 'unknown@example.com',
            'password' => 'password',
            'device_name' => 'phone',
        ])
            ->assertUnprocessable()
            ->assertJsonMissingValidationErrors('password')
            ->json('errors.email.0');

        $this->assertSame(__('auth.failed'), $wrongPassword);
        $this->assertSame($wrongPassword, $unknownEmail);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_unverified_user_gets_a_new_link_instead_of_a_token(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create(['email' => 'test@example.com']);

        $this->postJson(route('tokens.store'), [
            'email' => 'test@example.com',
            'password' => 'password',
            'device_name' => 'phone',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', __('Confirm your email address. We have sent you a new link.'));

        $this->assertDatabaseCount('personal_access_tokens', 0);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_unverified_user_with_wrong_password_gets_no_link(): void
    {
        Notification::fake();
        User::factory()->unverified()->create(['email' => 'test@example.com']);

        $this->postJson(route('tokens.store'), [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
            'device_name' => 'phone',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', __('auth.failed'));

        Notification::assertNothingSent();
    }

    public function test_blocked_user_gets_no_token(): void
    {
        User::factory()->blocked()->create(['email' => 'test@example.com']);

        $this->postJson(route('tokens.store'), [
            'email' => 'test@example.com',
            'password' => 'password',
            'device_name' => 'phone',
        ], ['Accept-Language' => 'pl'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'Twoje konto jest zablokowane.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_blocked_user_with_wrong_password_is_not_told_about_the_block(): void
    {
        User::factory()->blocked()->create(['email' => 'test@example.com']);

        $this->postJson(route('tokens.store'), [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
            'device_name' => 'phone',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', __('auth.failed'));
    }

    public function test_login_requires_email_password_and_device_name(): void
    {
        $this->postJson(route('tokens.store'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password', 'device_name']);
    }

    public function test_login_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson(route('tokens.store'))->assertUnprocessable();
        }

        $this->postJson(route('tokens.store'))->assertTooManyRequests();
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('phone')->plainTextToken;

        $this->deleteJson(route('tokens.destroy'), [], ['Authorization' => "Bearer $token"])
            ->assertNoContent();

        $this->app['auth']->forgetGuards();

        $this->getJson(route('profile.show'), ['Authorization' => "Bearer $token"])
            ->assertUnauthorized();
    }

    public function test_logout_keeps_other_tokens(): void
    {
        $user = User::factory()->create();
        $phone = $user->createToken('phone')->plainTextToken;
        $laptop = $user->createToken('laptop');

        $this->deleteJson(route('tokens.destroy'), [], ['Authorization' => "Bearer $phone"])
            ->assertNoContent();

        $this->assertModelExists($laptop->accessToken);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_protected_resources_require_token(): void
    {
        $this->getJson(route('profile.show'))->assertUnauthorized();
        $this->patchJson(route('profile.update'))->assertUnauthorized();
        $this->putJson(route('profile.password.update'))->assertUnauthorized();
        $this->deleteJson(route('tokens.destroy'))->assertUnauthorized();
    }

    public function test_web_app_from_another_origin_can_call_api(): void
    {
        $this->options(route('tokens.store'), [], [
            'Origin' => 'http://localhost:5173',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'authorization, content-type',
        ])
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', '*');
    }
}
