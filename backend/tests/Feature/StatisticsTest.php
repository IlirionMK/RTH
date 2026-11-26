<?php

namespace Tests\Feature;

use App\Models\Point;
use App\Models\Segment;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_statistics_cover_whole_history(): void
    {
        $user = $this->userWithTrips();
        Sanctum::actingAs($user);

        $this->getJson(route('statistics.show'))
            ->assertOk()
            ->assertJsonPath('data.trips_count', 2)
            ->assertJsonPath('data.countries_count', 3)
            ->assertJsonPath('data.countries', ['DE', 'FR', 'IT'])
            ->assertJsonPath('data.places_count', 4)
            ->assertJsonPath('data.distance_km', 2479.2)
            ->assertJsonPath('data.distance_by_transport', ['car' => 1054.3, 'plane' => 1182.5, 'train' => 242.4, 'other' => 0])
            ->assertJsonPath('data.years', [2025, 2024])
            ->assertJsonPath('data.distance_by_year', [
                ['year' => 2024, 'car' => 1054.3, 'plane' => 0, 'train' => 0, 'other' => 0],
                ['year' => 2025, 'car' => 0, 'plane' => 1182.5, 'train' => 242.4, 'other' => 0],
            ]);
    }

    public function test_statistics_of_year_count_trips_by_start_date(): void
    {
        $user = $this->userWithTrips();
        Sanctum::actingAs($user);

        $this->getJson(route('statistics.show', ['year' => 2024]))
            ->assertOk()
            ->assertJsonPath('data.trips_count', 1)
            ->assertJsonPath('data.countries', ['DE', 'FR'])
            ->assertJsonPath('data.places_count', 2)
            ->assertJsonPath('data.distance_km', 1054.3)
            ->assertJsonPath('data.years', [2025, 2024]);
        $this->getJson(route('statistics.show', ['year' => 2025]))
            ->assertOk()
            ->assertJsonPath('data.trips_count', 1)
            ->assertJsonPath('data.countries', ['DE', 'IT'])
            ->assertJsonPath('data.places_count', 3)
            ->assertJsonPath('data.distance_by_transport.car', 0);
    }

    public function test_user_without_trips_gets_zeros(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('statistics.show'))
            ->assertOk()
            ->assertJsonPath('data', [
                'trips_count' => 0,
                'countries_count' => 0,
                'countries' => [],
                'places_count' => 0,
                'distance_km' => 0,
                'distance_by_transport' => ['car' => 0, 'plane' => 0, 'train' => 0, 'other' => 0],
                'years' => [],
                'distance_by_year' => [],
            ]);
    }

    public function test_year_has_four_digits(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('statistics.show', ['year' => 'last']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('year');
    }

    public function test_guest_cannot_see_statistics(): void
    {
        $this->getJson(route('statistics.show'))->assertUnauthorized();
    }

    private function userWithTrips(): User
    {
        $user = User::factory()->create();

        $winter = Trip::factory()->for($user)->create(['starts_on' => '2024-12-30', 'ends_on' => '2025-01-05']);
        $berlin = $this->point($winter, 1, 52.52, 13.405, 'DE');
        $paris = $this->point($winter, 2, 48.8566, 2.3522, 'FR');
        $this->segment($berlin, $paris, 'car', 1054.3);

        $spring = Trip::factory()->for($user)->create(['starts_on' => '2025-04-10']);
        $berlinAgain = $this->point($spring, 1, 52.5201, 13.4049, 'DE');
        $rome = $this->point($spring, 2, 41.9028, 12.4964, 'IT');
        $sea = $this->point($spring, 3, 40.0, 12.0, null);
        $this->segment($berlinAgain, $rome, 'plane', 1182.5);
        $this->segment($rome, $sea, 'train', 242.4);

        $otherTrip = Trip::factory()->create(['starts_on' => '2025-04-10']);
        $this->segment($this->point($otherTrip, 1, 35.6762, 139.6503, 'JP'), $this->point($otherTrip, 2, 34.6937, 135.5023, 'JP'), 'train', 403.1);

        return $user;
    }

    private function point(Trip $trip, int $position, float $latitude, float $longitude, ?string $countryCode): Point
    {
        return Point::factory()->for($trip)->create([
            'position' => $position,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'country_code' => $countryCode,
        ]);
    }

    private function segment(Point $from, Point $to, string $transport, float $distanceKm): void
    {
        Segment::factory()->create([
            'trip_id' => $from->trip_id,
            'from_point_id' => $from->id,
            'to_point_id' => $to->id,
            'transport' => $transport,
            'distance_km' => $distanceKm,
        ]);
    }
}
