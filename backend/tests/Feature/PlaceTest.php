<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_finds_places_by_name(): void
    {
        Http::fake(['api.heigit.org/pelias/*' => Http::response($this->places())]);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('places.index', ['query' => 'Berlin']), ['Accept-Language' => 'pl'])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Berlin')
            ->assertJsonPath('data.0.label', 'Berlin, Niemcy')
            ->assertJsonPath('data.0.country_code', 'DE')
            ->assertJsonPath('data.0.latitude', 52.524932)
            ->assertJsonPath('data.0.longitude', 13.407032)
            ->assertJsonPath('data.1.country_code', null);

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'test-key')
            && $request['text'] === 'Berlin'
            && $request['lang'] === 'pl');
    }

    public function test_same_search_is_asked_only_once(): void
    {
        Http::fake(['api.heigit.org/pelias/*' => Http::response($this->places())]);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('places.index', ['query' => 'Berlin']))->assertOk();
        $this->getJson(route('places.index', ['query' => 'berlin']))->assertOk();

        Http::assertSentCount(1);
    }

    public function test_search_needs_at_least_two_letters(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('places.index', ['query' => 'B']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('query');
    }

    public function test_search_says_when_service_is_down(): void
    {
        Http::fake(['api.heigit.org/pelias/*' => Http::failedConnection()]);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('places.index', ['query' => 'Berlin']), ['Accept-Language' => 'en'])
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'Place search is not available right now. Try again later.');
    }

    public function test_guest_cannot_search_places(): void
    {
        $this->getJson(route('places.index', ['query' => 'Berlin']))->assertUnauthorized();
    }

    /**
     * @return array<string, mixed>
     */
    private function places(): array
    {
        return [
            'features' => [
                [
                    'geometry' => ['type' => 'Point', 'coordinates' => [13.407032, 52.524932]],
                    'properties' => ['name' => 'Berlin', 'label' => 'Berlin, Niemcy', 'country_a' => 'DEU'],
                ],
                [
                    'geometry' => ['type' => 'Point', 'coordinates' => [-30.0, 40.0]],
                    'properties' => ['name' => 'North Atlantic Ocean', 'label' => 'North Atlantic Ocean'],
                ],
            ],
        ];
    }
}
