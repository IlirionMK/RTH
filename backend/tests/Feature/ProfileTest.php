<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_sees_own_profile(): void
    {
        $user = User::factory()->create(['name' => 'Jan Kowalski', 'email' => 'jan@example.com']);
        Sanctum::actingAs($user);

        $this->getJson(route('profile.show'))
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', 'Jan Kowalski')
            ->assertJsonPath('data.email', 'jan@example.com')
            ->assertJsonPath('data.is_public', false);
    }

    public function test_user_changes_name_and_email(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->patchJson(route('profile.update'), [
            'name' => 'Anna Nowak',
            'email' => 'anna@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Anna Nowak')
            ->assertJsonPath('data.email', 'anna@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Anna Nowak',
            'email' => 'anna@example.com',
        ]);
    }

    public function test_user_keeps_own_email(): void
    {
        $user = User::factory()->create(['email' => 'jan@example.com']);
        Sanctum::actingAs($user);

        $this->patchJson(route('profile.update'), ['email' => 'jan@example.com'])->assertOk();
    }

    public function test_email_must_stay_unique(): void
    {
        User::factory()->create(['email' => 'anna@example.com']);
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson(route('profile.update'), ['email' => 'anna@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_name_and_email_cannot_be_empty(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson(route('profile.update'), ['name' => '', 'email' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_user_makes_profile_public_and_private(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->patchJson(route('profile.update'), ['is_public' => true])
            ->assertOk()
            ->assertJsonPath('data.is_public', true);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_public' => true]);

        $this->patchJson(route('profile.update'), ['is_public' => false])
            ->assertOk()
            ->assertJsonPath('data.is_public', false);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_public' => false]);
    }

    public function test_is_public_must_be_boolean(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson(route('profile.update'), ['is_public' => 'yes'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_public');
    }

    public function test_user_changes_password_with_current_password(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson(route('profile.password.update'), [
            'current_password' => 'password',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])->assertNoContent();

        $this->assertTrue(Hash::check('new-secret-password', $user->fresh()?->password ?? ''));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson(route('profile.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->assertTrue(Hash::check('password', $user->fresh()?->password ?? ''));
    }

    public function test_new_password_must_be_confirmed(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson(route('profile.password.update'), [
            'current_password' => 'password',
            'password' => 'new-secret-password',
            'password_confirmation' => 'other-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }
}
