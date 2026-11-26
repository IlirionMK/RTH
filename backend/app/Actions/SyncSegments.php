<?php

namespace App\Actions;

use App\Models\Segment;
use App\Models\Trip;
use Illuminate\Support\Collection;

class SyncSegments
{
    public function handle(Trip $trip): void
    {
        $segments = $trip->segments()->get()
            ->keyBy(fn (Segment $segment): string => $segment->from_point_id.'-'.$segment->to_point_id);
        $pairs = $trip->points()->pluck('id')
            ->sliding(2)
            ->keyBy(fn (Collection $pair): string => $pair->implode('-'));

        Segment::whereKey($segments->diffKeys($pairs)->modelKeys())->delete();

        foreach ($pairs->diffKeys($segments) as $pair) {
            $trip->segments()->create(['from_point_id' => $pair->first(), 'to_point_id' => $pair->last()]);
        }
    }
}
