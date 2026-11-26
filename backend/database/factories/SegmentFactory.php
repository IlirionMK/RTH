<?php

namespace Database\Factories;

use App\Models\Point;
use App\Models\Segment;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Segment>
 */
class SegmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'from_point_id' => fn (array $attributes): Point => Point::factory()->create([
                'trip_id' => $attributes['trip_id'],
                'position' => 1,
            ]),
            'to_point_id' => fn (array $attributes): Point => Point::factory()->create([
                'trip_id' => $attributes['trip_id'],
                'position' => 2,
            ]),
            'geometry' => ['type' => 'LineString', 'coordinates' => [[13.405, 52.52], [2.3522, 48.8566]]],
            'distance_km' => 877.5,
        ];
    }
}
