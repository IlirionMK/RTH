<?php

namespace Tests\Feature;

use App\Models\Point;
use App\Models\Segment;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PublicProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_finds_public_profiles_by_part_of_name(): void
    {
        $zoe = User::factory()->create(['name' => 'Zoe Test', 'is_public' => true]);
        $anna = User::factory()->create(['name' => 'Anna Tester', 'is_public' => true]);
        User::factory()->create(['name' => 'Private Test', 'is_public' => false]);
        User::factory()->create(['name' => 'Other User', 'is_public' => true]);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('public-profiles.index', ['query' => 'TEST']))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0', ['id' => $anna->id, 'name' => 'Anna Tester', 'trips_count' => 0, 'countries_count' => 0])
            ->assertJsonPath('data.1.id', $zoe->id)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_user_sees_all_public_profiles_with_trip_and_country_counts(): void
    {
        $owner = User::factory()->create(['name' => 'Test User', 'is_public' => true]);
        $summer = Trip::factory()->for($owner)->create();
        Point::factory()->for($summer)->create(['country_code' => 'DE', 'position' => 1]);
        Point::factory()->for($summer)->create(['country_code' => 'FR', 'position' => 2]);
        $winter = Trip::factory()->for($owner)->create();
        Point::factory()->for($winter)->create(['country_code' => 'DE', 'position' => 1]);
        Point::factory()->for($winter)->create(['country_code' => null, 'position' => 2]);
        User::factory()->create(['name' => 'Other User', 'is_public' => true]);
        User::factory()->create(['name' => 'Private User', 'is_public' => false]);
        Sanctum::actingAs(User::factory()->create(['name' => 'Viewer User']));

        $this->getJson(route('public-profiles.index'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Other User')
            ->assertJsonPath('data.1', ['id' => $owner->id, 'name' => 'Test User', 'trips_count' => 2, 'countries_count' => 2]);
    }

    public function test_search_needs_at_least_two_letters(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('public-profiles.index', ['query' => 'T']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('query');
    }

    public function test_user_sees_public_profile_with_trips_map_and_statistics(): void
    {
        $owner = User::factory()->create(['name' => 'Test User', 'is_public' => true]);
        $older = Trip::factory()->for($owner)->create(['name' => 'Older Trip', 'starts_on' => '2024-05-01']);
        $newer = Trip::factory()->for($owner)->create(['name' => 'Newer Trip', 'starts_on' => '2025-05-01']);
        $segment = Segment::factory()->for($newer)->create(['distance_km' => 877.5]);
        Segment::factory()->for(Trip::factory()->create(['starts_on' => '2025-06-01']))->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('public-profiles.show', $owner))
            ->assertOk()
            ->assertJsonPath('data', ['id' => $owner->id, 'name' => 'Test User']);
        $this->getJson(route('public-profiles.trips.index', $owner))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.1.id', $older->id);
        $this->getJson(route('public-profiles.map.show', [$owner, 'year' => 2025]))
            ->assertOk()
            ->assertJsonCount(1, 'data.features')
            ->assertJsonPath('data.features.0.id', $segment->id);
        $this->getJson(route('public-profiles.statistics.show', [$owner, 'year' => 2025]))
            ->assertOk()
            ->assertJsonPath('data.trips_count', 1)
            ->assertJsonPath('data.distance_km', 877.5);
    }

    public function test_private_profile_looks_like_missing_one(): void
    {
        $owner = User::factory()->create(['is_public' => false]);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('public-profiles.show', $owner))
            ->assertNotFound()
            ->assertJsonPath('message', "No query results for model [App\\Models\\User] {$owner->id}");
        $this->getJson(route('public-profiles.show', $owner->id + 1))
            ->assertNotFound()
            ->assertJsonPath('message', 'No query results for model [App\\Models\\User] '.($owner->id + 1));
        $this->getJson(route('public-profiles.trips.index', $owner))->assertNotFound();
        $this->getJson(route('public-profiles.map.show', $owner))->assertNotFound();
        $this->getJson(route('public-profiles.statistics.show', $owner))->assertNotFound();
    }

    public function test_user_reads_trip_of_public_profile_but_cannot_change_it(): void
    {
        $trip = Trip::factory()->for(User::factory()->create(['is_public' => true]))->create();
        $point = Point::factory()->for($trip)->create(['name' => 'Berlin']);
        $segment = Segment::factory()->for($trip)->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('trips.show', $trip))
            ->assertOk()
            ->assertJsonPath('data.points.0.name', 'Berlin')
            ->assertJsonPath('data.segments.0.id', $segment->id);
        $this->getJson(route('points.show', $point))->assertOk();

        $this->patchJson(route('trips.update', $trip), ['name' => 'Renamed Trip'])->assertForbidden();
        $this->deleteJson(route('trips.destroy', $trip))->assertForbidden();
        $this->postJson(route('trips.points.store', $trip), ['name' => 'Paris', 'latitude' => 48.8566, 'longitude' => 2.3522])
            ->assertForbidden();
        $this->patchJson(route('points.update', $point), ['name' => 'Paris'])->assertForbidden();
        $this->deleteJson(route('points.destroy', $point))->assertForbidden();
        $this->patchJson(route('segments.update', $segment), ['transport' => 'plane'])->assertForbidden();

        $this->assertModelExists($point);
        $this->assertDatabaseHas('trips', ['id' => $trip->id, 'name' => $trip->name]);
    }

    public function test_trip_of_private_profile_stays_closed(): void
    {
        $trip = Trip::factory()->for(User::factory()->create(['is_public' => false]))->create();
        $point = Point::factory()->for($trip)->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('trips.show', $trip))->assertForbidden();
        $this->getJson(route('points.show', $point))->assertForbidden();
    }

    public function test_blocked_public_profile_disappears(): void
    {
        $owner = User::factory()->blocked()->create(['name' => 'Blocked Test', 'is_public' => true]);
        $trip = Trip::factory()->for($owner)->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('public-profiles.index', ['query' => 'Blocked']))
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->getJson(route('public-profiles.show', $owner))->assertNotFound();
        $this->getJson(route('trips.show', $trip))->assertForbidden();
    }

    public function test_guest_cannot_see_public_profiles(): void
    {
        $owner = User::factory()->create(['is_public' => true]);

        $this->getJson(route('public-profiles.index', ['query' => 'Test']))->assertUnauthorized();
        $this->getJson(route('public-profiles.show', $owner))->assertUnauthorized();
    }
}
