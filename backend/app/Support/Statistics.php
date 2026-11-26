<?php

namespace App\Support;

final readonly class Statistics
{
    /**
     * @param  array<int, string>  $countries
     * @param  array<string, float>  $distanceByTransport
     * @param  array<int, int>  $years
     * @param  list<array<string, float|int>>  $distanceByYear
     */
    public function __construct(
        public int $tripsCount,
        public array $countries,
        public int $placesCount,
        public float $distanceKm,
        public array $distanceByTransport,
        public array $years,
        public array $distanceByYear,
    ) {}
}
