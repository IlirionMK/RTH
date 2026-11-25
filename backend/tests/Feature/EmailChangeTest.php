<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\EmailAlreadyRegistered;
use App\Notifications\VerifyNewEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailChangeTest extends TestCase
{
    use RefreshDatabase;

    private function confirmationUrl(User $user, string $hash, int $minutes = 60): string
    {
        return URL::temporarySignedRoute(
            'email-changes.store',
            now()->addMinutes($minutes),
            ['user' => $user->id, 'hash' => $hash],
            absolute: false,
        );
    }

    public function test_new_email_waits_for_confirmation(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'test@example.com']);
        Sanctum::actingAs($user);

        $this->patchJson(route('profile.update'), ['email' => 'renamed@example.com'])
            ->assertOk()
            ->assertJsonPath('data.email', 'test@example.com')
            ->assertJsonPath('data.pending_email', 'renamed@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'test@example.com',
            'pending_email' => 'renamed@example.com',
        ]);
        Notification::assertSentOnDemand(
            VerifyNewEmail::class,
            fn (VerifyNewEmail $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'renamed@example.com',
        );
    }

    public function test_confirmation_link_opens_the_web_app(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->patchJson(route('profile.update'), ['email' => 'renamed@example.com'])->assertOk();

        Notification::assertSentOnDemand(
            VerifyNewEmail::class,
            fn (VerifyNewEmail $notification, array $channels, AnonymousNotifiable $notifiable): bool => str_starts_with(
                $notification->toMail($notifiable)->actionUrl,
                "http://localhost:5173/email-changes/{$user->id}/".sha1('renamed@example.com'),
            ),
        );
    }

    public function test_taken_email_gets_the_same_answer_and_a_notice(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'other@example.com']);
        $user = User::factory()->create(['email' => 'test@example.com']);
        Sanctum::actingAs($user);

        $this->patchJson(route('profile.update'), ['email' => 'other@example.com'])
            ->assertOk()
            ->assertJsonPath('data.email', 'test@example.com')
            ->assertJsonPath('data.pending_email', 'other@example.com');

        Notification::assertSentOnDemand(
            EmailAlreadyRegistered::class,
            fn (EmailAlreadyRegistered $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'other@example.com',
        );
        Notification::assertSentOnDemandTimes(VerifyNewEmail::class, 0);
    }

    public function test_current_email_cancels_the_change(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'test@example.com', 'pending_email' => 'renamed@example.com']);
        Sanctum::actingAs($user);

        $this->patchJson(route('profile.update'), ['email' => 'test@example.com'])
            ->assertOk()
            ->assertJsonPath('data.pending_email', null);

        Notification::assertNothingSent();
    }

    public function test_signed_link_changes_the_email(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'test@example.com', 'pending_email' => 'renamed@example.com']);

        $this->postJson($this->confirmationUrl($user, sha1('renamed@example.com')))->assertNoContent();

        $user->refresh();
        $this->assertSame('renamed@example.com', $user->email);
        $this->assertNull($user->pending_email);
        $this->assertTrue($user->hasVerifiedEmail());
    }

    public function test_link_for_another_email_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com', 'pending_email' => 'renamed@example.com']);

        $this->postJson($this->confirmationUrl($user, sha1('other@example.com')))->assertForbidden();

        $this->assertSame('test@example.com', $user->fresh()?->email);
    }

    public function test_link_without_pending_email_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $this->postJson($this->confirmationUrl($user, sha1('renamed@example.com')))->assertForbidden();
    }

    public function test_expired_link_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com', 'pending_email' => 'renamed@example.com']);
        $url = $this->confirmationUrl($user, sha1('renamed@example.com'));

        $this->travel(61)->minutes();

        $this->postJson($url)->assertForbidden();
        $this->assertSame('test@example.com', $user->fresh()?->email);
    }

    public function test_email_taken_before_confirmation_is_a_conflict(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com', 'pending_email' => 'renamed@example.com']);
        User::factory()->create(['email' => 'renamed@example.com']);

        $this->postJson($this->confirmationUrl($user, sha1('renamed@example.com')))->assertConflict();

        $this->assertSame('test@example.com', $user->fresh()?->email);
    }
}
