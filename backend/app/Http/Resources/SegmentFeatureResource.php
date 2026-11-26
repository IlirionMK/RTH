<?php

namespace App\Http\Resources;

use App\Models\Segment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Segment
 */
class SegmentFeatureResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'Feature',
            'id' => $this->id,
            /** @var array{type: 'LineString', coordinates: list<array{float, float}>} */
            'geometry' => $this->map_geometry,
            'properties' => [
                'trip_id' => $this->trip_id,
                'trip_name' => $this->trip->name,
                'transport' => $this->transport,
                'distance_km' => $this->distance_km,
            ],
        ];
    }
}
