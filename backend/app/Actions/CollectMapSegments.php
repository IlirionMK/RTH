<?php

namespace App\Actions;

use App\Models\Segment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CollectMapSegments
{
    /**
     * @return Collection<int, Segment>
     */
    public function handle(User $user, ?int $year): Collection
    {
        return Segment::query()
            ->select(['id', 'trip_id', 'transport', 'distance_km', 'map_geometry'])
            ->whereIn('trip_id', $user->trips()->startedIn($year)->select('id'))
            ->with('trip:id,name')
            ->orderBy('trip_id')
            ->orderBy('id')
            ->get();
    }
}
