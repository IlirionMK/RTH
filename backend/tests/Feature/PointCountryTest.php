<?php

namespace Tests\Feature;

use App\Models\Point;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PointCountryTest extends TestCase
{
    use RefreshDatabase;

    public function test_point_gets_country_from_its_coordinates(): void
    {
        Http::fake(['api.heigit.org/pelias/*' => Http::response($this->country('DEU'))]);

        $this->addBerlin()
            ->assertCreated()
            ->assertJsonPath('data.country_code', 'DE');

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'test-key')
            && str_starts_with($request->url(), 'https://api.heigit.org/pelias/v1/reverse?')
            && str_contains($request->url(), 'point.lat=52.52&point.lon=13.405&layers=country'));
    }

    public function test_moved_point_gets_country_of_its_new_place(): void
    {
        Http::fake(['api.heigit.org/pelias/*' => Http::sequence()->push($this->country('DEU'))->push($this->country('FRA'))]);
        $this->addBerlin()->assertCreated();
        $point = Point::query()->sole();

        $this->patchJson(route('points.update', $point), ['name' => 'Paris', 'latitude' => 48.8566, 'longitude' => 2.3522])
            ->assertOk()
            ->assertJsonPath('data.country_code', 'FR');
    }

    public function test_renamed_point_keeps_its_country_without_new_lookup(): void
    {
        Http::fake(['api.heigit.org/pelias/*' => Http::response($this->country('DEU'))]);
        $this->addBerlin()->assertCreated();
        $point = Point::query()->sole();

        $this->patchJson(route('points.update', $point), ['name' => 'Berlin Mitte'])
            ->assertOk()
            ->assertJsonPath('data.country_code', 'DE');

        Http::assertSentCount(1);
    }

    public function test_point_at_sea_has_no_country(): void
    {
        Http::fake(['api.heigit.org/pelias/*' => Http::response(['features' => []])]);

        $this->addBerlin()
            ->assertCreated()
            ->assertJsonPath('data.country_code', null);
    }

    public function test_point_is_saved_without_country_when_service_is_down(): void
    {
        Http::fake(['api.heigit.org/pelias/*' => Http::failedConnection()]);

        $this->addBerlin()
            ->assertCreated()
            ->assertJsonPath('data.country_code', null);
    }

    public function test_command_fills_in_missing_countries(): void
    {
        Http::fake(['api.heigit.org/pelias/*' => Http::sequence()->push($this->country('DEU'))->push(['features' => []])]);
        $berlin = Point::factory()->create(['latitude' => 52.52, 'longitude' => 13.405]);
        $sea = Point::factory()->create(['latitude' => 54.5, 'longitude' => 4.0]);
        $warsaw = Point::factory()->create(['latitude' => 52.2297, 'longitude' => 21.0122, 'country_code' => 'PL']);

        $this->artisan('app:resolve-countries')
            ->expectsOutput('Countries resolved: 1.')
            ->assertSuccessful();

        $this->assertSame('DE', $berlin->refresh()->country_code);
        $this->assertNull($sea->refresh()->country_code);
        $this->assertSame('PL', $warsaw->refresh()->country_code);
        Http::assertSentCount(2);
    }

    private function addBerlin(): TestResponse
    {
        $trip = Trip::factory()->create();
        Sanctum::actingAs($trip->user);

        return $this->postJson(route('trips.points.store', $trip), [
            'name' => 'Berlin',
            'latitude' => 52.52,
            'longitude' => 13.405,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function country(string $alpha3): array
    {
        return ['features' => [['properties' => ['layer' => 'country', 'country_a' => $alpha3]]]];
    }
}
