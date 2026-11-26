<?php

namespace App\Http\Resources;

use App\Models\Segment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Segment
 */
class SegmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_point_id' => $this->from_point_id,
            'to_point_id' => $this->to_point_id,
            'transport' => $this->transport,
            'distance_km' => $this->distance_km,
            'is_approximate' => $this->is_approximate,
            /** @var array{type: 'LineString', coordinates: list<array{float, float}>} */
            'geometry' => $this->geometry,
        ];
    }
}
