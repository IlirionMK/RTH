<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('api')->prefix('api')->group(function (): void {
            Route::get('test-locale', fn (): array => ['locale' => app()->getLocale()]);
            Route::post('test-validation', fn (Request $request): array => $request->validate(['email' => 'required']));
        });
    }

    public function test_polish_is_used_when_no_language_is_requested(): void
    {
        $this->getJson('/api/test-locale', ['Accept-Language' => ''])->assertJsonPath('locale', 'pl');
    }

    public function test_english_is_used_when_requested(): void
    {
        $this->getJson('/api/test-locale', ['Accept-Language' => 'en-US,en;q=0.9'])
            ->assertJsonPath('locale', 'en');
    }

    public function test_unsupported_language_falls_back_to_polish(): void
    {
        $this->getJson('/api/test-locale', ['Accept-Language' => 'de-DE'])
            ->assertJsonPath('locale', 'pl');
    }

    public function test_validation_messages_are_in_english_when_requested(): void
    {
        $this->postJson('/api/test-validation', [], ['Accept-Language' => 'en'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'The email field is required.');
    }

    public function test_validation_messages_are_translated_to_polish(): void
    {
        $message = $this->postJson('/api/test-validation', [], ['Accept-Language' => 'pl'])
            ->assertUnprocessable()
            ->json('errors.email.0');

        $this->assertIsString($message);
        $this->assertNotSame('The email field is required.', $message);
        $this->assertStringStartsNotWith('validation.', $message);
    }
}
