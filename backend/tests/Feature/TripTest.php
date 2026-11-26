<?php

namespace Tests\Feature;

use App\Models\Point;
use App\Models\Segment;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TripTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_creates_trip(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson(route('trips.store'), [
            'name' => 'Test Trip',
            'starts_on' => '2026-07-01',
            'ends_on' => '2026-07-14',
            'description' => 'Summer by the sea',
        ]);

        $trip = Trip::query()->sole();
        $response->assertCreated()
            ->assertHeader('Location', route('trips.show', $trip))
            ->assertJsonPath('data.id', $trip->id)
            ->assertJsonPath('data.name', 'Test Trip')
            ->assertJsonPath('data.starts_on', '2026-07-01')
            ->assertJsonPath('data.ends_on', '2026-07-14')
            ->assertJsonPath('data.description', 'Summer by the sea');
        $this->assertDatabaseHas('trips', ['user_id' => $user->id, 'name' => 'Test Trip']);
    }

    public function test_trip_can_last_one_day_without_description(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('trips.store'), ['name' => 'Test Trip', 'starts_on' => '2026-07-01'])
            ->assertCreated()
            ->assertJsonPath('data.ends_on', null)
            ->assertJsonPath('data.description', null);
    }

    public function test_trip_needs_name_and_start_date(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('trips.store'), ['starts_on' => '01.07.2026'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'starts_on']);
    }

    public function test_trip_cannot_end_before_it_starts(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('trips.store'), [
            'name' => 'Test Trip',
            'starts_on' => '2026-07-14',
            'ends_on' => '2026-07-01',
        ])->assertUnprocessable()->assertJsonValidationErrors('ends_on');
    }

    public function test_date_errors_name_the_field_in_polish(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('trips.store'), ['name' => 'Test Trip'], ['Accept-Language' => 'pl'])
            ->assertJsonPath('errors.starts_on.0', 'Pole data rozpoczęcia jest wymagane.');
    }

    public function test_user_sees_own_trips_newest_first(): void
    {
        $user = User::factory()->create();
        Trip::factory()->for($user)->create(['name' => 'Older Trip', 'starts_on' => '2024-05-01']);
        Trip::factory()->for($user)->create(['name' => 'Newest Trip', 'starts_on' => '2026-05-01']);
        Trip::factory()->for($user)->create(['name' => 'Middle Trip', 'starts_on' => '2025-05-01']);
        Trip::factory()->create(['name' => 'Other Trip']);
        Sanctum::actingAs($user);

        $this->getJson(route('trips.index'))
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.name', 'Newest Trip')
            ->assertJsonPath('data.1.name', 'Middle Trip')
            ->assertJsonPath('data.2.name', 'Older Trip');
    }

    public function test_trip_list_shows_route_countries_transports_and_distance(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->for($user)->create(['starts_on' => '2026-05-01']);
        $berlin = Point::factory()->for($trip)->create(['name' => 'Berlin', 'position' => 1, 'country_code' => 'DE']);
        $paris = Point::factory()->for($trip)->create(['name' => 'Paris', 'position' => 2, 'country_code' => 'FR']);
        $munich = Point::factory()->for($trip)->create(['name' => 'Munich', 'position' => 3, 'country_code' => 'DE']);
        Segment::factory()->for($trip)->create(['from_point_id' => $berlin->id, 'to_point_id' => $paris->id, 'transport' => 'plane', 'distance_km' => 877.5]);
        Segment::factory()->for($trip)->create(['from_point_id' => $paris->id, 'to_point_id' => $munich->id, 'transport' => 'car', 'distance_km' => 832.4]);
        Trip::factory()->for($user)->create(['starts_on' => '2025-05-01']);
        Sanctum::actingAs($user);

        $this->getJson(route('trips.index'))
            ->assertOk()
            ->assertJsonPath('data.0.distance_km', 1709.9)
            ->assertJsonPath('data.0.countries', ['DE', 'FR'])
            ->assertJsonPath('data.0.transports', ['car', 'plane'])
            ->assertJsonPath('data.0.points.*.name', ['Berlin', 'Paris', 'Munich'])
            ->assertJsonMissingPath('data.0.segments')
            ->assertJsonPath('data.1.distance_km', 0)
            ->assertJsonPath('data.1.countries', [])
            ->assertJsonPath('data.1.transports', []);
    }

    public function test_trip_list_is_paginated(): void
    {
        $user = User::factory()->create();
        Trip::factory()->for($user)->count(16)->create();
        Sanctum::actingAs($user);

        $this->getJson(route('trips.index'))
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.total', 16)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_user_sees_trip_with_points_and_segments_in_order(): void
    {
        $trip = Trip::factory()->create();
        $paris = Point::factory()->for($trip)->create(['name' => 'Paris', 'position' => 2]);
        $berlin = Point::factory()->for($trip)->create(['name' => 'Berlin', 'position' => 1]);
        $rome = Point::factory()->for($trip)->create(['name' => 'Rome', 'position' => 3]);
        Segment::factory()->for($trip)->create(['from_point_id' => $paris->id, 'to_point_id' => $rome->id]);
        Segment::factory()->for($trip)->create([
            'from_point_id' => $berlin->id,
            'to_point_id' => $paris->id,
            'transport' => 'plane',
        ]);
        Sanctum::actingAs($trip->user);

        $this->getJson(route('trips.show', $trip))
            ->assertOk()
            ->assertJsonPath('data.points.0.name', 'Berlin')
            ->assertJsonPath('data.points.1.name', 'Paris')
            ->assertJsonPath('data.points.2.name', 'Rome')
            ->assertJsonPath('data.segments.0.from_point_id', $berlin->id)
            ->assertJsonPath('data.segments.0.transport', 'plane')
            ->assertJsonPath('data.segments.1.from_point_id', $paris->id)
            ->assertJsonPath('data.segments.1.transport', 'car');
    }

    public function test_user_changes_trip(): void
    {
        $trip = Trip::factory()->create(['starts_on' => '2026-07-01', 'ends_on' => '2026-07-14']);
        Sanctum::actingAs($trip->user);

        $this->patchJson(route('trips.update', $trip), ['name' => 'Renamed Trip', 'starts_on' => '2026-07-03'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed Trip')
            ->assertJsonPath('data.starts_on', '2026-07-03')
            ->assertJsonPath('data.ends_on', '2026-07-14');

        $this->assertDatabaseHas('trips', ['id' => $trip->id, 'name' => 'Renamed Trip']);
    }

    public function test_new_end_date_is_checked_against_saved_start_date(): void
    {
        $trip = Trip::factory()->create(['starts_on' => '2026-07-10']);
        Sanctum::actingAs($trip->user);

        $this->patchJson(route('trips.update', $trip), ['ends_on' => '2026-07-01'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ends_on');
    }

    public function test_new_start_date_is_checked_against_saved_end_date(): void
    {
        $trip = Trip::factory()->create(['starts_on' => '2026-07-01', 'ends_on' => '2026-07-14']);
        Sanctum::actingAs($trip->user);

        $this->patchJson(route('trips.update', $trip), ['starts_on' => '2026-07-20'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ends_on');
    }

    public function test_user_removes_end_date(): void
    {
        $trip = Trip::factory()->create(['starts_on' => '2026-07-01', 'ends_on' => '2026-07-14']);
        Sanctum::actingAs($trip->user);

        $this->patchJson(route('trips.update', $trip), ['ends_on' => null])
            ->assertOk()
            ->assertJsonPath('data.ends_on', null);
    }

    public function test_user_deletes_trip_with_its_points_and_segments(): void
    {
        $segment = Segment::factory()->create();
        $trip = $segment->trip;
        Sanctum::actingAs($trip->user);

        $this->deleteJson(route('trips.destroy', $trip))->assertNoContent();

        $this->assertModelMissing($trip);
        $this->assertModelMissing($segment);
        $this->assertDatabaseMissing('points', ['trip_id' => $trip->id]);
    }

    public function test_user_cannot_see_change_or_delete_trip_of_another_user(): void
    {
        $trip = Trip::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('trips.show', $trip))->assertForbidden();
        $this->patchJson(route('trips.update', $trip), ['name' => 'Renamed Trip'])->assertForbidden();
        $this->deleteJson(route('trips.destroy', $trip))->assertForbidden();

        $this->assertModelExists($trip);
    }

    public function test_guest_cannot_use_trips(): void
    {
        $this->getJson(route('trips.index'))->assertUnauthorized();
        $this->postJson(route('trips.store'), ['name' => 'Test Trip', 'starts_on' => '2026-07-01'])
            ->assertUnauthorized();
    }
}
