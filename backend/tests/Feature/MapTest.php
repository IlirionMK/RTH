<?php

namespace Tests\Feature;

use App\Models\Segment;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MapTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_shows_simplified_routes_of_all_users_trips(): void
    {
        $user = User::factory()->create();
        $berlinParis = Segment::factory()->for(Trip::factory()->for($user)->create(['name' => 'Paris']))->create([
            'transport' => 'car',
            'distance_km' => 1054.3,
            'geometry' => ['type' => 'LineString', 'coordinates' => [[13.405, 52.52], [8.6821, 50.1109], [2.3522, 48.8566]]],
            'map_geometry' => ['type' => 'LineString', 'coordinates' => [[13.405, 52.52], [2.3522, 48.8566]]],
        ]);
        $berlinRome = Segment::factory()->for(Trip::factory()->for($user)->create(['name' => 'Rome']))->create(['transport' => 'plane']);
        Segment::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson(route('map.show'))
            ->assertOk()
            ->assertJsonPath('data.type', 'FeatureCollection')
            ->assertJsonCount(2, 'data.features')
            ->assertJsonPath('data.features.0.type', 'Feature')
            ->assertJsonPath('data.features.0.id', $berlinParis->id)
            ->assertJsonPath('data.features.0.geometry.coordinates', [[13.405, 52.52], [2.3522, 48.8566]])
            ->assertJsonPath('data.features.0.properties', [
                'trip_id' => $berlinParis->trip_id,
                'trip_name' => 'Paris',
                'transport' => 'car',
                'distance_km' => 1054.3,
            ])
            ->assertJsonPath('data.features.1.id', $berlinRome->id)
            ->assertJsonPath('data.features.1.properties.transport', 'plane');
    }

    public function test_map_of_year_shows_trips_started_in_that_year(): void
    {
        $user = User::factory()->create();
        $newYear = Segment::factory()->for(Trip::factory()->for($user)->create(['starts_on' => '2024-12-30', 'ends_on' => '2025-01-05']))->create();
        $spring = Segment::factory()->for(Trip::factory()->for($user)->create(['starts_on' => '2025-04-10']))->create();
        Sanctum::actingAs($user);

        $this->getJson(route('map.show', ['year' => 2025]))
            ->assertOk()
            ->assertJsonCount(1, 'data.features')
            ->assertJsonPath('data.features.0.id', $spring->id);
        $this->getJson(route('map.show', ['year' => 2024]))
            ->assertOk()
            ->assertJsonCount(1, 'data.features')
            ->assertJsonPath('data.features.0.id', $newYear->id);
    }

    public function test_year_has_four_digits(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('map.show', ['year' => 25]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('year');
    }

    public function test_guest_cannot_see_map(): void
    {
        $this->getJson(route('map.show'))->assertUnauthorized();
    }
}
