<?php

namespace App\Actions;

use App\Models\Point;
use App\Models\Segment;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdatePoint
{
    public function __construct(
        private SyncSegments $syncSegments,
        private RouteSegment $routeSegment,
        private ResolveCountry $resolveCountry,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Point $point, array $attributes): Point
    {
        return DB::transaction(function () use ($point, $attributes): Point {
            $point->fill(Arr::except($attributes, 'position'));

            if ($point->isDirty(['latitude', 'longitude'])) {
                $point->country_code = $this->resolveCountry->handle($point->latitude, $point->longitude);
            }

            $point->save();

            if ($point->wasChanged(['latitude', 'longitude'])) {
                $segments = Segment::where('from_point_id', $point->id)->orWhere('to_point_id', $point->id)->get();

                foreach ($segments as $segment) {
                    $this->routeSegment->handle($segment);
                }
            }

            if (array_key_exists('position', $attributes)) {
                $this->move($point, (int) $attributes['position']);
            }

            return $point;
        });
    }

    private function move(Point $point, int $position): void
    {
        $trip = $point->trip;
        $from = $point->position;
        $to = min($position, Point::whereBelongsTo($trip)->count());

        if ($to === $from) {
            return;
        }

        if ($to < $from) {
            Point::whereBelongsTo($trip)->whereBetween('position', [$to, $from - 1])->increment('position');
        } else {
            Point::whereBelongsTo($trip)->whereBetween('position', [$from + 1, $to])->decrement('position');
        }

        $point->update(['position' => $to]);
        $this->syncSegments->handle($trip);
    }
}
