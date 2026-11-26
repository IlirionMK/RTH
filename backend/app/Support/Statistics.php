<?php

namespace App\Support;

final readonly class Statistics
{
    /**
     * @param  array<int, string>  $countries
     * @param  array<string, float>  $distanceByTransport
     */
    public function __construct(
        public int $tripsCount,
        public array $countries,
        public int $placesCount,
        public float $distanceKm,
        public array $distanceByTransport,
    ) {}
}
