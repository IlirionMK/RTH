<?php

namespace App\Http\Resources;

use App\Support\Statistics;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Statistics
 */
class StatisticsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'trips_count' => $this->tripsCount,
            'countries_count' => count($this->countries),
            'countries' => $this->countries,
            'places_count' => $this->placesCount,
            'distance_km' => $this->distanceKm,
            /** @var array{car: float, plane: float, train: float, other: float} */
            'distance_by_transport' => $this->distanceByTransport,
        ];
    }
}
