<?php

namespace App\Actions;

use App\Support\GreatCircle;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FindRailRoute
{
    private const RAIL_MODES = 'HIGHSPEED_RAIL,LONG_DISTANCE,NIGHT_RAIL,REGIONAL_FAST_RAIL,REGIONAL_RAIL,RAIL';

    private const MAX_WALK_SECONDS = 3600;

    /**
     * @param  array{float, float}  $from
     * @param  array{float, float}  $to
     * @return array{coordinates: list<array{float, float}>, distance_km: float}|null
     */
    public function handle(array $from, array $to): ?array
    {
        return Cache::remember('rails:'.implode(',', [...$from, ...$to]), now()->addDays(30), function () use ($from, $to): ?array {
            try {
                $response = Http::withUserAgent('RTH/1.0 (+https://github.com/IlirionMK/RTH)')
                    ->timeout(10)
                    ->get(config()->string('services.transitous.url').'/api/v1/plan', [
                        'fromPlace' => $from[1].','.$from[0],
                        'toPlace' => $to[1].','.$to[0],
                        'time' => now()->utc()->next('Tuesday')->setTime(7, 0)->toIso8601ZuluString(),
                        'transitModes' => self::RAIL_MODES,
                        'maxPreTransitTime' => self::MAX_WALK_SECONDS,
                        'maxPostTransitTime' => self::MAX_WALK_SECONDS,
                    ]);
            } catch (ConnectionException) {
                return null;
            }

            $itinerary = $response->collect('itineraries')
                ->sortBy([['transfers', 'asc'], ['duration', 'asc']])
                ->first();
            $coordinates = [];

            foreach (data_get($itinerary, 'legs', []) as $leg) {
                array_push($coordinates, ...$this->decode(
                    (string) data_get($leg, 'legGeometry.points'),
                    (int) data_get($leg, 'legGeometry.precision'),
                ));
            }

            if ($response->failed() || count($coordinates) < 2) {
                return null;
            }

            return ['coordinates' => $coordinates, 'distance_km' => GreatCircle::lengthKm($coordinates)];
        });
    }

    /**
     * @return list<array{float, float}>
     */
    private function decode(string $points, int $precision): array
    {
        $factor = 10 ** $precision;
        $coordinates = [];
        $index = 0;
        $latitude = 0;
        $longitude = 0;

        while ($index < strlen($points)) {
            $latitude += $this->nextNumber($points, $index);
            $longitude += $this->nextNumber($points, $index);
            $coordinates[] = [round($longitude / $factor, 6), round($latitude / $factor, 6)];
        }

        return $coordinates;
    }

    private function nextNumber(string $points, int &$index): int
    {
        $result = 0;
        $shift = 0;

        do {
            $chunk = ord($points[$index++]) - 63;
            $result |= ($chunk & 0x1F) << $shift;
            $shift += 5;
        } while ($chunk >= 0x20);

        return $result & 1 ? ~($result >> 1) : $result >> 1;
    }
}
