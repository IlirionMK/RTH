<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function verificationUrl(User $user, string $hash, int $minutes = 60): string
    {
        return URL::temporarySignedRoute(
            'email-verifications.store',
            now()->addMinutes($minutes),
            ['user' => $user->id, 'hash' => $hash],
            absolute: false,
        );
    }

    public function test_signed_link_verifies_the_email(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'test@example.com']);

        $this->postJson($this->verificationUrl($user, sha1('test@example.com')))->assertNoContent();

        $this->assertTrue($user->fresh()?->hasVerifiedEmail());
    }

    public function test_verified_email_stays_verified(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com', 'email_verified_at' => '2026-01-01 10:00:00']);

        $this->postJson($this->verificationUrl($user, sha1('test@example.com')))->assertNoContent();

        $this->assertSame('2026-01-01 10:00:00', $user->fresh()?->email_verified_at?->format('Y-m-d H:i:s'));
    }

    public function test_link_for_another_email_is_rejected(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'test@example.com']);

        $this->postJson($this->verificationUrl($user, sha1('other@example.com')))->assertForbidden();

        $this->assertFalse($user->fresh()?->hasVerifiedEmail());
    }

    public function test_link_without_valid_signature_is_rejected(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'test@example.com']);
        $url = $this->verificationUrl($user, sha1('test@example.com'));

        $this->postJson(str_replace('signature=', 'signature=0', $url))->assertForbidden();

        $this->assertFalse($user->fresh()?->hasVerifiedEmail());
    }

    public function test_expired_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'test@example.com']);
        $url = $this->verificationUrl($user, sha1('test@example.com'));

        $this->travel(61)->minutes();

        $this->postJson($url)->assertForbidden();
        $this->assertFalse($user->fresh()?->hasVerifiedEmail());
    }
}
