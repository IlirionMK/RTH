<?php

namespace App\Actions;

use App\Models\Point;
use App\Models\Trip;
use Illuminate\Support\Facades\DB;

class AddPoint
{
    public function __construct(
        private SyncSegments $syncSegments,
        private ResolveCountry $resolveCountry,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Trip $trip, array $attributes): Point
    {
        return DB::transaction(function () use ($trip, $attributes): Point {
            $last = Point::whereBelongsTo($trip)->count() + 1;
            $position = min((int) ($attributes['position'] ?? $last), $last);

            Point::whereBelongsTo($trip)->where('position', '>=', $position)->increment('position');
            $point = $trip->points()->create([
                ...$attributes,
                'position' => $position,
                'country_code' => $this->resolveCountry->handle((float) $attributes['latitude'], (float) $attributes['longitude']),
            ]);
            $this->syncSegments->handle($trip);

            return $point;
        });
    }
}
