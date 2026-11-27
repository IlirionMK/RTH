<?php

namespace App\Actions;

use App\Enums\Transport;
use App\Models\Segment;
use App\Support\GreatCircle;

class RouteSegment
{
    private const ARC_STEPS = 64;

    private const MAP_TOLERANCE_DEGREES = 0.005;

    public function __construct(
        private FindRoadRoute $findRoadRoute,
        private FindRailRoute $findRailRoute,
    ) {}

    public function handle(Segment $segment): void
    {
        $from = [$segment->fromPoint->longitude, $segment->fromPoint->latitude];
        $to = [$segment->toPoint->longitude, $segment->toPoint->latitude];
        $route = match ($segment->transport) {
            Transport::Car => $this->findRoadRoute->handle($from, $to),
            Transport::Train => $this->findRailRoute->handle($from, $to),
            default => null,
        };

        $coordinates = match (true) {
            $route !== null => $route['coordinates'],
            $segment->transport === Transport::Plane => $this->arc($from, $to),
            default => [$from, $to],
        };
        $distance = $route['distance_km'] ?? GreatCircle::lengthKm([$from, $to]);

        $segment->fill([
            'geometry' => ['type' => 'LineString', 'coordinates' => $coordinates],
            'map_geometry' => ['type' => 'LineString', 'coordinates' => $this->simplify($coordinates)],
            'distance_km' => round($distance, 1),
            'is_approximate' => in_array($segment->transport, [Transport::Car, Transport::Train], true) && $route === null,
        ])->save();
    }

    /**
     * @param  array{float, float}  $from
     * @param  array{float, float}  $to
     * @return list<array{float, float}>
     */
    private function arc(array $from, array $to): array
    {
        $angle = GreatCircle::angle($from, $to);

        if ($angle === 0.0) {
            return [$from, $to];
        }

        [$longitude1, $latitude1, $longitude2, $latitude2] = array_map(deg2rad(...), [...$from, ...$to]);
        $coordinates = [$from];

        for ($step = 1; $step <= self::ARC_STEPS; $step++) {
            $a = sin((1 - $step / self::ARC_STEPS) * $angle) / sin($angle);
            $b = sin($step / self::ARC_STEPS * $angle) / sin($angle);
            $x = $a * cos($latitude1) * cos($longitude1) + $b * cos($latitude2) * cos($longitude2);
            $y = $a * cos($latitude1) * sin($longitude1) + $b * cos($latitude2) * sin($longitude2);
            $z = $a * sin($latitude1) + $b * sin($latitude2);

            $longitude = rad2deg(atan2($y, $x));
            $previous = $coordinates[$step - 1][0];
            $longitude += round(($previous - $longitude) / 360) * 360;

            $coordinates[] = [round($longitude, 6), round(rad2deg(atan2($z, sqrt($x ** 2 + $y ** 2))), 6)];
        }

        return $coordinates;
    }

    /**
     * @param  array<mixed>  $coordinates
     * @return list<mixed>
     */
    private function simplify(array $coordinates): array
    {
        $coordinates = array_values($coordinates);
        $last = count($coordinates) - 1;

        if ($last < 2) {
            return $coordinates;
        }

        $kept = [0 => true, $last => true];
        $ranges = [[0, $last]];

        while ($ranges !== []) {
            [$start, $end] = array_pop($ranges);
            $farthest = null;
            $farthestOffset = self::MAP_TOLERANCE_DEGREES;

            for ($index = $start + 1; $index < $end; $index++) {
                $offset = $this->offset($coordinates[$index], $coordinates[$start], $coordinates[$end]);

                if ($offset > $farthestOffset) {
                    $farthest = $index;
                    $farthestOffset = $offset;
                }
            }

            if ($farthest !== null) {
                $kept[$farthest] = true;
                $ranges[] = [$start, $farthest];
                $ranges[] = [$farthest, $end];
            }
        }

        ksort($kept);

        return array_values(array_intersect_key($coordinates, $kept));
    }

    /**
     * @param  array{float, float}  $point
     * @param  array{float, float}  $start
     * @param  array{float, float}  $end
     */
    private function offset(array $point, array $start, array $end): float
    {
        $dx = $end[0] - $start[0];
        $dy = $end[1] - $start[1];
        $length = $dx ** 2 + $dy ** 2;
        $share = $length > 0 ? max(0, min(1, (($point[0] - $start[0]) * $dx + ($point[1] - $start[1]) * $dy) / $length)) : 0;

        return hypot($point[0] - $start[0] - $share * $dx, $point[1] - $start[1] - $share * $dy);
    }
}
