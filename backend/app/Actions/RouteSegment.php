<?php

namespace App\Actions;

use App\Enums\Transport;
use App\Models\Segment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RouteSegment
{
    private const EARTH_RADIUS_KM = 6371.0088;

    private const ARC_STEPS = 64;

    public function handle(Segment $segment): void
    {
        $from = [$segment->fromPoint->longitude, $segment->fromPoint->latitude];
        $to = [$segment->toPoint->longitude, $segment->toPoint->latitude];
        $road = $segment->transport === Transport::Car ? $this->road($from, $to) : null;

        $coordinates = match (true) {
            $road !== null => $road['coordinates'],
            $segment->transport === Transport::Plane => $this->arc($from, $to),
            default => [$from, $to],
        };
        $distance = $road['distance_km'] ?? self::EARTH_RADIUS_KM * $this->angle($from, $to);

        $segment->fill([
            'geometry' => ['type' => 'LineString', 'coordinates' => $coordinates],
            'distance_km' => round($distance, 1),
            'is_approximate' => $segment->transport === Transport::Car && $road === null,
        ])->save();
    }

    /**
     * @param  array{float, float}  $from
     * @param  array{float, float}  $to
     * @return array{coordinates: array<mixed>, distance_km: float}|null
     */
    private function road(array $from, array $to): ?array
    {
        return Cache::remember('roads:'.implode(',', [...$from, ...$to]), now()->addDays(30), function () use ($from, $to): ?array {
            try {
                $response = Http::withHeaders(['Authorization' => config()->string('services.openrouteservice.key')])
                    ->timeout(5)
                    ->post(config()->string('services.openrouteservice.url').'/openrouteservice/v2/directions/driving-car/geojson', [
                        'coordinates' => [$from, $to],
                    ]);
            } catch (ConnectionException) {
                return null;
            }

            $coordinates = $response->json('features.0.geometry.coordinates');
            $meters = $response->json('features.0.properties.summary.distance');

            if ($response->failed() || ! is_array($coordinates) || ! is_numeric($meters)) {
                return null;
            }

            return ['coordinates' => $coordinates, 'distance_km' => (float) $meters / 1000];
        });
    }

    /**
     * @param  array{float, float}  $from
     * @param  array{float, float}  $to
     * @return list<array{float, float}>
     */
    private function arc(array $from, array $to): array
    {
        $angle = $this->angle($from, $to);

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
     * @param  array{float, float}  $from
     * @param  array{float, float}  $to
     */
    private function angle(array $from, array $to): float
    {
        [$longitude1, $latitude1, $longitude2, $latitude2] = array_map(deg2rad(...), [...$from, ...$to]);
        $haversine = sin(($latitude2 - $latitude1) / 2) ** 2
            + cos($latitude1) * cos($latitude2) * sin(($longitude2 - $longitude1) / 2) ** 2;

        return 2 * asin(min(1.0, sqrt($haversine)));
    }
}
