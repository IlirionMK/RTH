<?php

namespace Tests\Feature;

use App\Models\Segment;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminTripTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_lists_all_trips_newest_first_with_authors(): void
    {
        $author = User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com']);
        $older = Trip::factory()->for($author)->create(['created_at' => now()->subMonth()]);
        $newer = Trip::factory()->create(['name' => 'Newer Trip', 'starts_on' => '2025-05-01', 'created_at' => now()->subDay()]);
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson(route('admin.trips.index'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $newer->id)
            ->assertJsonPath('data.0.name', 'Newer Trip')
            ->assertJsonPath('data.0.starts_on', '2025-05-01')
            ->assertJsonPath('data.1.id', $older->id)
            ->assertJsonPath('data.1.user', ['id' => $author->id, 'name' => 'Test User', 'email' => 'test@example.com'])
            ->assertJsonPath('meta.total', 2);
    }

    public function test_admin_finds_trips_by_name_or_author(): void
    {
        $byName = Trip::factory()->create(['name' => 'Summer in Rome']);
        $byAuthorName = Trip::factory()->for(User::factory()->create(['name' => 'Rome Lover']))->create(['name' => 'Paris']);
        $byAuthorEmail = Trip::factory()->for(User::factory()->create(['email' => 'rome@example.com']))->create(['name' => 'Berlin']);
        Trip::factory()->for(User::factory()->create(['name' => 'Other User', 'email' => 'other@example.com']))->create(['name' => 'Vienna']);
        Sanctum::actingAs(User::factory()->admin()->create(['name' => 'Admin User', 'email' => 'admin@example.com']));

        $ids = $this->getJson(route('admin.trips.index', ['query' => 'rome']))
            ->assertOk()
            ->json('data.*.id');

        $this->assertEqualsCanonicalizing([$byName->id, $byAuthorName->id, $byAuthorEmail->id], $ids);
    }

    public function test_admin_sees_and_deletes_any_trip_but_cannot_change_it(): void
    {
        $segment = Segment::factory()->create();
        $trip = $segment->trip;
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson(route('trips.show', $trip))
            ->assertOk()
            ->assertJsonPath('data.segments.0.geometry', $segment->geometry);
        $this->patchJson(route('trips.update', $trip), ['name' => 'Renamed Trip'])->assertForbidden();
        $this->deleteJson(route('trips.destroy', $trip))->assertNoContent();

        $this->assertModelMissing($trip);
        $this->assertModelMissing($segment);
    }
}
