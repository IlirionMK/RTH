<?php

namespace App\Actions;

use App\Enums\Transport;
use App\Models\Point;
use App\Models\Segment;
use App\Models\Trip;
use App\Models\User;
use App\Support\Statistics;

class CollectStatistics
{
    public function handle(User $user, ?int $year): Statistics
    {
        $trips = Trip::query()->whereBelongsTo($user)->startedIn($year);
        $points = Point::query()->whereIn('trip_id', $trips->clone()->select('id'));
        $distances = Segment::query()
            ->whereIn('trip_id', $trips->clone()->select('id'))
            ->groupBy('transport')
            ->selectRaw('transport, sum(distance_km) as distance_km')
            ->toBase()
            ->pluck('distance_km', 'transport');

        $distanceByTransport = [];

        foreach (Transport::cases() as $transport) {
            $distanceByTransport[$transport->value] = round((float) ($distances[$transport->value] ?? 0), 1);
        }

        return new Statistics(
            tripsCount: $trips->count(),
            countries: $points->clone()->whereNotNull('country_code')->distinct()->orderBy('country_code')->pluck('country_code')->all(),
            placesCount: (int) $points->clone()->toBase()->selectRaw('count(distinct (round(latitude, 3), round(longitude, 3))) as places')->value('places'),
            distanceKm: round(array_sum($distanceByTransport), 1),
            distanceByTransport: $distanceByTransport,
        );
    }
}
