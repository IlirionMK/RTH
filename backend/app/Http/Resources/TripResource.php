<?php

namespace App\Http\Resources;

use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Trip
 */
class TripResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'starts_on' => $this->starts_on->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'description' => $this->description,
            'points' => PointResource::collection($this->whenLoaded('points')),
            'segments' => SegmentResource::collection($this->whenLoaded('segments')),
        ];
    }
}
