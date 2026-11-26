<?php

namespace App\Http\Resources;

use App\Enums\Transport;
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
            'user_id' => $this->user_id,
            'name' => $this->name,
            'starts_on' => $this->starts_on->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'description' => $this->description,
            'distance_km' => $this->whenHas('distance_km', fn (mixed $value): float => round((float) $value, 1)),
            /** @var list<string> */
            'countries' => $this->whenLoaded('points', fn (): array => $this->points->pluck('country_code')->filter()->unique()->values()->all()),
            'transports' => $this->whenHas('transports', fn (?string $value): array => $value === null ? [] : array_map(Transport::from(...), explode(',', $value))),
            'points' => PointResource::collection($this->whenLoaded('points')),
            'segments' => SegmentResource::collection($this->whenLoaded('segments')),
        ];
    }
}
