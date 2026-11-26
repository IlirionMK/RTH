<?php

namespace Database\Factories;

use App\Models\Point;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Point>
 */
class PointFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'name' => fake()->city(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'position' => 1,
        ];
    }
}
