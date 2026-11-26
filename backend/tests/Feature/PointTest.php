<?php

namespace Tests\Feature;

use App\Models\Point;
use App\Models\Segment;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
    }

    public function test_user_adds_points_and_segments_join_them(): void
    {
        $trip = $this->tripWithPoints(['Berlin', 'Paris']);

        $response = $this->postJson(route('trips.points.store', $trip), [
            'name' => 'Rome',
            'latitude' => 41.9028,
            'longitude' => 12.4964,
        ]);

        $rome = Point::query()->where('name', 'Rome')->sole();
        $response->assertCreated()
            ->assertHeader('Location', route('points.show', $rome))
            ->assertJsonPath('data.name', 'Rome')
            ->assertJsonPath('data.latitude', 41.9028)
            ->assertJsonPath('data.longitude', 12.4964)
            ->assertJsonPath('data.position', 3);
        $this->assertSame(['Berlin', 'Paris', 'Rome'], $this->pointNames($trip));
        $this->assertSame(['Berlin-Paris car', 'Paris-Rome car'], $this->segmentNames($trip));
    }

    public function test_point_added_at_position_moves_next_points_and_keeps_their_segment(): void
    {
        $trip = $this->tripWithPoints(['Berlin', 'Paris']);
        $trip->segments()->sole()->update(['transport' => 'train']);

        $this->postJson(route('trips.points.store', $trip), [
            'name' => 'Rome',
            'latitude' => 41.9028,
            'longitude' => 12.4964,
            'position' => 1,
        ])->assertCreated()->assertJsonPath('data.position', 1);

        $this->assertSame(['Rome', 'Berlin', 'Paris'], $this->pointNames($trip));
        $this->assertSame(['Rome-Berlin car', 'Berlin-Paris train'], $this->segmentNames($trip));
    }

    public function test_point_added_past_the_end_goes_last(): void
    {
        $trip = $this->tripWithPoints(['Berlin']);

        $this->postJson(route('trips.points.store', $trip), [
            'name' => 'Paris',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'position' => 10,
        ])->assertCreated()->assertJsonPath('data.position', 2);
    }

    public function test_point_needs_name_and_real_coordinates(): void
    {
        $trip = $this->tripWithPoints([]);

        $this->postJson(route('trips.points.store', $trip), ['latitude' => 91, 'longitude' => -181, 'position' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'latitude', 'longitude', 'position']);
    }

    public function test_user_moves_point_up(): void
    {
        $trip = $this->tripWithPoints(['Berlin', 'Paris', 'Rome']);
        $rome = Point::query()->where('name', 'Rome')->sole();

        $this->patchJson(route('points.update', $rome), ['position' => 1])
            ->assertOk()
            ->assertJsonPath('data.position', 1);

        $this->assertSame(['Rome', 'Berlin', 'Paris'], $this->pointNames($trip));
        $this->assertSame(['Rome-Berlin car', 'Berlin-Paris car'], $this->segmentNames($trip));
    }

    public function test_user_moves_point_down(): void
    {
        $trip = $this->tripWithPoints(['Berlin', 'Paris', 'Rome']);
        $berlin = Point::query()->where('name', 'Berlin')->sole();

        $this->patchJson(route('points.update', $berlin), ['position' => 3])
            ->assertOk()
            ->assertJsonPath('data.position', 3);

        $this->assertSame(['Paris', 'Rome', 'Berlin'], $this->pointNames($trip));
        $this->assertSame(['Paris-Rome car', 'Rome-Berlin car'], $this->segmentNames($trip));
    }

    public function test_renamed_point_keeps_its_segments(): void
    {
        $trip = $this->tripWithPoints(['Berlin', 'Paris']);
        $segment = $trip->segments()->sole();
        $paris = Point::query()->where('name', 'Paris')->sole();

        $this->patchJson(route('points.update', $paris), ['name' => 'Paris Centre'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Paris Centre');

        $this->assertModelExists($segment);
    }

    public function test_user_removes_point_and_its_neighbours_get_joined(): void
    {
        $trip = $this->tripWithPoints(['Berlin', 'Paris', 'Rome']);
        $paris = Point::query()->where('name', 'Paris')->sole();

        $this->deleteJson(route('points.destroy', $paris))->assertNoContent();

        $this->assertModelMissing($paris);
        $this->assertSame(['Berlin', 'Rome'], $this->pointNames($trip));
        $this->assertSame([1, 2], $trip->points()->pluck('position')->all());
        $this->assertSame(['Berlin-Rome car'], $this->segmentNames($trip));
    }

    public function test_user_cannot_touch_points_of_another_users_trip(): void
    {
        $trip = $this->tripWithPoints(['Berlin']);
        $berlin = $trip->points()->sole();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('trips.points.store', $trip), ['name' => 'Paris', 'latitude' => 48.8566, 'longitude' => 2.3522])
            ->assertForbidden();
        $this->getJson(route('points.show', $berlin))->assertForbidden();
        $this->patchJson(route('points.update', $berlin), ['name' => 'Paris'])->assertForbidden();
        $this->deleteJson(route('points.destroy', $berlin))->assertForbidden();

        $this->assertModelExists($berlin);
    }

    /**
     * @param  list<string>  $names
     */
    private function tripWithPoints(array $names): Trip
    {
        $trip = Trip::factory()->create();
        Sanctum::actingAs($trip->user);

        foreach ($names as $name) {
            $this->postJson(route('trips.points.store', $trip), [
                'name' => $name,
                'latitude' => 50.0,
                'longitude' => 10.0,
            ])->assertCreated();
        }

        return $trip;
    }

    /**
     * @return list<string>
     */
    private function pointNames(Trip $trip): array
    {
        return $trip->points()->pluck('name')->all();
    }

    /**
     * @return list<string>
     */
    private function segmentNames(Trip $trip): array
    {
        return $trip->segments()
            ->with(['fromPoint', 'toPoint'])
            ->get()
            ->map(fn (Segment $segment): string => "{$segment->fromPoint->name}-{$segment->toPoint->name} {$segment->transport->value}")
            ->all();
    }
}
