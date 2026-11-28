<?php

namespace App\Support;

final class GreatCircle
{
    private const EARTH_RADIUS_KM = 6371.0088;

    /**
     * @param  array{float, float}  $from
     * @param  array{float, float}  $to
     */
    public static function angle(array $from, array $to): float
    {
        [$longitude1, $latitude1, $longitude2, $latitude2] = array_map(deg2rad(...), [...$from, ...$to]);
        $haversine = sin(($latitude2 - $latitude1) / 2) ** 2
            + cos($latitude1) * cos($latitude2) * sin(($longitude2 - $longitude1) / 2) ** 2;

        return 2 * asin(min(1.0, sqrt($haversine)));
    }

    /**
     * @param  list<array{float, float}>  $coordinates
     */
    public static function lengthKm(array $coordinates): float
    {
        $angle = 0.0;

        for ($index = 1; $index < count($coordinates); $index++) {
            $angle += self::angle($coordinates[$index - 1], $coordinates[$index]);
        }

        return self::EARTH_RADIUS_KM * $angle;
    }
}
