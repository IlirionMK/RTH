<?php

namespace Tests\Feature;

use App\Models\Segment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SegmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_changes_transport_of_segment(): void
    {
        $segment = Segment::factory()->create();
        Sanctum::actingAs($segment->trip->user);

        $this->patchJson(route('segments.update', $segment), ['transport' => 'plane'])
            ->assertOk()
            ->assertJsonPath('data.id', $segment->id)
            ->assertJsonPath('data.transport', 'plane');

        $this->assertDatabaseHas('segments', ['id' => $segment->id, 'transport' => 'plane']);
    }

    public function test_transport_is_car_plane_train_or_other(): void
    {
        $segment = Segment::factory()->create();
        Sanctum::actingAs($segment->trip->user);

        $this->patchJson(route('segments.update', $segment), ['transport' => 'bike'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('transport');
    }

    public function test_user_cannot_change_segment_of_another_users_trip(): void
    {
        $segment = Segment::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson(route('segments.update', $segment), ['transport' => 'plane'])->assertForbidden();

        $this->assertDatabaseHas('segments', ['id' => $segment->id, 'transport' => 'car']);
    }
}
