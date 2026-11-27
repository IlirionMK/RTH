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

        $years = Trip::query()
            ->whereBelongsTo($user)
            ->toBase()
            ->selectRaw('distinct extract(year from starts_on)::int as year')
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn (mixed $year): int => (int) $year)
            ->all();

        return new Statistics(
            tripsCount: $trips->count(),
            countries: $points->clone()->whereNotNull('country_code')->distinct()->orderBy('country_code')->pluck('country_code')->all(),
            placesCount: (int) $points->clone()->toBase()->selectRaw('count(distinct (round(latitude, 3), round(longitude, 3))) as places')->value('places'),
            distanceKm: round(array_sum($distanceByTransport), 1),
            distanceByTransport: $distanceByTransport,
            years: $years,
            distanceByYear: $this->distanceByYear($user, array_reverse($years)),
        );
    }

    /**
     * @param  array<int, int>  $years
     * @return list<array<string, float|int>>
     */
    private function distanceByYear(User $user, array $years): array
    {
        $distances = Segment::query()
            ->join('trips', 'trips.id', '=', 'segments.trip_id')
            ->where('trips.user_id', $user->id)
            ->groupByRaw('1, 2')
            ->selectRaw('extract(year from trips.starts_on)::int as year, segments.transport, sum(segments.distance_km) as distance_km')
            ->toBase()
            ->get()
            ->groupBy('year');

        return array_map(function (int $year) use ($distances): array {
            $row = ['year' => $year];
            $sums = $distances->get($year)?->pluck('distance_km', 'transport');

            foreach (Transport::cases() as $transport) {
                $row[$transport->value] = round((float) ($sums[$transport->value] ?? 0), 1);
            }

            return $row;
        }, array_values($years));
    }
}
