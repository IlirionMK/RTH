<?php

namespace Tests\Feature;

use App\Models\Point;
use App\Models\Segment;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SegmentRouteTest extends TestCase
{
    use RefreshDatabase;

    private const BERLIN = ['Berlin', 52.52, 13.405];

    private const PARIS = ['Paris', 48.8566, 2.3522];

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['api.heigit.org/pelias/*' => Http::response(['features' => []])]);
    }

    public function test_car_segment_follows_roads(): void
    {
        Http::fake(['api.heigit.org/openrouteservice/*' => Http::response($this->road())]);

        $segment = $this->tripWith([self::BERLIN, self::PARIS])->segments()->sole();

        $this->assertSame([[13.405, 52.52], [8.6821, 50.1109], [2.3522, 48.8566]], $segment->geometry['coordinates']);
        $this->assertSame(1054.3, $segment->distance_km);
        $this->assertFalse($segment->is_approximate);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/openrouteservice/')
            && $request->hasHeader('Authorization', 'test-key')
            && $request['coordinates'] === [[13.405, 52.52], [2.3522, 48.8566]]);
    }

    public function test_map_line_drops_points_closer_than_500_metres_to_it(): void
    {
        $coordinates = [[10.1, 50.1], [10.6, 50.104], [11.1, 50.1], [11.106, 50.6], [11.1, 51.1]];
        Http::fake(['api.heigit.org/openrouteservice/*' => Http::response($this->road($coordinates))]);

        $segment = $this->tripWith([['Start', 50.1, 10.1], ['End', 51.1, 11.1]])->segments()->sole();

        $this->assertSame($coordinates, $segment->geometry['coordinates']);
        $this->assertSame([[10.1, 50.1], [11.1, 50.1], [11.106, 50.6], [11.1, 51.1]], $segment->map_geometry['coordinates']);
    }

    public function test_car_segment_is_approximate_straight_line_when_no_road_is_found(): void
    {
        Http::fake(['api.heigit.org/openrouteservice/*' => Http::response(['error' => ['code' => 2009]], 404)]);

        $segment = $this->tripWith([self::BERLIN, self::PARIS])->segments()->sole();

        $this->assertSame([[13.405, 52.52], [2.3522, 48.8566]], $segment->geometry['coordinates']);
        $this->assertSame(877.5, $segment->distance_km);
        $this->assertTrue($segment->is_approximate);
    }

    public function test_trip_is_saved_when_route_service_is_down(): void
    {
        Http::fake(['api.heigit.org/openrouteservice/*' => Http::failedConnection()]);

        $segment = $this->tripWith([self::BERLIN, self::PARIS])->segments()->sole();

        $this->assertTrue($segment->is_approximate);
    }

    public function test_same_road_is_asked_only_once(): void
    {
        Http::fake(['api.heigit.org/openrouteservice/*' => Http::response($this->road())]);

        $this->tripWith([self::BERLIN, self::PARIS]);
        $this->tripWith([self::BERLIN, self::PARIS]);

        $this->assertRoadsAsked(1);
    }

    public function test_plane_segment_is_arc_along_great_circle(): void
    {
        Http::fake(['api.heigit.org/openrouteservice/*' => Http::response(status: 500)]);
        $segment = $this->tripWith([self::BERLIN, ['New York', 40.7128, -74.006]])->segments()->sole();

        $this->patchJson(route('segments.update', $segment), ['transport' => 'plane'])
            ->assertOk()
            ->assertJsonPath('data.distance_km', 6385)
            ->assertJsonPath('data.is_approximate', false)
            ->assertJsonPath('data.geometry.type', 'LineString');

        $coordinates = $segment->refresh()->geometry['coordinates'];
        $this->assertCount(65, $coordinates);
        $this->assertSame([13.405, 52.52], $coordinates[0]);
        $this->assertSame([-74.006, 40.7128], $coordinates[64]);
        $this->assertGreaterThan(52.52, $coordinates[20][1]);
        $this->assertRoadsAsked(1);
    }

    public function test_plane_arc_over_pacific_does_not_jump_across_map(): void
    {
        Http::fake(['api.heigit.org/openrouteservice/*' => Http::response(status: 500)]);
        $segment = $this->tripWith([['Tokyo', 35.6762, 139.6503], ['Los Angeles', 34.0522, -118.2437]])
            ->segments()
            ->sole();

        $this->patchJson(route('segments.update', $segment), ['transport' => 'plane'])->assertOk();

        $longitudes = array_column($segment->refresh()->geometry['coordinates'], 0);
        foreach (array_slice($longitudes, 1) as $index => $longitude) {
            $this->assertLessThan(180, abs($longitude - $longitudes[$index]));
        }
        $this->assertEqualsWithDelta(-118.2437 + 360, end($longitudes), 0.000001);
    }

    public function test_train_segment_follows_rails(): void
    {
        Http::fake([
            'api.heigit.org/openrouteservice/*' => Http::response(status: 500),
            'brouter.de/*' => Http::response($this->rail()),
        ]);
        $segment = $this->tripWith([self::BERLIN, self::PARIS])->segments()->sole();

        $this->patchJson(route('segments.update', $segment), ['transport' => 'train'])
            ->assertOk()
            ->assertJsonPath('data.geometry.coordinates', [[13.405, 52.52], [9.7332, 52.3759], [2.3522, 48.8566]])
            ->assertJsonPath('data.distance_km', 1096.4)
            ->assertJsonPath('data.is_approximate', false);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'brouter.de/brouter')
            && $request['lonlats'] === '13.405,52.52|2.3522,48.8566'
            && $request['profile'] === 'rail'
            && $request['format'] === 'geojson');
    }

    public function test_train_segment_is_approximate_straight_line_when_no_rail_is_found(): void
    {
        Http::fake([
            'api.heigit.org/openrouteservice/*' => Http::response(status: 500),
            'brouter.de/*' => Http::response('from-position not mapped in existing datafile', 400),
        ]);
        $segment = $this->tripWith([self::BERLIN, self::PARIS])->segments()->sole();

        $this->patchJson(route('segments.update', $segment), ['transport' => 'train'])
            ->assertOk()
            ->assertJsonPath('data.geometry.coordinates', [[13.405, 52.52], [2.3522, 48.8566]])
            ->assertJsonPath('data.distance_km', 877.5)
            ->assertJsonPath('data.is_approximate', true);
    }

    public function test_command_draws_train_segments_along_rails(): void
    {
        Http::fake(['brouter.de/*' => Http::response($this->rail())]);
        $train = Segment::factory()->create(['transport' => 'train']);
        $car = Segment::factory()->create(['transport' => 'car']);

        $this->artisan('app:reroute-trains')
            ->expectsOutput('Train segments on rails: 1 of 1.')
            ->assertSuccessful();

        $this->assertSame(1096.4, $train->refresh()->distance_km);
        $this->assertCount(3, $train->geometry['coordinates']);
        $this->assertSame(877.5, $car->refresh()->distance_km);
        Http::assertSentCount(1);
    }

    public function test_moved_point_redraws_its_segments(): void
    {
        Http::fake(['api.heigit.org/openrouteservice/*' => Http::response(status: 500)]);
        $trip = $this->tripWith([self::BERLIN, self::PARIS]);
        $paris = Point::query()->where('name', 'Paris')->sole();

        $this->patchJson(route('points.update', $paris), ['name' => 'Rome', 'latitude' => 41.9028, 'longitude' => 12.4964])
            ->assertOk();

        $segment = $trip->segments()->sole();
        $this->assertSame([[13.405, 52.52], [12.4964, 41.9028]], $segment->geometry['coordinates']);
        $this->assertSame(1182.5, $segment->distance_km);
    }

    public function test_trip_shows_segment_lines_and_distances(): void
    {
        $segment = Segment::factory()->create(['distance_km' => 877.5, 'is_approximate' => true]);
        Sanctum::actingAs($segment->trip->user);

        $this->getJson(route('trips.show', $segment->trip))
            ->assertOk()
            ->assertJsonPath('data.segments.0.distance_km', 877.5)
            ->assertJsonPath('data.segments.0.is_approximate', true)
            ->assertJsonPath('data.segments.0.geometry', $segment->geometry);
    }

    /**
     * @param  list<array{string, float, float}>  $points
     */
    private function tripWith(array $points): Trip
    {
        $trip = Trip::factory()->create();
        Sanctum::actingAs($trip->user);

        foreach ($points as [$name, $latitude, $longitude]) {
            $this->postJson(route('trips.points.store', $trip), [
                'name' => $name,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ])->assertCreated();
        }

        return $trip;
    }

    private function assertRoadsAsked(int $count): void
    {
        $this->assertCount($count, Http::recorded(fn (Request $request): bool => str_contains($request->url(), '/openrouteservice/')));
    }

    /**
     * @param  list<array{float, float}>  $coordinates
     * @return array<string, mixed>
     */
    private function road(array $coordinates = [[13.405, 52.52], [8.6821, 50.1109], [2.3522, 48.8566]]): array
    {
        return [
            'features' => [[
                'geometry' => ['type' => 'LineString', 'coordinates' => $coordinates],
                'properties' => ['summary' => ['distance' => 1054321.0]],
            ]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rail(): array
    {
        return [
            'type' => 'FeatureCollection',
            'features' => [[
                'type' => 'Feature',
                'properties' => ['track-length' => '1096421'],
                'geometry' => [
                    'type' => 'LineString',
                    'coordinates' => [[13.405, 52.52, 34.0], [9.7332, 52.3759, 55.25], [2.3522, 48.8566, 35.5]],
                ],
            ]],
        ];
    }
}
