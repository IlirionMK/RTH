<?php

namespace App\Support;

final readonly class Place
{
    public function __construct(
        public string $name,
        public string $label,
        public ?string $countryCode,
        public float $latitude,
        public float $longitude,
    ) {}
}
